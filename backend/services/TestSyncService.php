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











    //// SHOPEE

    private function syncShopeeTransactionFeesV2($channel, $fromTime, $toTime)
    {
        $channel_id = is_object($channel) ? $channel->id : (int)$channel;

        if (is_int($channel)) {
            $channel = OnlineChannel::findOne($channel_id);
            if (!$channel) {
                Yii::error("Channel not found: $channel_id", __METHOD__);
                return 0;
            }
        }

        $tokenModel = ShopeeToken::find()
            ->where(['status' => 'active'])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if (!$tokenModel) {
            Yii::warning('No active Shopee token found', __METHOD__);
            return 0;
        }

        // Check token expiry
        if (strtotime($tokenModel->expires_at) < time()) {
            if (!$this->refreshShopeeToken($tokenModel)) {
                Yii::warning('Failed to refresh Shopee token', __METHOD__);
                return 0;
            }
        }

        $partner_id = 2012399;
        $partner_key = 'shpk72476151525864414e4b6e475449626679624f695a696162696570417043';
        $shop_id = $tokenModel->shop_id;
        $access_token = $tokenModel->access_token;

        Yii::info("=== Syncing Shopee Wallet Transactions ===", __METHOD__);
        Yii::info("Period: " . date('Y-m-d H:i:s', $fromTime) . " to " . date('Y-m-d H:i:s', $toTime), __METHOD__);

        $totalCount = 0;
        $page_no = 1;
        $page_size = 100; // Max 100 per page

        try {
            do {
                $timestamp = time();
                $path = "/api/v2/payment/get_wallet_transaction_list";

                // Generate sign
                $base_string = $partner_id . $path . $timestamp . $access_token . $shop_id;
                $sign = hash_hmac('sha256', $base_string, $partner_key);

                $params = [
                    'partner_id' => (int)$partner_id,
                    'shop_id' => (int)$shop_id,
                    'sign' => $sign,
                    'timestamp' => $timestamp,
                    'access_token' => $access_token,
                ];

                $body = [
                    'transaction_time_from' => (int)$fromTime,
                    'transaction_time_to' => (int)$toTime,
                    'page_no' => $page_no,
                    'page_size' => $page_size,
                ];

                Yii::info("Fetching page $page_no (page_size: $page_size)", __METHOD__);

                $response = $this->httpClient->post('https://partner.shopeemobile.com' . $path, [
                    'query' => $params,
                    'json' => $body,
                    'timeout' => 30,
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ]
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode !== 200) {
                    Yii::error("HTTP Error: $statusCode", __METHOD__);
                    break;
                }

                $rawBody = (string)$response->getBody();
                Yii::debug("Response body: " . substr($rawBody, 0, 500), __METHOD__);

                $data = Json::decode($rawBody);

                // Check API error
                if (!empty($data['error'])) {
                    Yii::error("Shopee API Error: {$data['error']} - " . ($data['message'] ?? 'Unknown'), __METHOD__);
                    break;
                }

                // Get transaction list
                $response_data = $data['response'] ?? [];
                $transactionList = $response_data['transaction_list'] ?? [];
                $more = $response_data['more'] ?? false;
                $total = $response_data['total'] ?? 0;

                if (empty($transactionList)) {
                    Yii::info('No more transactions found', __METHOD__);
                    break;
                }

                Yii::info("Processing " . count($transactionList) . " transactions", __METHOD__);

                // Process each transaction
                foreach ($transactionList as $transaction) {
                    if ($this->processShopeeWalletTransaction($channel_id, $transaction, $shop_id)) {
                        $totalCount++;
                    }
                }

                // Check if has more pages
                if (!$more) {
                    Yii::info("No more pages (total processed: $totalCount/$total)", __METHOD__);
                    break;
                }

                $page_no++;
                usleep(200000); // 0.2 second delay

            } while (true);

            Yii::info("✓ Total synced: $totalCount transactions", __METHOD__);

        } catch (\Exception $e) {
            Yii::error('Shopee Transaction API error: ' . $e->getMessage(), __METHOD__);
            Yii::error('Stack trace: ' . $e->getTraceAsString(), __METHOD__);
        }

        return $totalCount;
    }

    /**
     * Process individual Shopee wallet transaction
     * @param int $channel_id
     * @param array $transaction
     * @param string $shop_id
     * @return bool
     */
    private function processShopeeWalletTransaction($channel_id, $transaction, $shop_id)
    {
        try {
            $transaction_id = $transaction['transaction_id'] ?? null;
            if (empty($transaction_id)) {
                Yii::warning("Missing transaction_id", __METHOD__);
                return false;
            }

            $transaction_id = (string)$transaction_id;

            // Check if exists
            $existing = ShopeeTransaction::findOne(['transaction_id' => $transaction_id]);
            if ($existing) {
                Yii::debug("Transaction exists: $transaction_id", __METHOD__);
                return false;
            }

            $feeTransaction = new ShopeeTransaction();
            $feeTransaction->transaction_id = $transaction_id;
            $feeTransaction->channel_id = $channel_id;
            $feeTransaction->shop_id = (string)$shop_id;

            // Transaction type และ reason
            $transaction_type = $transaction['transaction_type'] ?? 'UNKNOWN';
            $feeTransaction->transaction_type = (string)$transaction_type;

            $feeTransaction->reason = isset($transaction['reason'])
                ? (string)$transaction['reason']
                : $transaction_type;

            // Amount
            $amount = (float)($transaction['amount'] ?? 0);
            $feeTransaction->amount = $amount;

            // Current balance
            $feeTransaction->current_balance = (float)($transaction['current_balance'] ?? 0);

            // Status
            $feeTransaction->status = isset($transaction['status'])
                ? (string)$transaction['status']
                : 'COMPLETED';

            // Order reference - VALIDATE BEFORE SETTING
            $order_sn = $transaction['order_sn'] ?? $transaction['order_id'] ?? null;

            if (!empty($order_sn)) {
                $order_sn = (string)$order_sn;

                // Check if order exists in system
                $orderExists = Order::find()
                    ->where(['order_sn' => $order_sn])
                    ->orWhere(['order_id' => $order_sn])
                    ->exists();

                if ($orderExists) {
                    // Order found - set it
                    $feeTransaction->order_sn = $order_sn;
                    Yii::debug("Order found for transaction: $order_sn", __METHOD__);
                } else {
                    // Order not found - store in reason but don't set order_sn
                    $feeTransaction->reason .= " [Order: $order_sn]";
                    Yii::debug("Order not in system: $order_sn (stored in reason)", __METHOD__);
                }
            }

            // Transaction date
            $create_time = $transaction['create_time'] ?? time();
            $feeTransaction->transaction_date = date('Y-m-d H:i:s', $create_time);

            // Categorize fee
            $feeTransaction->fee_category = $this->categorizeShopeeTransaction($transaction);

            $feeTransaction->created_at = date('Y-m-d H:i:s');
            $feeTransaction->updated_at = date('Y-m-d H:i:s');

            if ($feeTransaction->save()) {
                Yii::info("Saved: $transaction_id (Type: {$feeTransaction->transaction_type}, Amount: $amount)", __METHOD__);
                return true;
            } else {
                $errors = Json::encode($feeTransaction->errors);
                Yii::error("Failed to save $transaction_id: $errors", __METHOD__);

                // Try to save without order_sn if validation failed on order_sn
                if (isset($feeTransaction->errors['order_sn'])) {
                    Yii::warning("Retrying save without order_sn for $transaction_id", __METHOD__);
                    if (!empty($feeTransaction->order_sn)) {
                        $feeTransaction->reason .= " [Failed Order: {$feeTransaction->order_sn}]";
                        $feeTransaction->order_sn = null;

                        if ($feeTransaction->save()) {
                            Yii::info("Saved without order_sn: $transaction_id", __METHOD__);
                            return true;
                        }
                    }
                }

                return false;
            }

        } catch (\Exception $e) {
            Yii::error('Error processing wallet transaction: ' . $e->getMessage(), __METHOD__);
            Yii::error('Transaction data: ' . Json::encode($transaction), __METHOD__);
            return false;
        }
    }

    /**
     * Categorize Shopee transaction into fee categories
     * @param array $transaction
     * @return string
     */
//    private function categorizeShopeeTransaction($transaction)
//    {
//        $type = strtolower($transaction['transaction_type'] ?? '');
//        $reason = strtolower($transaction['reason'] ?? '');
//        $amount = (float)($transaction['amount'] ?? 0);
//
//        // Only categorize expenses (negative amounts)
//        if ($amount >= 0) {
//            return 'INCOME';
//        }
//
//        // Shopee transaction types
//        // Commission fees
//        if (strpos($type, 'commission') !== false ||
//            strpos($reason, 'commission') !== false ||
//            strpos($reason, 'seller commission') !== false) {
//            return 'COMMISSION_FEE';
//        }
//
//        // Transaction fees
//        if (strpos($type, 'transaction_fee') !== false ||
//            strpos($reason, 'transaction fee') !== false ||
//            strpos($reason, 'payment fee') !== false) {
//            return 'TRANSACTION_FEE';
//        }
//
//        // Service fees
//        if (strpos($type, 'service_fee') !== false ||
//            strpos($reason, 'service fee') !== false ||
//            strpos($reason, 'platform fee') !== false) {
//            return 'SERVICE_FEE';
//        }
//
//        // Shipping fees
//        if (strpos($type, 'shipping') !== false ||
//            strpos($reason, 'shipping') !== false ||
//            strpos($reason, 'logistic') !== false ||
//            strpos($reason, 'delivery') !== false) {
//            return 'SHIPPING_FEE';
//        }
//
//        // Campaign/Marketing fees
//        if (strpos($type, 'campaign') !== false ||
//            strpos($type, 'ads') !== false ||
//            strpos($type, 'marketing') !== false ||
//            strpos($reason, 'promotion') !== false ||
//            strpos($reason, 'voucher') !== false ||
//            strpos($reason, 'discount') !== false ||
//            strpos($reason, 'flash sale') !== false ||
//            strpos($reason, 'campaign') !== false) {
//            return 'CAMPAIGN_FEE';
//        }
//
//        // Penalty/Fine
//        if (strpos($type, 'penalty') !== false ||
//            strpos($type, 'fine') !== false ||
//            strpos($reason, 'penalty') !== false ||
//            strpos($reason, 'fine') !== false ||
//            strpos($reason, 'violation') !== false) {
//            return 'PENALTY_FEE';
//        }
//
//        // Refund
//        if (strpos($type, 'refund') !== false ||
//            strpos($type, 'return') !== false ||
//            strpos($reason, 'refund') !== false ||
//            strpos($reason, 'return') !== false) {
//            return 'REFUND';
//        }
//
//        // Adjustment
//        if (strpos($type, 'adjustment') !== false ||
//            strpos($reason, 'adjustment') !== false ||
//            strpos($reason, 'correction') !== false) {
//            return 'ADJUSTMENT';
//        }
//
//        // Withdrawal
//        if (strpos($type, 'withdrawal') !== false ||
//            strpos($type, 'payout') !== false ||
//            strpos($reason, 'withdrawal') !== false ||
//            strpos($reason, 'transfer') !== false) {
//            return 'WITHDRAWAL';
//        }
//
//        // Payment fee (separate from transaction fee)
//        if (strpos($type, 'payment') !== false && strpos($type, 'fee') !== false) {
//            return 'PAYMENT_FEE';
//        }
//
//        // Others
//        return 'OTHER';
//    }

    /**
     * Sync Shopee order income details using Order Income API
     * @param OnlineChannel|int $channel
     * @param int $fromTime
     * @param int $toTime
     * @return int
     */
    private function syncShopeeOrderIncomeV2($channel, $fromTime, $toTime)
    {
        $channel_id = is_object($channel) ? $channel->id : (int)$channel;

        if (is_int($channel)) {
            $channel = OnlineChannel::findOne($channel_id);
            if (!$channel) {
                Yii::error("Channel not found: $channel_id", __METHOD__);
                return 0;
            }
        }

        $tokenModel = ShopeeToken::find()
            ->where(['status' => 'active'])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if (!$tokenModel) {
            Yii::warning('No active Shopee token found', __METHOD__);
            return 0;
        }

        if (strtotime($tokenModel->expires_at) < time()) {
            if (!$this->refreshShopeeToken($tokenModel)) {
                return 0;
            }
        }

        $partner_id = 2012399;
        $partner_key = 'shpk72476151525864414e4b6e475449626679624f695a696162696570417043';
        $shop_id = $tokenModel->shop_id;
        $access_token = $tokenModel->access_token;

        // Get orders in the period
        $orders = Order::find()
            ->where(['channel_id' => $channel_id])
            ->andWhere(['>=', 'order_date', date('Y-m-d H:i:s', $fromTime)])
            ->andWhere(['<=', 'order_date', date('Y-m-d H:i:s', $toTime)])
            ->all();

        if (empty($orders)) {
            Yii::info('No orders found', __METHOD__);
            return 0;
        }

        Yii::info("Processing " . count($orders) . " orders for income details", __METHOD__);

        $count = 0;
        $successCount = 0;
        $skipCount = 0;

        foreach ($orders as $order) {
            $count++;

            // Skip if already synced recently (within 1 hour)
            if ($order->fee_sync_at && strtotime($order->fee_sync_at) > strtotime('-1 hour')) {
                $skipCount++;
                continue;
            }

            try {
                // Get order income details from API
                $incomeDetails = $this->getShopeeOrderIncome(
                    $order->order_sn,
                    $shop_id,
                    $partner_id,
                    $partner_key,
                    $access_token
                );

                if ($incomeDetails) {
                    if ($this->processShopeeOrderIncome($channel_id, $order, $incomeDetails)) {
                        $successCount++;
                    }
                }

                usleep(200000); // 0.2 second delay

            } catch (\Exception $e) {
                Yii::error("Error processing order {$order->order_sn}: " . $e->getMessage(), __METHOD__);
                continue;
            }

            if ($count % 20 == 0) {
                Yii::info("Progress: {$count}/" . count($orders) . " (Success: {$successCount}, Skip: {$skipCount})", __METHOD__);
            }
        }

        Yii::info("✓ Updated {$successCount} orders with income details", __METHOD__);
        return $successCount;
    }

    /**
     * Get Shopee order income details
     * @param string $order_sn
     * @param string $shop_id
     * @param int $partner_id
     * @param string $partner_key
     * @param string $access_token
     * @return array|null
     */
    private function getShopeeOrderIncome($order_sn, $shop_id, $partner_id, $partner_key, $access_token)
    {
        try {
            $timestamp = time();
            $path = "/api/v2/order/get_order_detail";

            $base_string = $partner_id . $path . $timestamp . $access_token . $shop_id;
            $sign = hash_hmac('sha256', $base_string, $partner_key);

            $params = [
                'partner_id' => (int)$partner_id,
                'shop_id' => (int)$shop_id,
                'sign' => $sign,
                'timestamp' => $timestamp,
                'access_token' => $access_token,
                'order_sn_list' => $order_sn,
                'response_optional_fields' => 'buyer_user_id,buyer_username,estimated_shipping_fee,recipient_address,actual_shipping_fee,goods_to_declare,note,note_update_time,item_list,pay_time,dropshipper,dropshipper_phone,split_up,buyer_cancel_reason,cancel_by,cancel_reason,actual_shipping_fee_confirmed,buyer_cpf_id,fulfillment_flag,pickup_done_time,package_list,shipping_carrier,payment_method,total_amount,buyer_username,invoice_data,checkout_shipping_carrier,reverse_shipping_fee,order_chargeable_weight_gram,edt'
            ];

            $response = $this->httpClient->get('https://partner.shopeemobile.com' . $path, [
                'query' => $params,
                'timeout' => 30
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $data = Json::decode((string)$response->getBody());

            if (!empty($data['error']) || empty($data['response']['order_list'])) {
                return null;
            }

            return $data['response']['order_list'][0] ?? null;

        } catch (\Exception $e) {
            Yii::error("Error getting order income for $order_sn: " . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /**
     * Process Shopee order income details
     * @param int $channel_id
     * @param Order $order
     * @param array $incomeDetails
     * @return bool
     */
    private function processShopeeOrderIncome($channel_id, $order, $incomeDetails)
    {
        try {
            // Extract income breakdown
            $income_breakdown = $incomeDetails['income_breakdown'] ?? [];

            if (empty($income_breakdown)) {
                Yii::debug("No income breakdown for order {$order->order_sn}", __METHOD__);
                return false;
            }

            // Main amounts
            $actual_shipping_fee = (float)($incomeDetails['actual_shipping_fee'] ?? 0);
            $escrow_amount = (float)($income_breakdown['escrow_amount'] ?? 0);
            $seller_discount = (float)($income_breakdown['seller_discount'] ?? 0);
            $shopee_discount = (float)($income_breakdown['shopee_discount'] ?? 0);
            $coins_cashback = (float)($income_breakdown['coins_cashback'] ?? 0);
            $voucher_from_seller = (float)($income_breakdown['voucher_from_seller'] ?? 0);
            $voucher_from_shopee = (float)($income_breakdown['voucher_from_shopee'] ?? 0);

            // Fees
            $commission_fee = abs((float)($income_breakdown['commission_fee'] ?? 0));
            $transaction_fee = abs((float)($income_breakdown['transaction_fee'] ?? 0));
            $service_fee = abs((float)($income_breakdown['service_fee'] ?? 0));
            $payment_fee = abs((float)($income_breakdown['payment_fee'] ?? 0));

            // Buyer paid amount
            $buyer_paid_amount = (float)($income_breakdown['original_price'] ?? $order->total_amount);

            // Actual income
            $seller_income = (float)($income_breakdown['actual_income'] ?? 0);
            if ($seller_income == 0) {
                $seller_income = $order->total_amount - $commission_fee - $transaction_fee - $service_fee - $payment_fee;
            }

            Yii::info("Order {$order->order_sn} income:", __METHOD__);
            Yii::info("  Commission: $commission_fee", __METHOD__);
            Yii::info("  Transaction: $transaction_fee", __METHOD__);
            Yii::info("  Service: $service_fee", __METHOD__);
            Yii::info("  Payment: $payment_fee", __METHOD__);
            Yii::info("  Actual Income: $seller_income", __METHOD__);

            // Update order
            $order->commission_fee = $commission_fee;
            $order->transaction_fee = $transaction_fee;
            $order->service_fee = $service_fee;
            $order->payment_fee = $payment_fee;
            $order->actual_income = $seller_income;
            $order->escrow_amount = $escrow_amount;
            $order->seller_discount = $seller_discount + $voucher_from_seller;
            $order->shopee_discount = $shopee_discount + $voucher_from_shopee + $coins_cashback;
            $order->buyer_paid_amount = $buyer_paid_amount;
            $order->fee_sync_at = date('Y-m-d H:i:s');
            $order->updated_at = date('Y-m-d H:i:s');

            if ($order->save(false)) {
                Yii::info("✓ Updated order {$order->order_sn}", __METHOD__);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Yii::error("Error processing order income: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    private function calculateFeeSummary($channel, $fromTime, $toTime)
    {
        $transactions = ShopeeTransaction::find()
            ->where(['channel_id' => $channel])
            ->andWhere(['>=', 'transaction_date', date('Y-m-d H:i:s', $fromTime)])
            ->andWhere(['<=', 'transaction_date', date('Y-m-d H:i:s', $toTime)])
            ->andWhere(['<', 'amount', 0]) // เฉพาะค่าใช้จ่าย
            ->all();

        $summary = [
            'commission_fee' => 0,
            'transaction_fee' => 0,
            'service_fee' => 0,
            'shipping_fee' => 0,
            'campaign_fee' => 0,
            'penalty_fee' => 0,
            'refund' => 0,
            'other' => 0,
            'total_fees' => 0,
        ];

        foreach ($transactions as $trans) {
            $amount = abs($trans->amount);
            $summary['total_fees'] += $amount;

            switch ($trans->fee_category) {
                case 'COMMISSION_FEE':
                    $summary['commission_fee'] += $amount;
                    break;
                case 'TRANSACTION_FEE':
                    $summary['transaction_fee'] += $amount;
                    break;
                case 'SERVICE_FEE':
                    $summary['service_fee'] += $amount;
                    break;
                case 'SHIPPING_FEE':
                    $summary['shipping_fee'] += $amount;
                    break;
                case 'CAMPAIGN_FEE':
                    $summary['campaign_fee'] += $amount;
                    break;
                case 'PENALTY_FEE':
                    $summary['penalty_fee'] += $amount;
                    break;
                case 'REFUND':
                    $summary['refund'] += $amount;
                    break;
                default:
                    $summary['other'] += $amount;
                    break;
            }
        }

        return $summary;
    }
    /**
     * Main sync function - Updated to use V2 methods
     */
    public function syncMonthlyShopeeFeesV2($channel, $year = null, $month = null)
    {
        if ($year === null) {
            $year = (int)date('Y');
        }
        if ($month === null) {
            $month = (int)date('m')-1;
        }

        $fromTime = strtotime("$year-$month-01 00:00:00");
        if ($month == 12) {
            $toTime = strtotime(($year + 1) . "-01-01 00:00:00") - 1;
        } else {
            $toTime = strtotime("$year-" . ($month + 1) . "-01 00:00:00") - 1;
        }

        Yii::info("=== Syncing Shopee fees for {$year}-{$month} (V2) ===", __METHOD__);

        $results = [
            'success' => true,
            'platform' => 'Shopee',
            'period' => [
                'year' => $year,
                'month' => $month,
                'from' => date('Y-m-d H:i:s', $fromTime),
                'to' => date('Y-m-d H:i:s', $toTime),
            ]
        ];

        try {
            // 1. Sync wallet transactions (V2)
            Yii::info('Step 1: Syncing wallet transactions (V2)...', __METHOD__);
            $transactionCount = $this->syncShopeeTransactionFeesV2($channel, $fromTime, $toTime);
            $results['transaction_count'] = $transactionCount;
            Yii::info("✓ Synced {$transactionCount} transactions", __METHOD__);

            // 2. Sync order income details (V2)
            Yii::info('Step 2: Syncing order income details (V2)...', __METHOD__);
            $orderIncomeCount = $this->syncShopeeOrderIncomeV2($channel, $fromTime, $toTime);
            $results['order_income_count'] = $orderIncomeCount;
            Yii::info("✓ Updated {$orderIncomeCount} orders", __METHOD__);

            // 3. Sync settlements
            Yii::info('Step 3: Syncing settlements...', __METHOD__);
            $settlementCount = $this->syncShopeeSettlements($channel, $fromTime, $toTime);
            $results['settlement_count'] = $settlementCount;
            Yii::info("✓ Synced {$settlementCount} settlements", __METHOD__);

            // 4-6. Calculate summaries (use existing functions)
            $results['transaction_summary'] = $this->calculateFeeSummary($channel, $fromTime, $toTime);
            $results['order_summary'] = $this->calculateOrderFeeSummary($channel, $fromTime, $toTime);
            $results['settlement_summary'] = $this->calculateSettlementSummary($channel, $fromTime, $toTime);

            $results['grand_summary'] = [
                'total_revenue' => $results['order_summary']['total_revenue'],
                'total_buyer_paid' => $results['order_summary']['total_buyer_paid'],
                'total_all_fees' => $results['order_summary']['total_all_fees'],
                'total_actual_income' => $results['order_summary']['total_actual_income'],
                'total_settlements_received' => $results['settlement_summary']['total_net_amount'],
                'fee_percentage' => $results['order_summary']['fee_percentage'],
                'total_orders' => $results['order_summary']['total_orders'],
                'total_settlements' => $results['settlement_summary']['total_settlements'],
            ];

            Yii::info("=== Sync completed ===", __METHOD__);

        } catch (\Exception $e) {
            $results['success'] = false;
            $results['error'] = $e->getMessage();
            Yii::error("Sync failed: " . $e->getMessage(), __METHOD__);
        }

        return $results;
    }

    private function calculateOrderFeeSummary($channel, $fromTime, $toTime)
    {
        $orders = Order::find()
            ->where(['channel_id' => $channel])
            ->andWhere(['>=', 'order_date', date('Y-m-d H:i:s', $fromTime)])
            ->andWhere(['<=', 'order_date', date('Y-m-d H:i:s', $toTime)])
            ->all();

        $summary = [
            'total_orders' => count($orders),
            'total_quantity' => 0,
            'total_revenue' => 0,
            'total_buyer_paid' => 0,
            'total_commission_fee' => 0,
            'total_transaction_fee' => 0,
            'total_service_fee' => 0,
            'total_payment_fee' => 0,
            'total_seller_discount' => 0,
            'total_shopee_discount' => 0,
            'total_escrow' => 0,
            'total_actual_income' => 0,
            'total_all_fees' => 0,
        ];

        foreach ($orders as $order) {
            $summary['total_quantity'] += $order->quantity;
            $summary['total_revenue'] += $order->total_amount;
            $summary['total_buyer_paid'] += $order->buyer_paid_amount;
            $summary['total_commission_fee'] += $order->commission_fee;
            $summary['total_transaction_fee'] += $order->transaction_fee;
            $summary['total_service_fee'] += $order->service_fee;
            $summary['total_payment_fee'] += $order->payment_fee;
            $summary['total_seller_discount'] += $order->seller_discount;
            $summary['total_shopee_discount'] += $order->shopee_discount;
            $summary['total_escrow'] += $order->escrow_amount;
            $summary['total_actual_income'] += $order->actual_income;

            // รวมค่าธรรมเนียมทั้งหมด
            $summary['total_all_fees'] += ($order->commission_fee +
                $order->transaction_fee +
                $order->service_fee +
                $order->payment_fee);
        }

        // คำนวณเปอร์เซ็นต์
        if ($summary['total_revenue'] > 0) {
            $summary['fee_percentage'] = ($summary['total_all_fees'] / $summary['total_revenue']) * 100;
            $summary['commission_percentage'] = ($summary['total_commission_fee'] / $summary['total_revenue']) * 100;
        } else {
            $summary['fee_percentage'] = 0;
            $summary['commission_percentage'] = 0;
        }

        return $summary;
    }
    /**
     * Sync Shopee settlements from wallet transactions
     * Since get_settlement_list API doesn't exist, we extract settlement data from wallet transactions
     */
    private function syncShopeeSettlements($channel, $fromTime, $toTime)
    {
        $channel_id = is_object($channel) ? $channel->id : (int)$channel;

        Yii::info("=== Syncing Shopee Settlements from Wallet Transactions ===", __METHOD__);

        // Get all withdrawal/payout transactions from wallet transactions that were already synced
        $payoutTransactions = ShopeeTransaction::find()
            ->where(['channel_id' => $channel_id])
            ->andWhere(['>=', 'transaction_date', date('Y-m-d H:i:s', $fromTime)])
            ->andWhere(['<=', 'transaction_date', date('Y-m-d H:i:s', $toTime)])
            ->andWhere(['fee_category' => 'WITHDRAWAL'])
            ->all();

        if (empty($payoutTransactions)) {
            Yii::info('No payout/withdrawal transactions found in the period', __METHOD__);
            return 0;
        }

        Yii::info('Found ' . count($payoutTransactions) . ' payout transactions', __METHOD__);

        $count = 0;

        foreach ($payoutTransactions as $trans) {
            try {
                // Check if settlement already exists
                $existing = ShopeeSettlement::find()
                    ->where([
                        'channel_id' => $channel_id,
                        'transaction_id' => $trans->transaction_id,
                    ])
                    ->one();

                if ($existing) {
                    Yii::debug("Settlement already exists for transaction: {$trans->transaction_id}", __METHOD__);
                    continue;
                }

                // Create settlement from transaction
                $settlement = new ShopeeSettlement();
                $settlement->channel_id = $channel_id;
                $settlement->shop_id = $trans->shop_id;
                $settlement->transaction_id = $trans->transaction_id;
                $settlement->settlement_no = $trans->transaction_id; // Use transaction ID as settlement number

                // Payout amount is the absolute value of withdrawal amount
                $payoutAmount = abs($trans->amount);
                $settlement->settlement_amount = $payoutAmount;

                // Try to get related order fees to calculate settlement fee
                // Settlement fee = sum of all fees for orders in this payout period
                $relatedFees = ShopeeTransaction::find()
                    ->where(['channel_id' => $channel_id])
                    ->andWhere(['<', 'amount', 0]) // negative amounts = fees
                    ->andWhere(['!=', 'fee_category', 'WITHDRAWAL'])
                    ->andWhere(['>=', 'transaction_date', date('Y-m-d H:i:s', strtotime($trans->transaction_date) - 86400 * 7)]) // 7 days before payout
                    ->andWhere(['<=', 'transaction_date', $trans->transaction_date])
                    ->sum('ABS(amount)');

                $settlement->settlement_fee = $relatedFees ?: 0;
                $settlement->net_settlement_amount = $payoutAmount;

                $settlement->payout_time = $trans->transaction_date;
                $settlement->status = 'completed';

                // Count related orders
                $orderCount = Order::find()
                    ->where(['channel_id' => $channel_id])
                    ->andWhere(['>=', 'order_date', date('Y-m-d H:i:s', strtotime($trans->transaction_date) - 86400 * 7)])
                    ->andWhere(['<=', 'order_date', $trans->transaction_date])
                    ->count();

                $settlement->order_count = $orderCount;
                $settlement->created_at = date('Y-m-d H:i:s');
                $settlement->updated_at = date('Y-m-d H:i:s');

                if ($settlement->save()) {
                    $count++;
                    Yii::info("✓ Created settlement from transaction: {$trans->transaction_id}, Amount: {$payoutAmount}", __METHOD__);
                } else {
                    Yii::error("Failed to save settlement: " . Json::encode($settlement->errors), __METHOD__);
                }

            } catch (\Exception $e) {
                Yii::error("Error processing settlement for transaction {$trans->transaction_id}: " . $e->getMessage(), __METHOD__);
                continue;
            }
        }

        Yii::info("✓ Created {$count} settlements from wallet transactions", __METHOD__);
        return $count;
    }
    private function calculateSettlementSummary($channel, $fromTime, $toTime)
    {
        $settlements = ShopeeSettlement::find()
            ->where(['channel_id' => $channel])
            ->andWhere(['>=', 'payout_time', date('Y-m-d H:i:s', $fromTime)])
            ->andWhere(['<=', 'payout_time', date('Y-m-d H:i:s', $toTime)])
            ->all();

        $summary = [
            'total_settlements' => count($settlements),
            'total_settlement_amount' => 0,
            'total_settlement_fee' => 0,
            'total_net_amount' => 0,
            'total_orders' => 0,
            'avg_settlement_amount' => 0,
            'avg_fee_percentage' => 0,
        ];

        $totalFeePercentage = 0;

        foreach ($settlements as $settlement) {
            $summary['total_settlement_amount'] += $settlement->settlement_amount;
            $summary['total_settlement_fee'] += $settlement->settlement_fee;
            $summary['total_net_amount'] += $settlement->net_settlement_amount;
            $summary['total_orders'] += $settlement->order_count;
            $totalFeePercentage += $settlement->getFeePercentage();
        }

        if (count($settlements) > 0) {
            $summary['avg_settlement_amount'] = $summary['total_settlement_amount'] / count($settlements);
            $summary['avg_fee_percentage'] = $totalFeePercentage / count($settlements);
        }

        return $summary;
    }



    private function syncShopeeWalletTransactionsOfficial($channel, $fromTime, $toTime)
    {
        $channel_id = is_object($channel) ? $channel->id : (int)$channel;

        if (is_int($channel)) {
            $channel = OnlineChannel::findOne($channel_id);
            if (!$channel) {
                Yii::error("Channel not found: $channel_id", __METHOD__);
                return 0;
            }
        }

        $tokenModel = ShopeeToken::find()
            ->where(['status' => 'active'])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if (!$tokenModel) {
            Yii::warning('No active Shopee token found', __METHOD__);
            return 0;
        }

        // Check token expiry
        if (strtotime($tokenModel->expires_at) < time()) {
            if (!$this->refreshShopeeToken($tokenModel)) {
                Yii::warning('Failed to refresh Shopee token', __METHOD__);
                return 0;
            }
        }

        $partner_id = 2012399;
        $partner_key = 'shpk72476151525864414e4b6e475449626679624f695a696162696570417043';
        $shop_id = $tokenModel->shop_id;
        $access_token = $tokenModel->access_token;

        Yii::info("=== Syncing Shopee Wallet Transactions (Official API) ===", __METHOD__);
        Yii::info("Period: " . date('Y-m-d H:i:s', $fromTime) . " to " . date('Y-m-d H:i:s', $toTime), __METHOD__);
        Yii::info("Shop ID: $shop_id", __METHOD__);

        $totalCount = 0;
        $page_no = 1;
        $page_size = 100; // Max 100 per request

        try {
            do {
                $timestamp = time();

                // Official API endpoint (GET method)
                $path = "/api/v2/payment/get_wallet_transaction_list";

                // Generate sign according to documentation
                // Format: partner_id + path + timestamp + access_token + shop_id
                $base_string = $partner_id . $path . $timestamp . $access_token . $shop_id;
                $sign = hash_hmac('sha256', $base_string, $partner_key);

                // Query parameters (all in URL for GET request)
                $params = [
                    'partner_id' => (int)$partner_id,
                    'shop_id' => (int)$shop_id,
                    'sign' => $sign,
                    'timestamp' => $timestamp,
                    'access_token' => $access_token,
                    'transaction_time_from' => (int)$fromTime,
                    'transaction_time_to' => (int)$toTime,
                    'page_no' => $page_no,
                    'page_size' => $page_size,
                ];

                Yii::info("Fetching page $page_no (size: $page_size)", __METHOD__);
                Yii::debug("Query params: " . Json::encode($params), __METHOD__);

                // Use GET method (as per documentation)
                $response = $this->httpClient->get('https://partner.shopeemobile.com' . $path, [
                    'query' => $params,
                    'timeout' => 30,
                    'http_errors' => false,
                ]);

                $statusCode = $response->getStatusCode();
                $rawBody = (string)$response->getBody();

                Yii::debug("Response Status: $statusCode", __METHOD__);
                Yii::debug("Response Body: " . substr($rawBody, 0, 1000), __METHOD__);

                if ($statusCode !== 200) {
                    Yii::error("HTTP Error: $statusCode", __METHOD__);
                    Yii::error("Response: $rawBody", __METHOD__);
                    break;
                }

                $data = Json::decode($rawBody);

                // Check API error
                if (isset($data['error']) && !empty($data['error'])) {
                    Yii::error("Shopee API Error: {$data['error']}", __METHOD__);
                    if (isset($data['message'])) {
                        Yii::error("Error message: {$data['message']}", __METHOD__);
                    }
                    break;
                }

                // Get response data
                $response_data = $data['response'] ?? [];
                $transactionList = $response_data['transaction_list'] ?? [];
                $more = $response_data['more'] ?? false;

                if (empty($transactionList)) {
                    Yii::info('No more transactions found', __METHOD__);
                    break;
                }

                Yii::info("Processing " . count($transactionList) . " transactions from page $page_no", __METHOD__);

                // Process each transaction
                $pageSuccess = 0;
                $pageSkip = 0;
                $pageFail = 0;

                foreach ($transactionList as $transaction) {
                    $result = $this->processShopeeWalletTransactionSafe($channel_id, $transaction, $shop_id);

                    if ($result === true) {
                        $totalCount++;
                        $pageSuccess++;
                    } elseif ($result === 'skip') {
                        $pageSkip++;
                    } else {
                        $pageFail++;
                    }
                }

                Yii::info("Page $page_no: Success=$pageSuccess, Skip=$pageSkip, Fail=$pageFail", __METHOD__);

                // Check if has more pages
                if (!$more) {
                    Yii::info("No more pages", __METHOD__);
                    break;
                }

                $page_no++;
                usleep(300000); // 0.3 second delay between pages

            } while (true);

            Yii::info("✓ Total synced: $totalCount wallet transactions", __METHOD__);

        } catch (\Exception $e) {
            Yii::error('Exception in wallet transaction sync: ' . $e->getMessage(), __METHOD__);
            Yii::error('Stack trace: ' . $e->getTraceAsString(), __METHOD__);
        }

        return $totalCount;
    }

    /**
     * Process Shopee wallet transaction safely (with order validation)
     * @param int $channel_id
     * @param array $transaction
     * @param string $shop_id
     * @return bool|string true=saved, 'skip'=already exists, false=failed
     */
    private function processShopeeWalletTransactionSafe($channel_id, $transaction, $shop_id)
    {
        try {
            // Extract transaction ID
            $transaction_id = $transaction['transaction_id'] ?? null;
            if (empty($transaction_id)) {
                Yii::warning("Missing transaction_id in: " . Json::encode($transaction), __METHOD__);
                return false;
            }

            $transaction_id = (string)$transaction_id;

            // Check if already exists
            $existing = ShopeeTransaction::findOne(['transaction_id' => $transaction_id]);
            if ($existing) {
                Yii::debug("Transaction exists: $transaction_id", __METHOD__);
                return 'skip';
            }

            // Create new transaction record
            $feeTransaction = new ShopeeTransaction();
            $feeTransaction->transaction_id = $transaction_id;
            $feeTransaction->channel_id = $channel_id;
            $feeTransaction->shop_id = (string)$shop_id;

            // Transaction type
            $transaction_type = $transaction['transaction_type'] ?? 'UNKNOWN';
            $feeTransaction->transaction_type = (string)$transaction_type;

            // Reason/Description
            $reason = $transaction['reason'] ?? $transaction['description'] ?? '';
            if (empty($reason)) {
                $reason = $transaction_type;
            }
            $feeTransaction->reason = (string)$reason;

            // Amount
            $amount = (float)($transaction['amount'] ?? 0);
            $feeTransaction->amount = $amount;

            // Current balance
            $feeTransaction->current_balance = (float)($transaction['current_balance'] ?? 0);

            // Status
            $feeTransaction->status = isset($transaction['status'])
                ? (string)$transaction['status']
                : 'COMPLETED';

            // Order reference - VALIDATE BEFORE SETTING
            $order_sn = $transaction['order_sn'] ?? $transaction['order_id'] ?? null;

            if (!empty($order_sn)) {
                $order_sn = (string)$order_sn;

                // Check if order exists in system
                $orderExists = Order::find()
                    ->where(['order_sn' => $order_sn])
                    ->orWhere(['order_id' => $order_sn])
                    ->exists();

                if ($orderExists) {
                    // Order found - set it
                    $feeTransaction->order_sn = $order_sn;
                    Yii::debug("Order found for transaction: $order_sn", __METHOD__);
                } else {
                    // Order not found - store in reason but don't set order_sn
                    $feeTransaction->reason .= " [Order: $order_sn]";
                    Yii::debug("Order not in system: $order_sn (stored in reason)", __METHOD__);
                }
            }

            // Transaction date
            $create_time = $transaction['create_time'] ?? $transaction['transaction_time'] ?? time();
            $feeTransaction->transaction_date = date('Y-m-d H:i:s', $create_time);

            // Categorize transaction
            $feeTransaction->fee_category = $this->categorizeShopeeTransaction($transaction);

            $feeTransaction->created_at = date('Y-m-d H:i:s');
            $feeTransaction->updated_at = date('Y-m-d H:i:s');

            // Save
            if ($feeTransaction->save()) {
                Yii::info("✓ Saved: $transaction_id (Type: {$transaction_type}, Amount: $amount, Category: {$feeTransaction->fee_category})", __METHOD__);
                return true;
            } else {
                $errors = Json::encode($feeTransaction->errors);
                Yii::error("✗ Failed to save $transaction_id: $errors", __METHOD__);
                Yii::debug("Transaction data: " . Json::encode($transaction), __METHOD__);

                // Try to save without order_sn if validation failed on order_sn
                if (isset($feeTransaction->errors['order_sn'])) {
                    Yii::warning("Retrying save without order_sn for $transaction_id", __METHOD__);
                    if (!empty($feeTransaction->order_sn)) {
                        $feeTransaction->reason .= " [Failed Order: {$feeTransaction->order_sn}]";
                        $feeTransaction->order_sn = null;

                        if ($feeTransaction->save()) {
                            Yii::info("✓ Saved without order_sn: $transaction_id", __METHOD__);
                            return true;
                        }
                    }
                }

                return false;
            }

        } catch (\Exception $e) {
            Yii::error('Exception processing transaction: ' . $e->getMessage(), __METHOD__);
            Yii::error('Transaction data: ' . Json::encode($transaction), __METHOD__);
            return false;
        }
    }

    /**
     * Categorize Shopee transaction (enhanced version)
     */
    private function categorizeShopeeTransaction($transaction)
    {
        $type = strtoupper($transaction['transaction_type'] ?? '');
        $reason = strtolower($transaction['reason'] ?? $transaction['description'] ?? '');
        $amount = (float)($transaction['amount'] ?? 0);

        // Income (positive amounts)
        if ($amount > 0) {
            if (strpos($reason, 'order') !== false ||
                strpos($reason, 'payment') !== false ||
                strpos($type, 'ORDER') !== false) {
                return 'ORDER_INCOME';
            }
            if (strpos($reason, 'refund') !== false ||
                strpos($type, 'REFUND') !== false) {
                return 'REFUND_RECEIVED';
            }
            if (strpos($reason, 'adjustment') !== false ||
                strpos($reason, 'compensation') !== false) {
                return 'ADJUSTMENT_IN';
            }
            return 'INCOME';
        }

        // Expenses (negative amounts)
        // Commission
        if (strpos($type, 'COMMISSION') !== false ||
            strpos($reason, 'commission') !== false ||
            strpos($reason, 'seller commission') !== false) {
            return 'COMMISSION_FEE';
        }

        // Transaction fee
        if (strpos($type, 'TRANSACTION_FEE') !== false ||
            strpos($type, 'PAYMENT_FEE') !== false ||
            strpos($reason, 'transaction fee') !== false ||
            strpos($reason, 'payment fee') !== false) {
            return 'TRANSACTION_FEE';
        }

        // Service fee
        if (strpos($type, 'SERVICE_FEE') !== false ||
            strpos($reason, 'service fee') !== false ||
            strpos($reason, 'platform fee') !== false) {
            return 'SERVICE_FEE';
        }

        // Shipping
        if (strpos($type, 'SHIPPING') !== false ||
            strpos($reason, 'shipping') !== false ||
            strpos($reason, 'logistic') !== false ||
            strpos($reason, 'delivery') !== false) {
            return 'SHIPPING_FEE';
        }

        // Campaign/Marketing
        if (strpos($type, 'CAMPAIGN') !== false ||
            strpos($type, 'PROMOTION') !== false ||
            strpos($type, 'ADS') !== false ||
            strpos($reason, 'voucher') !== false ||
            strpos($reason, 'discount') !== false ||
            strpos($reason, 'flash sale') !== false ||
            strpos($reason, 'campaign') !== false ||
            strpos($reason, 'promotion') !== false ||
            strpos($reason, 'marketing') !== false) {
            return 'CAMPAIGN_FEE';
        }

        // Penalty
        if (strpos($type, 'PENALTY') !== false ||
            strpos($type, 'FINE') !== false ||
            strpos($reason, 'penalty') !== false ||
            strpos($reason, 'fine') !== false ||
            strpos($reason, 'violation') !== false) {
            return 'PENALTY_FEE';
        }

        // Refund (outgoing)
        if (strpos($type, 'REFUND') !== false ||
            strpos($type, 'RETURN') !== false ||
            strpos($reason, 'refund') !== false ||
            strpos($reason, 'return') !== false) {
            return 'REFUND';
        }

        // Adjustment
        if (strpos($type, 'ADJUSTMENT') !== false ||
            strpos($reason, 'adjustment') !== false ||
            strpos($reason, 'correction') !== false) {
            return 'ADJUSTMENT';
        }

        // Withdrawal/Payout
        if (strpos($type, 'WITHDRAWAL') !== false ||
            strpos($type, 'PAYOUT') !== false ||
            strpos($reason, 'withdrawal') !== false ||
            strpos($reason, 'payout') !== false ||
            strpos($reason, 'transfer') !== false) {
            return 'WITHDRAWAL';
        }

        // Reversal
        if (strpos($type, 'REVERSAL') !== false ||
            strpos($reason, 'reversal') !== false ||
            strpos($reason, 'reverse') !== false) {
            return 'REVERSAL';
        }

        // Other
        return 'OTHER';
    }

    /**
     * Main Shopee sync function - Updated with official API
     */
    public function syncMonthlyShopeeFeesOfficial($channel, $year = null, $month = null)
    {
        if ($year === null) {
            $year = (int)date('Y');
        }
        if ($month === null) {
            $month = (int)date('m');
        }

        $fromTime = strtotime("$year-$month-01 00:00:00");
        if ($month == 12) {
            $toTime = strtotime(($year + 1) . "-01-01 00:00:00") - 1;
        } else {
            $toTime = strtotime("$year-" . ($month + 1) . "-01 00:00:00") - 1;
        }

        Yii::info("=== Syncing Shopee fees for {$year}-{$month} (Official API) ===", __METHOD__);

        $results = [
            'success' => true,
            'platform' => 'Shopee',
            'api_version' => 'v2 (Official)',
            'period' => [
                'year' => $year,
                'month' => $month,
                'from' => date('Y-m-d H:i:s', $fromTime),
                'to' => date('Y-m-d H:i:s', $toTime),
            ]
        ];

        try {
            // 1. Sync wallet transactions (Official API)
            Yii::info('Step 1: Syncing wallet transactions (Official API)...', __METHOD__);
            $transactionCount = $this->syncShopeeWalletTransactionsOfficial($channel, $fromTime, $toTime);
            $results['transaction_count'] = $transactionCount;
            Yii::info("✓ Synced {$transactionCount} wallet transactions", __METHOD__);

            // 2. Sync order income details
            Yii::info('Step 2: Syncing order income details...', __METHOD__);
            $orderIncomeCount = $this->syncShopeeOrderIncomeV2($channel, $fromTime, $toTime);
            $results['order_income_count'] = $orderIncomeCount;
            Yii::info("✓ Updated {$orderIncomeCount} orders with income details", __METHOD__);

            // 3. Sync settlements/payouts
            Yii::info('Step 3: Syncing settlements...', __METHOD__);
            try {
                $settlementCount = $this->syncShopeeSettlementsFixed($channel, $fromTime, $toTime);
            } catch (\Exception $e) {
                Yii::warning('Settlement API error, using fallback: ' . $e->getMessage(), __METHOD__);
                $settlementCount = $this->syncShopeePayoutFromTransactions($channel, $fromTime, $toTime);
            }
            $results['settlement_count'] = $settlementCount;
            Yii::info("✓ Synced {$settlementCount} settlements", __METHOD__);

            // 4-6. Calculate summaries
            Yii::info('Step 4: Calculating summaries...', __METHOD__);
            $results['transaction_summary'] = $this->calculateFeeSummary($channel, $fromTime, $toTime);
            $results['order_summary'] = $this->calculateOrderFeeSummary($channel, $fromTime, $toTime);
            $results['settlement_summary'] = $this->calculateSettlementSummary($channel, $fromTime, $toTime);

            $results['grand_summary'] = [
                'total_revenue' => $results['order_summary']['total_revenue'],
                'total_buyer_paid' => $results['order_summary']['total_buyer_paid'],
                'total_all_fees' => $results['order_summary']['total_all_fees'],
                'total_actual_income' => $results['order_summary']['total_actual_income'],
                'total_settlements_received' => $results['settlement_summary']['total_net_amount'],
                'fee_percentage' => $results['order_summary']['fee_percentage'],
                'total_orders' => $results['order_summary']['total_orders'],
                'total_settlements' => $results['settlement_summary']['total_settlements'],
            ];

            Yii::info("=== Sync completed successfully ===", __METHOD__);
            Yii::info("Wallet transactions: {$transactionCount}", __METHOD__);
            Yii::info("Orders updated: {$orderIncomeCount}", __METHOD__);
            Yii::info("Settlements: {$settlementCount}", __METHOD__);
            Yii::info("Total fees: " . number_format($results['transaction_summary']['total_fees'], 2), __METHOD__);

        } catch (\Exception $e) {
            $results['success'] = false;
            $results['error'] = $e->getMessage();
            Yii::error("Sync failed: " . $e->getMessage(), __METHOD__);
            Yii::error("Stack trace: " . $e->getTraceAsString(), __METHOD__);
        }

        return $results;
    }

}