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
    private function getTikTokOrderDetailFixed($order_id, $shop_id, $app_key, $app_secret, $access_token)
    {
        Yii::info("Fetching order details for: $order_id", __METHOD__);

        // TikTok Order ID format handling
        // TikTok มักใช้ order_id แบบ numeric string หรือมี underscore
        // ลองทั้ง 2 format:
        // 1. ใช้ order_id เต็ม
        // 2. ใช้ส่วนแรกก่อน underscore (ถ้ามี)
        // 3. ใช้ส่วนหลัง underscore (ถ้ามี)

        $orderIdVariants = [$order_id];

        if (strpos($order_id, '_') !== false) {
            $parts = explode('_', $order_id);
            $orderIdVariants[] = $parts[0]; // ส่วนแรก
            $orderIdVariants[] = $parts[1]; // ส่วนหลัง
            if (count($parts) > 2) {
                $orderIdVariants[] = end($parts); // ส่วนสุดท้าย
            }
        }

        // Remove duplicates
        $orderIdVariants = array_unique($orderIdVariants);

        Yii::info("Will try order ID variants: " . implode(', ', $orderIdVariants), __METHOD__);

        // Try multiple API versions and order ID variants
        $apiVersions = [
            '/order/202309/orders',      // Latest version
            '/order/202212/orders',      // Previous version
            '/order/202111/orders',      // Older version
        ];

        foreach ($orderIdVariants as $tryOrderId) {
            foreach ($apiVersions as $basePath) {
                try {
                    $timestamp = time();
                    $path = $basePath . '/' . $tryOrderId;

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

                    Yii::debug("Trying: $path with order_id: $tryOrderId", __METHOD__);

                    $response = $this->httpClient->get($url, [
                        'query' => $queryParams,
                        'timeout' => 30,
                        'http_errors' => false,
                    ]);

                    $statusCode = $response->getStatusCode();
                    $rawBody = (string)$response->getBody();

                    if ($statusCode !== 200) {
                        Yii::debug("HTTP $statusCode for $tryOrderId with $basePath", __METHOD__);
                        continue;
                    }

                    $data = Json::decode($rawBody);

                    if (!isset($data['code'])) {
                        Yii::debug("Invalid response format", __METHOD__);
                        continue;
                    }

                    // Success
                    if ($data['code'] == 0 && !empty($data['data'])) {
                        Yii::info("✓ Successfully got order details using: $tryOrderId with $basePath", __METHOD__);

                        // Log payment data if available
                        if (!empty($data['data']['payment'])) {
                            Yii::info("Payment data found: " . Json::encode($data['data']['payment']), __METHOD__);
                        } else {
                            Yii::warning("No payment data in response", __METHOD__);
                        }

                        return $data['data'];
                    }

                    // Log specific errors
                    if ($data['code'] == 36009009) {
                        Yii::debug("Path not found (36009009), trying next variant", __METHOD__);
                        continue;
                    }

                    Yii::debug("API Error: Code={$data['code']}, Message=" . ($data['message'] ?? 'Unknown'), __METHOD__);

                } catch (\Exception $e) {
                    Yii::debug("Exception with $basePath and $tryOrderId: " . $e->getMessage(), __METHOD__);
                    continue;
                }
            }
        }

        Yii::error("All API attempts failed for order: $order_id", __METHOD__);
        Yii::error("Tried order ID variants: " . implode(', ', $orderIdVariants), __METHOD__);

        return null;
    }

    /**
     * Alternative: ดึงข้อมูล Order จาก TikTok List Orders API
     * ใช้เมื่อ Order Detail API ไม่ทำงาน
     */
    private function getTikTokOrderFromList($order_id, $shop_id, $app_key, $app_secret, $access_token)
    {
        try {
            Yii::info("Trying to get order from List Orders API: $order_id", __METHOD__);

            $timestamp = time();
            $path = "/order/202309/orders/search";

            $params = [
                'app_key' => $app_key,
                'timestamp' => $timestamp,
                'shop_id' => $shop_id,
                'access_token' => $access_token,
            ];

            // Try different order ID formats in search
            $searchOrderIds = [$order_id];
            if (strpos($order_id, '_') !== false) {
                $parts = explode('_', $order_id);
                $searchOrderIds = array_merge($searchOrderIds, $parts);
            }

            foreach ($searchOrderIds as $searchId) {
                $body = [
                    'order_id_list' => [$searchId],
                    'page_size' => 1,
                ];

                ksort($params);

                // Generate signature
                $sign_string = $path;
                foreach ($params as $key => $value) {
                    if ($key != 'access_token' && $key != 'sign') {
                        $sign_string .= $key . $value;
                    }
                }
                $sign_string .= json_encode($body);
                $sign = hash_hmac('sha256', $sign_string, $app_secret);

                $url = "https://open-api.tiktokglobalshop.com" . $path;
                $queryParams = array_merge($params, ['sign' => $sign]);

                Yii::debug("Searching for order: $searchId", __METHOD__);

                $response = $this->httpClient->post($url, [
                    'query' => $queryParams,
                    'json' => $body,
                    'timeout' => 30,
                    'http_errors' => false,
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode !== 200) {
                    continue;
                }

                $rawBody = (string)$response->getBody();
                $data = Json::decode($rawBody);

                if (isset($data['code']) && $data['code'] == 0 && !empty($data['data']['orders'])) {
                    $orderData = $data['data']['orders'][0];
                    Yii::info("✓ Found order using search with ID: $searchId", __METHOD__);
                    return $orderData;
                }
            }

            Yii::warning("Could not find order in List Orders API", __METHOD__);
            return null;

        } catch (\Exception $e) {
            Yii::error("Error getting order from list: " . $e->getMessage(), __METHOD__);
            return null;
        }
    }

    /**
     * Enhanced: Process TikTok order fees with better fallback
     */
    private function processTikTokOrderFeesEnhanced($channel_id, $order, $orderDetails, $shop_id)
    {
        try {
            // Check if orderDetails has payment data
            $payment = $orderDetails['payment'] ?? [];

            if (empty($payment)) {
                Yii::warning("No payment data in order details for {$order->order_id}", __METHOD__);

                // Try to extract basic info from order details
                if (!empty($orderDetails['order_amount'])) {
                    $orderAmount = (float)($orderDetails['order_amount'] ?? 0);
                    $payment = [
                        'subtotal' => $orderAmount,
                        'total_fee' => 0, // Will estimate
                    ];
                    Yii::info("Using basic order amount: $orderAmount", __METHOD__);
                } else {
                    return false;
                }
            }

            Yii::debug("Payment data: " . Json::encode($payment), __METHOD__);

            // Extract fee information
            $total_fee = (float)($payment['total_fee'] ?? 0);
            $commission_fee = (float)($payment['commission_fee'] ?? 0);
            $transaction_fee = (float)($payment['transaction_fee'] ?? 0);
            $affiliate_commission = (float)($payment['affiliate_commission'] ?? 0);
            $affiliate_partner_commission = (float)($payment['affiliate_partner_commission'] ?? 0);
            $retail_delivery_fee = (float)($payment['retail_delivery_fee'] ?? 0);
            $shipping_insurance_fee = (float)($payment['shipping_insurance_fee'] ?? 0);
            $small_order_cost = (float)($payment['small_order_cost'] ?? 0);
            $seller_income = (float)($payment['seller_income'] ?? 0);

            // Other payment details
            $subtotal = (float)($payment['subtotal'] ?? 0);
            $platform_discount = (float)($payment['platform_discount'] ?? 0);
            $seller_discount = (float)($payment['seller_discount'] ?? 0);
            $shipping_fee = (float)($payment['shipping_fee'] ?? 0);
            $shipping_fee_seller_discount = (float)($payment['shipping_fee_seller_discount'] ?? 0);
            $shipping_fee_platform_discount = (float)($payment['shipping_fee_platform_discount'] ?? 0);
            $taxes = (float)($payment['taxes'] ?? 0);

            Yii::info("Order {$order->order_id} fees breakdown:", __METHOD__);
            Yii::info("  Subtotal: $subtotal", __METHOD__);
            Yii::info("  Commission: $commission_fee", __METHOD__);
            Yii::info("  Transaction: $transaction_fee", __METHOD__);
            Yii::info("  Total Fee: $total_fee", __METHOD__);
            Yii::info("  Seller Income: $seller_income", __METHOD__);

            // Build fees array
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

            // If no breakdown but has total_fee
            if (empty($fees) && $total_fee > 0) {
                $fees['COMMISSION_FEE'] = $total_fee;
                Yii::info("Using total_fee as commission_fee: $total_fee", __METHOD__);
            }

            // If still no fees and have subtotal, estimate
            if (empty($fees) && $subtotal > 0) {
                $estimatedCommission = $subtotal * 0.05; // 5%
                $estimatedTransaction = $subtotal * 0.02; // 2%
                $fees['COMMISSION_FEE'] = $estimatedCommission;
                $fees['TRANSACTION_FEE'] = $estimatedTransaction;
                Yii::warning("No fee data, using estimates for order {$order->order_id}", __METHOD__);
            }

            if (empty($fees)) {
                Yii::warning("No fees could be determined for order {$order->order_id}", __METHOD__);
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
                $feeTransaction->amount = -$amount;
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

            if ($seller_income > 0) {
                $order->actual_income = $seller_income;
            } else {
                $total_fees_amount = array_sum($fees);
                $order->actual_income = $order->total_amount - $total_fees_amount;
            }

            if ($subtotal > 0) {
                $order->buyer_paid_amount = $subtotal + $shipping_fee - $platform_discount - $seller_discount + $taxes;
                $order->seller_discount = $seller_discount + $shipping_fee_seller_discount;
                $order->shopee_discount = $platform_discount + $shipping_fee_platform_discount;
            }

            $order->updated_at = date('Y-m-d H:i:s');

            if ($order->save(false)) {
                Yii::info("✓ Updated order {$order->order_id}", __METHOD__);
                Yii::info("  Commission: {$order->commission_fee}", __METHOD__);
                Yii::info("  Transaction: {$order->transaction_fee}", __METHOD__);
                Yii::info("  Actual Income: {$order->actual_income}", __METHOD__);
            } else {
                Yii::error("✗ Failed to update order", __METHOD__);
            }

            return $transactionCreated;

        } catch (\Exception $e) {
            Yii::error("Exception processing fees for {$order->order_id}: " . $e->getMessage(), __METHOD__);
            Yii::error("Stack trace: " . $e->getTraceAsString(), __METHOD__);
            return false;
        }
    }

    /**
     * Main sync function with enhanced order detail retrieval
     */
    public function syncTikTokOrderIncomeEnhanced($channel, $fromTime = null, $toTime = null)
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

        $count = 0;
        $successCount = 0;

        Yii::info("Processing " . count($orders) . " orders", __METHOD__);

        foreach ($orders as $order) {
            $count++;

            // Skip if already has transactions
            $hasTransaction = ShopeeTransaction::find()
                ->where(['channel_id' => $channel_id])
                ->andWhere(['order_sn' => $order->order_id])
                ->exists();

            if ($hasTransaction) {
                continue;
            }

            try {
                // Method 1: Try detail API
                $orderDetails = $this->getTikTokOrderDetailFixed($order->order_id, $shop_id, $app_key, $app_secret, $access_token);

                // Method 2: Try list API if detail fails
                if (!$orderDetails) {
                    Yii::info("Trying List Orders API for {$order->order_id}", __METHOD__);
                    $orderDetails = $this->getTikTokOrderFromList($order->order_id, $shop_id, $app_key, $app_secret, $access_token);
                }

                if ($orderDetails) {
                    if ($this->processTikTokOrderFeesEnhanced($channel_id, $order, $orderDetails, $shop_id)) {
                        $successCount++;
                    }
                } else {
                    // Fallback: Estimated fees
                    Yii::warning("Using estimated fees for {$order->order_id}", __METHOD__);
//                    if ($this->createEstimatedFeesForOrder($channel_id, $order, $shop_id)) {
//                        $successCount++;
//                    }
                }

                usleep(300000);

            } catch (\Exception $e) {
                Yii::error("Error: " . $e->getMessage(), __METHOD__);
                continue;
            }

            if ($count % 10 == 0) {
                Yii::info("Progress: {$count}/" . count($orders) . " (Success: {$successCount})", __METHOD__);
            }
        }

        Yii::info("Complete: {$successCount}/" . count($orders) . " orders processed", __METHOD__);
        return $successCount;
    }
}
