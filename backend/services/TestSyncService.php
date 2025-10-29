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
    public function syncTikTokTransactionFees($channel, $fromTime = null, $toTime = null)
    {
        $channel_id = is_object($channel) ? $channel->id : (int)$channel;

        if (is_int($channel)) {
            $channel = OnlineChannel::findOne($channel_id);
            if (!$channel) {
                Yii::error("Channel not found: $channel_id", __METHOD__);
                return 0;
            }
        }

        // Get active token
        $tokenModel = TiktokToken::find()
            ->where(['status' => 'active'])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if (!$tokenModel) {
            Yii::warning('No active TikTok token found for channel: ' . $channel_id, __METHOD__);
            return 0;
        }

        // Check token expiry
        if (strtotime($tokenModel->expires_at) < time()) {
            Yii::error('TikTok Access Token is expired', __METHOD__);
            if (!$this->refreshTikTokToken($tokenModel)) {
                Yii::warning('Failed to refresh TikTok token', __METHOD__);
                return 0;
            }
        }

        $app_key = '6h9n461r774e1';
        $app_secret = '1c45a0c25224293abd7de681049f90de3363389a';
        $shop_id = $tokenModel->shop_id;
        $access_token = $tokenModel->access_token;

        if ($fromTime === null) {
            $fromTime = strtotime('-30 day');
        }
        if ($toTime === null) {
            $toTime = time();
        }

        Yii::info("=== Starting TikTok Fee Sync ===", __METHOD__);
        Yii::info("Period: " . date('Y-m-d H:i:s', $fromTime) . " to " . date('Y-m-d H:i:s', $toTime), __METHOD__);
        Yii::info("Shop ID: $shop_id", __METHOD__);

        // Try multiple methods
        $totalCount = 0;

        // Method 1: Try to get from order details directly
        Yii::info("Method 1: Extracting fees from existing orders...", __METHOD__);
        $totalCount = $this->syncTikTokOrderIncome($channel, $fromTime, $toTime);

        Yii::info("Total synced: {$totalCount} TikTok fee records", __METHOD__);
        return $totalCount;
    }

    /**
     * Improved: Sync TikTok order income with better error handling
     */
    private function syncTikTokOrderIncome($channel, $fromTime = null, $toTime = null)
    {
        $channel_id = is_object($channel) ? $channel->id : (int)$channel;

        if ($fromTime === null) {
            $fromTime = strtotime('-30 day');
        }
        if ($toTime === null) {
            $toTime = time();
        }

        // Get orders in the period
        $orders = Order::find()
            ->where(['channel_id' => $channel_id])
            ->andWhere(['>=', 'order_date', date('Y-m-d H:i:s', $fromTime)])
            ->andWhere(['<=', 'order_date', date('Y-m-d H:i:s', $toTime)])
            ->all();

        if (empty($orders)) {
            Yii::info('No orders found in the period', __METHOD__);
            return 0;
        }

        Yii::info("Found " . count($orders) . " orders to process", __METHOD__);

        $tokenModel = TiktokToken::find()
            ->where(['status' => 'active'])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if (!$tokenModel) {
            Yii::warning('No active TikTok token found', __METHOD__);
            return 0;
        }

        $app_key = '6h9n461r774e1';
        $app_secret = '1c45a0c25224293abd7de681049f90de3363389a';
        $shop_id = $tokenModel->shop_id;
        $access_token = $tokenModel->access_token;

        $count = 0;
        $successCount = 0;
        $failCount = 0;

        foreach ($orders as $order) {
            $count++;

            Yii::info("Processing order {$count}/" . count($orders) . ": {$order->order_id}", __METHOD__);

            // Check if already has fee transactions
            $hasTransaction = ShopeeTransaction::find()
                ->where(['channel_id' => $channel_id])
                ->andWhere(['order_sn' => $order->order_id])
                ->exists();

            if ($hasTransaction) {
                Yii::debug("Order {$order->order_id} already has transactions, skipping", __METHOD__);
                continue;
            }

            try {
                // Try to get order details from TikTok API
                $orderDetails = $this->getTikTokOrderDetailImproved($order->order_id, $shop_id, $app_key, $app_secret, $access_token);

                if ($orderDetails) {
                    if ($this->processTikTokOrderFeesImproved($channel_id, $order, $orderDetails, $shop_id)) {
                        $successCount++;
                        Yii::info("✓ Successfully processed order {$order->order_id}", __METHOD__);
                    } else {
                        $failCount++;
                        Yii::warning("✗ Failed to process fees for order {$order->order_id}", __METHOD__);
                    }
                } else {
                    // Fallback: Use estimated fees
                    Yii::warning("Cannot get order details from API for {$order->order_id}, using estimated fees", __METHOD__);
                    if ($this->createEstimatedFeesForOrder($channel_id, $order, $shop_id)) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                }

                usleep(300000); // 0.3 seconds delay

            } catch (\Exception $e) {
                $failCount++;
                Yii::error("Error processing order {$order->order_id}: " . $e->getMessage(), __METHOD__);
                continue;
            }

            // Progress report every 10 orders
            if ($count % 10 == 0) {
                Yii::info("Progress: {$count}/" . count($orders) . " orders processed (Success: {$successCount}, Failed: {$failCount})", __METHOD__);
            }
        }

        Yii::info("=== Sync Complete ===", __METHOD__);
        Yii::info("Total orders: " . count($orders), __METHOD__);
        Yii::info("Successfully processed: {$successCount}", __METHOD__);
        Yii::info("Failed: {$failCount}", __METHOD__);

        return $successCount;
    }

    /**
     * Improved: Get TikTok order detail with better error handling
     */
    private function getTikTokOrderDetailImproved($order_id, $shop_id, $app_key, $app_secret, $access_token)
    {
        Yii::info("Fetching order details for: $order_id", __METHOD__);

        $timestamp = time();
        $path = "/order/202309/orders/{$order_id}";

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

        try {
            $response = $this->httpClient->get($url, [
                'query' => $queryParams,
                'timeout' => 30,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $rawBody = (string)$response->getBody();

            Yii::debug("TikTok API Response Status: $statusCode", __METHOD__);
            Yii::debug("Response Body: " . substr($rawBody, 0, 500), __METHOD__);

            if ($statusCode !== 200) {
                Yii::error("HTTP Error $statusCode for order $order_id", __METHOD__);
                return null;
            }

            $data = Json::decode($rawBody);

            if (!isset($data['code'])) {
                Yii::error("Invalid response format for order $order_id", __METHOD__);
                return null;
            }

            if ($data['code'] != 0) {
                Yii::warning("TikTok API Error for order $order_id: Code={$data['code']}, Message=" . ($data['message'] ?? 'Unknown'), __METHOD__);
                return null;
            }

            if (empty($data['data'])) {
                Yii::warning("Empty data returned for order $order_id", __METHOD__);
                return null;
            }

            Yii::info("✓ Successfully got order details for $order_id", __METHOD__);
            return $data['data'];

        } catch (\Exception $e) {
            Yii::error("Exception getting order details for $order_id: " . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /**
     * Improved: Process TikTok order fees with detailed logging
     */
    private function processTikTokOrderFeesImproved($channel_id, $order, $orderDetails, $shop_id)
    {
        try {
            $payment = $orderDetails['payment'] ?? [];

            if (empty($payment)) {
                Yii::warning("No payment data found for order {$order->order_id}", __METHOD__);
                return false;
            }

            Yii::debug("Payment data for order {$order->order_id}: " . Json::encode($payment), __METHOD__);

            // Extract all fee fields
            $total_fee = (float)($payment['total_fee'] ?? 0);
            $commission_fee = (float)($payment['commission_fee'] ?? 0);
            $transaction_fee = (float)($payment['transaction_fee'] ?? 0);
            $affiliate_commission = (float)($payment['affiliate_commission'] ?? 0);
            $affiliate_partner_commission = (float)($payment['affiliate_partner_commission'] ?? 0);
            $retail_delivery_fee = (float)($payment['retail_delivery_fee'] ?? 0);
            $shipping_insurance_fee = (float)($payment['shipping_insurance_fee'] ?? 0);
            $small_order_cost = (float)($payment['small_order_cost'] ?? 0);
            $seller_income = (float)($payment['seller_income'] ?? 0);

            // Calculate other payment details
            $subtotal = (float)($payment['subtotal'] ?? 0);
            $platform_discount = (float)($payment['platform_discount'] ?? 0);
            $seller_discount = (float)($payment['seller_discount'] ?? 0);
            $shipping_fee = (float)($payment['shipping_fee'] ?? 0);
            $shipping_fee_seller_discount = (float)($payment['shipping_fee_seller_discount'] ?? 0);
            $shipping_fee_platform_discount = (float)($payment['shipping_fee_platform_discount'] ?? 0);
            $taxes = (float)($payment['taxes'] ?? 0);

            Yii::info("Order {$order->order_id} fees:", __METHOD__);
            Yii::info("  - Commission: $commission_fee", __METHOD__);
            Yii::info("  - Transaction: $transaction_fee", __METHOD__);
            Yii::info("  - Total Fee: $total_fee", __METHOD__);
            Yii::info("  - Seller Income: $seller_income", __METHOD__);

            // Prepare fee array
            $fees = [];

            if ($commission_fee > 0) {
                $fees['COMMISSION_FEE'] = $commission_fee;
            }

            if ($transaction_fee > 0) {
                $fees['TRANSACTION_FEE'] = $transaction_fee;
            }

            $totalAffiliateFee = $affiliate_commission + $affiliate_partner_commission;
            if ($totalAffiliateFee > 0) {
                $fees['AFFILIATE_FEE'] = $totalAffiliateFee;
            }

            if ($shipping_fee_seller_discount > 0) {
                $fees['SHIPPING_FEE'] = $shipping_fee_seller_discount;
            }

            $totalServiceFee = $retail_delivery_fee + $shipping_insurance_fee + $small_order_cost;
            if ($totalServiceFee > 0) {
                $fees['SERVICE_FEE'] = $totalServiceFee;
            }

            // If no breakdown but has total_fee, use it
            if (empty($fees) && $total_fee > 0) {
                $fees['COMMISSION_FEE'] = $total_fee;
            }

            if (empty($fees)) {
                Yii::warning("No fees found for order {$order->order_id}", __METHOD__);
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
                    Yii::debug("Transaction already exists: $transaction_id", __METHOD__);
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
                    Yii::info("✓ Created transaction: $transaction_id, Amount: -$amount", __METHOD__);
                } else {
                    Yii::error("✗ Failed to save transaction: " . Json::encode($feeTransaction->errors), __METHOD__);
                }
            }

            // Update order record
            $order->commission_fee = $fees['COMMISSION_FEE'] ?? 0;
            $order->transaction_fee = $fees['TRANSACTION_FEE'] ?? 0;
            $order->payment_fee = $fees['AFFILIATE_FEE'] ?? 0;
            $order->service_fee = $fees['SERVICE_FEE'] ?? 0;

            if ($seller_income > 0) {
                $order->actual_income = $seller_income;
            } else {
                $total_fees = array_sum($fees);
                $order->actual_income = $order->total_amount - $total_fees;
            }

            $order->buyer_paid_amount = $subtotal + $shipping_fee - $platform_discount - $seller_discount + $taxes;
            $order->seller_discount = $seller_discount + $shipping_fee_seller_discount;
            $order->shopee_discount = $platform_discount + $shipping_fee_platform_discount;
            $order->updated_at = date('Y-m-d H:i:s');

            if ($order->save(false)) {
                Yii::info("✓ Updated order {$order->order_id}", __METHOD__);
            } else {
                Yii::error("✗ Failed to update order: " . Json::encode($order->errors), __METHOD__);
            }

            return $transactionCreated;

        } catch (\Exception $e) {
            Yii::error("Exception processing fees for order {$order->order_id}: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Create estimated fees for order when API fails
     */
    private function createEstimatedFeesForOrder($channel_id, $order, $shop_id)
    {
        try {
            // TikTok typical fee structure (estimates)
            $commission_rate = 0.05; // 5%
            $transaction_fee_rate = 0.02; // 2%

            $commission_fee = $order->total_amount * $commission_rate;
            $transaction_fee = $order->total_amount * $transaction_fee_rate;

            $fees = [
                'COMMISSION_FEE' => $commission_fee,
                'TRANSACTION_FEE' => $transaction_fee,
            ];

            Yii::info("Creating estimated fees for order {$order->order_id}", __METHOD__);

            $transactionCreated = false;

            foreach ($fees as $category => $amount) {
                if ($amount <= 0) continue;

                $transaction_id = 'TT_' . $order->order_id . '_' . $category . '_EST';

                $existing = ShopeeTransaction::findOne(['transaction_id' => $transaction_id]);
                if ($existing) continue;

                $feeTransaction = new ShopeeTransaction();
                $feeTransaction->transaction_id = $transaction_id;
                $feeTransaction->channel_id = $channel_id;
                $feeTransaction->shop_id = (string)$shop_id;
                $feeTransaction->transaction_type = 'ORDER_FEE_ESTIMATED';
                $feeTransaction->status = 'ESTIMATED';
                $feeTransaction->reason = $category . ' (Estimated) for order ' . $order->order_id;
                $feeTransaction->amount = -$amount;
                $feeTransaction->current_balance = 0;
                $feeTransaction->order_sn = $order->order_id;
                $feeTransaction->transaction_date = $order->order_date;
                $feeTransaction->fee_category = $category;
                $feeTransaction->created_at = date('Y-m-d H:i:s');
                $feeTransaction->updated_at = date('Y-m-d H:i:s');

                if ($feeTransaction->save()) {
                    $transactionCreated = true;
                }
            }

            // Update order
            $order->commission_fee = $commission_fee;
            $order->transaction_fee = $transaction_fee;
            $order->actual_income = $order->total_amount - $commission_fee - $transaction_fee;
            $order->updated_at = date('Y-m-d H:i:s');
            $order->save(false);

            return $transactionCreated;

        } catch (\Exception $e) {
            Yii::error("Error creating estimated fees: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }
}
