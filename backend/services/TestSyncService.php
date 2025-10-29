<?php
namespace backend\services;

use backend\models\ShopeeSettlement;
use backend\models\ShopeeTransaction;
use Yii;
use backend\models\Order;
use backend\models\OnlineChannel;
use backend\models\ShopeeToken;

// สมมติว่ามีตาราง shopee_tokens
use backend\models\TiktokToken;

// สมมติว่ามีตาราง tiktok_tokens
use yii\base\Exception;
use GuzzleHttp\Client;
use yii\helpers\Json;

class TestSyncService
{
    private $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }

    ///////// IMPROVED TIKTOK FEE TRANSACTION WITH DEBUGGING
     private function refreshTikTokToken($tokenModel)
        {
            try {
                $appKey = '6h9n461r774e1';
                $appSecret = '1c45a0c25224293abd7de681049f90de3363389a';
                $refreshToken = $tokenModel->refresh_token;

                // Parameters ตาม API document
                $params = [
                    'app_key' => $appKey,
                    'app_secret' => $appSecret,
                    'refresh_token' => $refreshToken,
                    'grant_type' => 'refresh_token',
                ];

                // ใช้ GET request ตาม API document
                $url = 'https://auth.tiktok-shops.com/api/v2/token/refresh';
                $getUrl = $url . '?' . http_build_query($params);

                $client = new \GuzzleHttp\Client(['timeout' => 30]);

                $response = $client->get($getUrl, [
                    'headers' => [
                        'User-Agent' => 'YourApp/1.0',
                        'Accept' => 'application/json',
                    ]
                ]);

                $body = $response->getBody()->getContents();
                $data = json_decode($body, true);

                if (isset($data['data']['access_token'])) {
                    $tokenModel->access_token = $data['data']['access_token'];
                    $tokenModel->refresh_token = $data['data']['refresh_token'];
                    $tokenModel->expires_at = date('Y-m-d H:i:s', time() + $data['data']['access_token_expire_in']);
                    $tokenModel->updated_at = date('Y-m-d H:i:s');

                    return $tokenModel->save();
                }

            } catch (\Exception $e) {
                Yii::error('Failed to refresh TikTok token: ' . $e->getMessage(), __METHOD__);
            }

            return false;
        }

    /**
     * Main function to sync TikTok fees
     */
    private function getTikTokOrderTransactions($order_id, $shop_id, $app_key, $app_secret, $access_token)
    {
        Yii::info("Getting transaction details for order: $order_id", __METHOD__);

        try {
            // Clean order ID (remove underscore if exists)
            $cleanOrderId = $order_id;
            if (strpos($order_id, '_') !== false) {
                $parts = explode('_', $order_id);
                $cleanOrderId = $parts[0]; // Try first part
                Yii::info("Cleaned order ID from $order_id to $cleanOrderId", __METHOD__);
            }

            $timestamp = time();

            // Use Finance API endpoint
            $path = "/finance/202501/orders/{$cleanOrderId}/statement_transactions";

            $params = [
                'app_key' => $app_key,
                'timestamp' => $timestamp,
                'shop_id' => $shop_id,
                'access_token' => $access_token,
            ];

            ksort($params);

            // Generate signature
            $sign_string = $path;
            foreach ($params as $key => $value) {
                if ($key != 'access_token' && $key != 'sign') {
                    $sign_string .= $key . $value;
                }
            }
            $sign = hash_hmac('sha256', $sign_string, $app_secret);

            $url = "https://open-api.tiktokglobalshop.com" . $path;
            $queryParams = array_merge($params, ['sign' => $sign]);

            Yii::info("Calling Finance API: $url", __METHOD__);

            $response = $this->httpClient->get($url, [
                'query' => $queryParams,
                'timeout' => 30,
                'http_errors' => false,
                'headers' => [
                    'Content-Type' => 'application/json',
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $rawBody = (string)$response->getBody();

            Yii::debug("Response Status: $statusCode", __METHOD__);
            Yii::debug("Response Body: " . substr($rawBody, 0, 1000), __METHOD__);

            if ($statusCode !== 200) {
                Yii::error("HTTP Error $statusCode for order $order_id", __METHOD__);
                return null;
            }

            $data = Json::decode($rawBody);

            if (!isset($data['code'])) {
                Yii::error("Invalid response format", __METHOD__);
                return null;
            }

            if ($data['code'] != 0) {
                Yii::warning("API Error: Code={$data['code']}, Message=" . ($data['message'] ?? 'Unknown'), __METHOD__);

                // If order ID with underscore didn't work, try second part
                if (strpos($order_id, '_') !== false && $cleanOrderId != $order_id) {
                    $parts = explode('_', $order_id);
                    if (count($parts) > 1) {
                        Yii::info("Retrying with second part of order ID", __METHOD__);
                        return $this->getTikTokOrderTransactions($parts[1], $shop_id, $app_key, $app_secret, $access_token);
                    }
                }

                return null;
            }

            if (empty($data['data'])) {
                Yii::warning("Empty data returned for order $order_id", __METHOD__);
                return null;
            }

            Yii::info("✓ Successfully got transaction details for order $order_id", __METHOD__);
            return $data['data'];

        } catch (\Exception $e) {
            Yii::error("Exception getting transactions for order $order_id: " . $e->getMessage(), __METHOD__);
            Yii::error("Stack trace: " . $e->getTraceAsString(), __METHOD__);
            return null;
        }
    }

    /**
     * Process TikTok order fees from Finance API transaction data
     * @param int $channel_id
     * @param Order $order
     * @param array $transactionData
     * @param string $shop_id
     * @return bool
     */
    private function processTikTokOrderTransactions($channel_id, $order, $transactionData, $shop_id)
    {
        try {
            Yii::info("Processing transaction data for order: {$order->order_id}", __METHOD__);
            Yii::debug("Transaction data: " . Json::encode($transactionData), __METHOD__);

            // Extract main amounts
            $revenue_amount = (float)($transactionData['revenue_amount'] ?? 0);
            $fee_and_tax_amount = (float)($transactionData['fee_and_tax_amount'] ?? 0);
            $shipping_cost_amount = (float)($transactionData['shipping_cost_amount'] ?? 0);
            $settlement_amount = (float)($transactionData['settlement_amount'] ?? 0);

            Yii::info("Order {$order->order_id} amounts:", __METHOD__);
            Yii::info("  Revenue: $revenue_amount", __METHOD__);
            Yii::info("  Fee & Tax: $fee_and_tax_amount", __METHOD__);
            Yii::info("  Shipping Cost: $shipping_cost_amount", __METHOD__);
            Yii::info("  Settlement: $settlement_amount", __METHOD__);

            // Get SKU transactions (detailed breakdown)
            $sku_transactions = $transactionData['sku_transactions'] ?? [];

            if (empty($sku_transactions)) {
                Yii::warning("No SKU transactions found", __METHOD__);
                return false;
            }

            // Sum up all fees from all SKU transactions
            $totalCommission = 0;
            $totalTransactionFee = 0;
            $totalAffiliateFee = 0;
            $totalServiceFee = 0;
            $totalTax = 0;
            $totalShippingCost = 0;

            foreach ($sku_transactions as $sku) {
                $feeBreakdown = $sku['fee_tax_breakdown']['fee'] ?? [];
                $taxBreakdown = $sku['fee_tax_breakdown']['tax'] ?? [];

                // Commission fees
                $platformCommission = abs((float)($feeBreakdown['platform_commission_amount'] ?? 0));
                $referralFee = abs((float)($feeBreakdown['referral_fee_amount'] ?? 0));
                $totalCommission += $platformCommission + $referralFee;

                // Transaction fees
                $transactionFee = abs((float)($feeBreakdown['transaction_fee_amount'] ?? 0));
                $creditCardFee = abs((float)($feeBreakdown['credit_card_handling_fee_amount'] ?? 0));
                $totalTransactionFee += $transactionFee + $creditCardFee;

                // Affiliate fees
                $affiliateCommission = abs((float)($feeBreakdown['affiliate_commission_amount'] ?? 0));
                $affiliatePartner = abs((float)($feeBreakdown['affiliate_partner_commission_amount'] ?? 0));
                $affiliateAds = abs((float)($feeBreakdown['affiliate_ads_commission_amount'] ?? 0));
                $totalAffiliateFee += $affiliateCommission + $affiliatePartner + $affiliateAds;

                // Service fees
                $sfpServiceFee = abs((float)($feeBreakdown['sfp_service_fee_amount'] ?? 0));
                $liveSpecialsFee = abs((float)($feeBreakdown['live_specials_fee_amount'] ?? 0));
                $mallServiceFee = abs((float)($feeBreakdown['mall_service_fee_amount'] ?? 0));
                $voucherServiceFee = abs((float)($feeBreakdown['voucher_xtra_service_fee_amount'] ?? 0));
                $flashSalesFee = abs((float)($feeBreakdown['flash_sales_service_fee_amount'] ?? 0));
                $cofundedPromoFee = abs((float)($feeBreakdown['cofunded_promotion_service_fee_amount'] ?? 0));
                $preOrderFee = abs((float)($feeBreakdown['pre_order_service_fee_amount'] ?? 0));
                $tspCommission = abs((float)($feeBreakdown['tsp_commission_amount'] ?? 0));
                $dtHandlingFee = abs((float)($feeBreakdown['dt_handling_fee_amount'] ?? 0));
                $eprPobFee = abs((float)($feeBreakdown['epr_pob_service_fee_amount'] ?? 0));
                $feePerItem = abs((float)($feeBreakdown['fee_per_item_sold_amount'] ?? 0));
                $paylaterFee = abs((float)($feeBreakdown['seller_paylater_handling_fee_amount'] ?? 0));
                $creatorBonus = abs((float)($feeBreakdown['cofunded_creator_bonus_amount'] ?? 0));
                $dynamicCommission = abs((float)($feeBreakdown['dynamic_commission_amount'] ?? 0));
                $externalAffiliateFee = abs((float)($feeBreakdown['external_affiliate_marketing_fee_amount'] ?? 0));
                $installationFee = abs((float)($feeBreakdown['installation_service_fee'] ?? 0));
                $campaignResourceFee = abs((float)($feeBreakdown['campaign_resource_fee'] ?? 0));

                $totalServiceFee += $sfpServiceFee + $liveSpecialsFee + $mallServiceFee + $voucherServiceFee +
                    $flashSalesFee + $cofundedPromoFee + $preOrderFee + $tspCommission +
                    $dtHandlingFee + $eprPobFee + $feePerItem + $paylaterFee +
                    $creatorBonus + $dynamicCommission + $externalAffiliateFee +
                    $installationFee + $campaignResourceFee;

                // Taxes
                $vat = abs((float)($taxBreakdown['vat_amount'] ?? 0));
                $importVat = abs((float)($taxBreakdown['import_vat_amount'] ?? 0));
                $customsDuty = abs((float)($taxBreakdown['customs_duty_amount'] ?? 0));
                $customsClearance = abs((float)($taxBreakdown['customs_clearance_amount'] ?? 0));
                $sst = abs((float)($taxBreakdown['sst_amount'] ?? 0));
                $gst = abs((float)($taxBreakdown['gst_amount'] ?? 0));
                $iva = abs((float)($taxBreakdown['iva_amount'] ?? 0));
                $isr = abs((float)($taxBreakdown['isr_amount'] ?? 0));
                $antiDumping = abs((float)($taxBreakdown['anti_dumping_duty_amount'] ?? 0));
                $localVat = abs((float)($taxBreakdown['local_vat_amount'] ?? 0));
                $pit = abs((float)($taxBreakdown['pit_amount'] ?? 0));

                $totalTax += $vat + $importVat + $customsDuty + $customsClearance + $sst +
                    $gst + $iva + $isr + $antiDumping + $localVat + $pit;

                // Shipping costs
                $skuShippingCost = abs((float)($sku['shipping_cost_amount'] ?? 0));
                $totalShippingCost += $skuShippingCost;
            }

            Yii::info("Fee breakdown:", __METHOD__);
            Yii::info("  Commission: $totalCommission", __METHOD__);
            Yii::info("  Transaction: $totalTransactionFee", __METHOD__);
            Yii::info("  Affiliate: $totalAffiliateFee", __METHOD__);
            Yii::info("  Service: $totalServiceFee", __METHOD__);
            Yii::info("  Tax: $totalTax", __METHOD__);
            Yii::info("  Shipping: $totalShippingCost", __METHOD__);

            // Create fee transactions
            $fees = [];

            if ($totalCommission > 0) {
                $fees['COMMISSION_FEE'] = $totalCommission;
            }

            if ($totalTransactionFee > 0) {
                $fees['TRANSACTION_FEE'] = $totalTransactionFee;
            }

            if ($totalAffiliateFee > 0) {
                $fees['AFFILIATE_FEE'] = $totalAffiliateFee;
            }

            if ($totalServiceFee > 0) {
                $fees['SERVICE_FEE'] = $totalServiceFee;
            }

            if ($totalShippingCost > 0) {
                $fees['SHIPPING_FEE'] = $totalShippingCost;
            }

            if (empty($fees)) {
                Yii::warning("No fees to record for order {$order->order_id}", __METHOD__);
                return false;
            }

            $transactionCreated = false;

            // Create transaction records
            foreach ($fees as $category => $amount) {
                if ($amount <= 0) continue;

                $transaction_id = 'TT_' . $order->order_id . '_' . $category;

                // Check if exists
                $existing = ShopeeTransaction::findOne(['transaction_id' => $transaction_id]);
                if ($existing) {
                    Yii::debug("Transaction exists: $transaction_id", __METHOD__);
                    continue;
                }

                $feeTransaction = new ShopeeTransaction();
                $feeTransaction->transaction_id = $transaction_id;
                $feeTransaction->channel_id = $channel_id;
                $feeTransaction->shop_id = (string)$shop_id;
                $feeTransaction->transaction_type = 'ORDER_FEE';
                $feeTransaction->status = 'COMPLETED';
                $feeTransaction->reason = str_replace('_', ' ', $category) . ' for order ' . $order->order_id;
                $feeTransaction->amount = -$amount; // Negative for expense
                $feeTransaction->current_balance = 0;
                $feeTransaction->order_sn = $order->order_id;
                $feeTransaction->transaction_date = $order->order_date;
                $feeTransaction->fee_category = $category;
                $feeTransaction->created_at = date('Y-m-d H:i:s');
                $feeTransaction->updated_at = date('Y-m-d H:i:s');

                if ($feeTransaction->save()) {
                    $transactionCreated = true;
                    Yii::info("✓ Created: $transaction_id (-$amount)", __METHOD__);
                } else {
                    Yii::error("✗ Failed to save: " . Json::encode($feeTransaction->errors), __METHOD__);
                }
            }

            // Update order record
            $order->commission_fee = $fees['COMMISSION_FEE'] ?? 0;
            $order->transaction_fee = $fees['TRANSACTION_FEE'] ?? 0;
            $order->payment_fee = $fees['AFFILIATE_FEE'] ?? 0;
            $order->service_fee = $fees['SERVICE_FEE'] ?? 0;

            // Calculate actual income
            if ($settlement_amount > 0) {
                $order->actual_income = $settlement_amount;
            } else {
                $total_fees_amount = array_sum($fees) + $totalTax;
                $order->actual_income = $revenue_amount - $total_fees_amount - $totalShippingCost;
            }

            $order->updated_at = date('Y-m-d H:i:s');

            if ($order->save(false)) {
                Yii::info("✓ Updated order {$order->order_id}", __METHOD__);
                Yii::info("  Commission: {$order->commission_fee}", __METHOD__);
                Yii::info("  Transaction: {$order->transaction_fee}", __METHOD__);
                Yii::info("  Service: {$order->service_fee}", __METHOD__);
                Yii::info("  Actual Income: {$order->actual_income}", __METHOD__);
            } else {
                Yii::error("✗ Failed to update order", __METHOD__);
            }

            return $transactionCreated;

        } catch (\Exception $e) {
            Yii::error("Exception processing transactions for {$order->order_id}: " . $e->getMessage(), __METHOD__);
            Yii::error("Stack trace: " . $e->getTraceAsString(), __METHOD__);
            return false;
        }
    }

    /**
     * Sync TikTok order income using Finance API
     */
    public function syncTikTokOrderIncomeFinanceAPI($channel, $fromTime = null, $toTime = null)
    {
        $channel_id = is_object($channel) ? $channel->id : (int)$channel;

        if ($fromTime === null) {
            $fromTime = strtotime('-30 day');
        }
        if ($toTime === null) {
            $toTime = time();
        }

        $orders = Order::find()
            ->where(['channel_id' => $channel_id])
            ->andWhere(['>=', 'order_date', date('Y-m-d H:i:s', $fromTime)])
            ->andWhere(['<=', 'order_date', date('Y-m-d H:i:s', $toTime)])
            ->all();

        if (empty($orders)) {
            Yii::info('No orders found', __METHOD__);
            return 0;
        }

        $tokenModel = TiktokToken::find()
            ->where(['status' => 'active'])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if (!$tokenModel) {
            Yii::warning('No active token', __METHOD__);
            return 0;
        }

        $app_key = '6h9n461r774e1';
        $app_secret = '1c45a0c25224293abd7de681049f90de3363389a';
        $shop_id = $tokenModel->shop_id;
        $access_token = $tokenModel->access_token;

        Yii::info("=== Syncing fees using Finance API ===", __METHOD__);
        Yii::info("Total orders to process: " . count($orders), __METHOD__);

        $count = 0;
        $successCount = 0;
        $skipCount = 0;
        $failCount = 0;

        foreach ($orders as $order) {
            $count++;

            // Skip if already has transactions
            $hasTransaction = ShopeeTransaction::find()
                ->where(['channel_id' => $channel_id])
                ->andWhere(['order_sn' => $order->order_id])
                ->exists();

            if ($hasTransaction) {
                $skipCount++;
                Yii::debug("Order {$order->order_id} already has transactions", __METHOD__);
                continue;
            }

            try {
                // Get transaction details from Finance API
                $transactionData = $this->getTikTokOrderTransactions(
                    $order->order_id,
                    $shop_id,
                    $app_key,
                    $app_secret,
                    $access_token
                );

                if ($transactionData) {
                    if ($this->processTikTokOrderTransactions($channel_id, $order, $transactionData, $shop_id)) {
                        $successCount++;
                        Yii::info("✓ Success: {$order->order_id}", __METHOD__);
                    } else {
                        $failCount++;
                        Yii::warning("✗ Failed to process: {$order->order_id}", __METHOD__);
                    }
                } else {
                    $failCount++;
                    Yii::warning("✗ No transaction data: {$order->order_id}", __METHOD__);

                    // Fallback: Use estimated fees
//                    if ($this->createEstimatedFeesForOrder($channel_id, $order, $shop_id)) {
//                        Yii::info("Created estimated fees for {$order->order_id}", __METHOD__);
//                    }
                }

                usleep(300000); // 0.3 seconds delay

            } catch (\Exception $e) {
                $failCount++;
                Yii::error("Error: " . $e->getMessage(), __METHOD__);
                continue;
            }

            // Progress report
            if ($count % 10 == 0) {
                Yii::info("Progress: {$count}/" . count($orders) . " (Success: {$successCount}, Skip: {$skipCount}, Fail: {$failCount})", __METHOD__);
            }
        }

        Yii::info("=== Sync Complete ===", __METHOD__);
        Yii::info("Total: " . count($orders), __METHOD__);
        Yii::info("Success: {$successCount}", __METHOD__);
        Yii::info("Skipped: {$skipCount}", __METHOD__);
        Yii::info("Failed: {$failCount}", __METHOD__);

        return $successCount;
    }

    /**
     * Main sync function - updated to use Finance API
     */
    private function syncTikTokTransactionFeesV2($channel, $fromTime = null, $toTime = null)
    {
        $channel_id = is_object($channel) ? $channel->id : (int)$channel;

        if (is_int($channel)) {
            $channel = OnlineChannel::findOne($channel_id);
            if (!$channel) {
                Yii::error("Channel not found: $channel_id", __METHOD__);
                return 0;
            }
        }

        if ($fromTime === null) {
            $fromTime = strtotime('-30 day');
        }
        if ($toTime === null) {
            $toTime = time();
        }

        Yii::info("=== Starting TikTok Fee Sync (Finance API) ===", __METHOD__);
        Yii::info("Period: " . date('Y-m-d', $fromTime) . " to " . date('Y-m-d', $toTime), __METHOD__);

        // Use Finance API method
        $totalCount = $this->syncTikTokOrderIncomeFinanceAPI($channel, $fromTime, $toTime);

        Yii::info("Total synced: {$totalCount} orders", __METHOD__);
        return $totalCount;
    }
}
