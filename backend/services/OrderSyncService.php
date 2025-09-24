<?php

namespace backend\services;

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

class OrderSyncService
{
    private $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }

    /**
     * Sync orders from online channel
     * @param int $channelId
     * @return array
     * @throws Exception
     */
    public function syncOrders($channelId = null)
    {
        $channels = [];

        if ($channelId) {
            $channel = OnlineChannel::findOne($channelId);
            if (!$channel) {
                throw new Exception('Channel not found');
            }
            $channels[] = $channel;
        } else {
            $channels = OnlineChannel::find()->where(['status' => OnlineChannel::STATUS_ACTIVE])->all();
        }

        $totalSynced = 0;
        $errors = [];

        foreach ($channels as $channel) {
            try {
                switch ($channel->name) {
                    case 'Tiktok':
                        $totalSynced += $this->syncTikTokOrders($channel);
                        break;
                    case 'Shopee':
                        $totalSynced += $this->syncShopeeOrders($channel);
                        break;
                }
            } catch (\Exception $e) {
                $errors[] = $channel->name . ': ' . $e->getMessage();
                Yii::error('Order sync error for ' . $channel->name . ': ' . $e->getMessage(), __METHOD__);
            }
        }

        return [
            'count' => $totalSynced,
            'errors' => $errors
        ];
    }

    /**
     * Sync orders from Shopee using real API
     * @param OnlineChannel $channel
     * @return int
     */
    private function syncShopeeOrders($channel)
    {
        $tokenModel = ShopeeToken::find()
            ->where(['status' => 'active'])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if (!$tokenModel) {
            Yii::warning('No active Shopee token found for channel: ' . $channel->id, __METHOD__);
            return $this->shopeeSampleOrders($channel);
        }

        // ตรวจสอบ token หมดอายุ
        if (strtotime($tokenModel->expires_at) < time()) {
            Yii::error('Access Token is expired');
            if (!$this->refreshShopeeToken($tokenModel)) {
                Yii::warning('Failed to refresh Shopee token for channel: ' . $channel->id, __METHOD__);
                return $this->shopeeSampleOrders($channel);
            }
        }

        $partner_id = 2012399;
        $partner_key = 'shpk72476151525864414e4b6e475449626679624f695a696162696570417043';
        $shop_id = $tokenModel->shop_id;
        $access_token = $tokenModel->access_token;

        $count = 0;
        $page_size = 100;
        $cursor = '';

        try {
            do {
                $timestamp = time();
                $path = "/api/v2/order/get_order_list";
                $base_string = $partner_id . $path . $timestamp . $access_token . $shop_id;
                $sign = hash_hmac('sha256', $base_string, $partner_key);

                $params = [
                    'partner_id' => $partner_id,
                    'shop_id' => (int)$shop_id,
                    'sign' => $sign,
                    'timestamp' => $timestamp,
                    'access_token' => $access_token,
                    'time_range_field' => 'create_time',
                    'time_from' => strtotime('-10 day'),
                    'time_to' => time(),
                    'page_size' => $page_size,
                    'response_optional_fields' => 'order_status',
                    'order_status' => 'SHIPPED', // optional
                ];

                if (!empty($cursor)) {
                    $params['cursor'] = $cursor;
                }

                $response = $this->httpClient->get('https://partner.shopeemobile.com' . $path, [
                    'query' => $params,
                    'timeout' => 30
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode !== 200) {
                    Yii::error("HTTP Shopee Sync Error: {$statusCode}", __METHOD__);
                    break;
                }

                $rawBody = (string)$response->getBody();
                Yii::debug("Shopee Raw Body: " . $rawBody, __METHOD__);

                $data = Json::decode($rawBody);

                // เช็ค API error
                if (!empty($data['error'])) {
                    Yii::error("Shopee API Error Sync: {$data['error']} - " . ($data['message'] ?? 'Unknown error'), __METHOD__);

                    if (in_array($data['error'], ['error_auth', 'error_permission'])) {
                        if ($this->refreshShopeeToken($tokenModel)) {
                            continue; // ลองใหม่ด้วย token ใหม่
                        }
                    }
                    break;
                }

                // มี response แต่ไม่มี order_list
                if (empty($data['response']['order_list'])) {
                    Yii::info('Shopee response valid but no order_list found', __METHOD__);
                    break;
                }

                $orderList = $data['response']['order_list'];
                foreach ($orderList as $orderData) {
                    $processResult = $this->processShopeeOrder(
                        $channel,
                        $orderData,
                        $partner_id,
                        $partner_key,
                        $access_token,
                        $shop_id
                    );
                    if ($processResult !== false) {
                        $count += $processResult;
                    }
                }

                $cursor = $data['response']['next_cursor'] ?? '';

                if (!empty($cursor)) {
                    usleep(200000); // 0.2 วินาที กัน rate limit
                }

            } while (!empty($cursor));

            Yii::info("Synced {$count} Shopee orders for channel: " . $channel->id, __METHOD__);

        } catch (\Exception $e) {
            Yii::error('Shopee API error on Catch: ' . $e->getMessage(), __METHOD__);
            throw $e;
        }

        return $count;
    }


    /**
     * Process individual Shopee order
     * @param OnlineChannel $channel
     * @param array $orderData
     * @param string $partner_id
     * @param string $partner_key
     * @param string $access_token
     * @param string $shop_id
     * @return int
     */
    private function processShopeeOrder($channel, $orderData, $partner_id, $partner_key, $access_token, $shop_id)
    {
        $order_sn = $orderData['order_sn'];
        $count = 0;

        try {
            // ดึงรายละเอียด order
            $timestamp = time();
            $path = "/api/v2/order/get_order_detail";
            $base_string = $partner_id . $path . $timestamp . $access_token . $shop_id;
            $sign = hash_hmac('sha256', $base_string, $partner_key);

            $response = $this->httpClient->get('https://partner.shopeemobile.com' . $path, [
                'query' => [
                    'partner_id' => (int)$partner_id, // ✅ cast เป็น int
                    'timestamp' => $timestamp,
                    'access_token' => $access_token,
                    'shop_id' => (int)$shop_id, // ✅ cast เป็น int
                    'sign' => $sign,
                    'order_sn_list' => $order_sn,
                    'response_optional_fields' => 'item_list,buyer_paid_amount,buyer_total_amount,total_amount,payment_info,escrow_amount,commission_fee,service_fee',
                ],
                'timeout' => 30 // ✅ เพิ่ม timeout
            ]);

            // ✅ เช็ค HTTP status
            if ($response->getStatusCode() !== 200) {
                Yii::error("HTTP Error {$response->getStatusCode()} for order: $order_sn", __METHOD__);
                return 0;
            }

            $body = $response->getBody()->getContents();
            $data = Json::decode($body);

            // ✅ เช็ค API errors
            if (isset($data['error'])) {
                if(!empty($data['error'])){
                    Yii::error("Shopee API Error for order $order_sn: {$data['error']} - " . ($data['message'] ?? 'Unknown error'), __METHOD__);
                    return 0;
                }

            }

            // ✅ ปรับการเช็ค response structure
            if (!isset($data['response']['order_list']) || empty($data['response']['order_list'])) {
                Yii::warning("No order detail found for order: $order_sn", __METHOD__);
                return 0;
            }

            $orderDetail = $data['response']['order_list'][0];

            // ✅ เช็ค item_list
            if (!isset($orderDetail['item_list']) || !is_array($orderDetail['item_list'])) {
                Yii::warning("No items found for order: $order_sn", __METHOD__);
                return 0;
            }

            // สร้าง Order records สำหรับแต่ละ item
            foreach ($orderDetail['item_list'] as $item) {
                // ✅ เช็ค required fields
                if (empty($item['item_id']) || empty($item['item_name'])) {
                    Yii::warning("Missing required item data for order: $order_sn", __METHOD__);
                    continue;
                }

                $order_status = strtoupper($orderDetail['order_status'] ?? 'UNKNOWN');
                if (!in_array($order_status, ['SHIPPED', 'COMPLETED'])) {
                    Yii::info("Skip order $order_sn with status $order_status", __METHOD__);
                    continue; // ข้ามถ้าไม่ใช่ SHIPPED หรือ COMPLETED
                }

                $unique_order_id = $order_sn . '_' . $item['item_id'];

                $existingOrder = Order::findOne(['order_id' => $unique_order_id]);
                if ($existingOrder) {
                    continue; // ข้าม order ที่มีอยู่แล้ว
                }

                $order = new Order();
                $order->order_id = $unique_order_id;
                $order->channel_id = $channel->id;
                $order->shop_id = $shop_id;
                $order->order_sn = $order_sn;

                // ✅ ปรับการดึง SKU ให้ปลอดภัยขึ้น
                $order->sku = $item['model_sku'] ?? $item['item_sku'] ?? 'UNKNOWN_SKU';
                $order->product_name = $item['item_name'];

                $this->checkSaveNewProduct($order->sku,$order->product_name); // check has already product

                // ✅ เช็คและปรับค่า quantity
                $quantity = $item['model_quantity_purchased']
                    ?? $item['quantity_purchased']
                    ?? 0;
                if ($quantity <= 0) {
                    Yii::warning("Invalid quantity for item {$item['item_id']} in order: $order_sn", __METHOD__);
                    continue;
                }
                $order->quantity = $quantity;

                // ✅ ปรับการคำนวณราคาให้ปลอดภัย
               // $price_micro = $item['model_discounted_price'] ?? $item['model_original_price'] ?? 0;
                // ✅ ราคาจริง Shopee ส่งมาเป็น float ไม่ใช่ micro units
                $price_value = $item['model_discounted_price']
                    ?? $item['discounted_price']
                    ?? $item['model_original_price']
                    ?? $item['original_price']
                    ?? 0;
               // $order->price = $price_micro > 0 ? $price_micro / 100000 : 0; // Shopee ส่งมาเป็น micro units
                $order->price =  (float) $price_value;
                $order->total_amount = $order->quantity * $order->price;

                // ✅ ปรับการจัดการวันที่
                $create_time = $orderDetail['create_time'] ?? time();
                $order->order_date = date('Y-m-d H:i:s', $create_time);

                // ✅ เพิ่ม order_status กลับมา (ถ้า table มี field นี้)
                $order->order_status = $order_status;
                $order->created_at = date('Y-m-d H:i:s');
                $order->updated_at = date('Y-m-d H:i:s');
                if ($order->save()) {
                    $count++;
                    Yii::info("Saved order: $unique_order_id", __METHOD__);
                } else {
                    Yii::error("Failed to save Shopee order $unique_order_id: " . Json::encode($order->errors), __METHOD__);
                }
            }

        } catch (\Exception $e) {
            Yii::error('Error processing Shopee order ' . $order_sn . ': ' . $e->getMessage(), __METHOD__);
            return 0; // ✅ return 0 แทน false เพื่อให้ consistent
        }

        return $count;
    }

    private function checkSaveNewProduct($sku,$name){
        $model = \backend\models\Product::find()->where(['sku'=>trim($sku),'name'=>trim($name)])->one();
        if(!$model){
            $model_new = new \backend\models\Product();
            $model_new->sku = trim($sku);
            $model_new->name = trim($name);
            $model_new->status = 1;
            $model_new->save(false);
        }
    }

    /**
     * Sync orders from TikTok Shop using real API
     * @param OnlineChannel $channel
     * @return int
     */
    private function generateTikTokSign(string $appSecret, string $path, array $params): string
    {
        ksort($params);
        $signString = '';
        foreach ($params as $k => $v) {
            $signString .= $k . $v;
        }
        $raw = $appSecret . $path . $signString . $appSecret;
        return strtolower(hash('sha256', $raw));
    }

    private function fetchShopCipher(TiktokToken $tokenModel)
    {
        $appKey     = '6h9n461r774e1';
        $appSecret  = '1c45a0c25224293abd7de681049f90de3363389a';
        $timestamp  = time();
        $path       = '/authorization/202309/shops';

        $params = [
            'app_key'   => $appKey,
            'timestamp' => $timestamp,
        ];

        // ✅ สร้าง sign ถูกต้องตาม TikTok
        ksort($params);
        $signString = '';
        foreach ($params as $k => $v) {
            $signString .= $k . $v;
        }
        $sign = strtolower(hash('sha256', $appSecret . $path . $signString . $appSecret));

        $url = 'https://open-api.tiktokglobalshop.com' . $path . '?' . http_build_query(array_merge($params, ['sign' => $sign]));

        Yii::info("Shop API URL: $url", __METHOD__);

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-tts-access-token' => $tokenModel->access_token,
                ],
            ]);

            $result = json_decode($response->getBody(), true);
            Yii::info('Shop API response: ' . json_encode($result), __METHOD__);

            if (isset($result['code']) && $result['code'] !== 0) {
                throw new \Exception("TikTok API error [{$result['code']}] {$result['message']}");
            }

            if (!empty($result['data']['shops'][0]['cipher'])) {
                $shopCipher = $result['data']['shops'][0]['cipher'];
                $shopName   = $result['data']['shops'][0]['name'] ?? '';

                $tokenModel->shop_cipher = $shopCipher;
                $tokenModel->shop_name   = $shopName;
                $tokenModel->save(false);

                Yii::info("✅ Shop cipher updated: {$shopCipher}", __METHOD__);
                return $shopCipher;
            } else {
                Yii::warning("No shop_cipher returned in API response", __METHOD__);
            }

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $body = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : 'No response';
            Yii::error("ClientException: $body", __METHOD__);
        } catch (\Exception $e) {
            Yii::error("Exception: " . $e->getMessage(), __METHOD__);
        }

        return null;
    }



    private function syncTikTokOrders($channel)
    {
        $tokenModel = TiktokToken::find()
            ->where(['status' => 'active'])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if (!$tokenModel) {
            Yii::error('No active TikTok token found', __METHOD__);
            return 0;
        }

        // ✅ Refresh token ถ้าหมดอายุ
        if ($tokenModel->expires_at && strtotime($tokenModel->expires_at) < time()) {
            $this->refreshTikTokToken($tokenModel);
        }

        // ✅ Fetch shop_cipher ถ้ายังไม่มี
        if (empty($tokenModel->shop_cipher)) {
            $this->fetchShopCipher($tokenModel);
        }

        $appKey      = '6h9n461r774e1';
        $appSecret   = '1c45a0c25224293abd7de681049f90de3363389a';
        $accessToken = $tokenModel->access_token;
        $shopCipher  = $tokenModel->shop_cipher;

        $pageSize  = 50;
        $pageToken = '';
        $count     = 0;
        $pageCount = 0;
        $path      = '/order/202309/orders/search';
        $baseUrl   = 'https://open-api.tiktokglobalshop.com';

        try {
            do {
                $pageCount++;
                $timestamp = time();

                $queryParams = [
                    'app_key'     => $appKey,
                    'timestamp'   => $timestamp,
                    'shop_cipher' => $shopCipher,
                    'page_size'   => $pageSize,
                    'sort_field'  => 'create_time',
                    'sort_order'  => 'DESC',
                ];

                if ($pageToken) {
                    $queryParams['page_token'] = $pageToken;
                }

                // ✅ สร้าง sign
                $sign = $this->generateTikTokSign($appSecret, $path, $queryParams);
                $queryParams['sign'] = $sign;

                $url = $baseUrl . $path . '?' . http_build_query($queryParams);

                $body = [
                    'create_time_ge' => strtotime('-7 days'),
                    'create_time_lt' => time(),
                    'update_time_ge' => strtotime('-7 days'),
                    'update_time_lt' => time(),
                ];

                $response = $this->httpClient->post($url, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'x-tts-access-token' => $accessToken,
                    ],
                    'body' => json_encode($body),
                ]);

                $result = json_decode($response->getBody(), true);

                if (isset($result['code']) && $result['code'] !== 0) {
                    throw new \Exception("TikTok API error [{$result['code']}] {$result['message']}");
                }

                $orders = $result['data']['orders'] ?? [];
                foreach ($orders as $order) {
                    $this->processTikTokOrder($channel, $order);
                    $count++;
                }

                $pageToken = $result['data']['next_page_token'] ?? '';
                if ($pageCount >= 5) break;

            } while (!empty($pageToken));

        } catch (\Exception $e) {
            Yii::error("TikTok sync error: " . $e->getMessage(), __METHOD__);
        }

        Yii::info("✅ Total orders synced: {$count}", __METHOD__);
        return $count;
    }





    /**
     * Process individual TikTok order
     * @param OnlineChannel $channel
     * @param array $orderData
     * @return int
     */
    private function processTikTokOrder($channel, $orderData)
    {
        $order_id = $orderData['id'];
        $count = 0;

        try {
            foreach ($orderData['line_items'] as $item) {
                $unique_order_id = $order_id . '_' . $item['id'];

                $existingOrder = Order::findOne(['order_id' => $unique_order_id]);
                if ($existingOrder) {
                    continue;
                }

                $order = new Order();
                $order->order_id = $unique_order_id;
                $order->channel_id = $channel->id;
//                $order->shop_id = $orderData['shop_id'] ?? '';
//                $order->order_sn = $order_id;
                $order->sku = $item['seller_sku'] ?? $item['sku_id'] ?? '';
                $order->product_name = $item['product_name'];
                $order->quantity = $item['quantity'];
                $order->price = $item['sale_price'] / 1000000; // TikTok ส่งมาเป็น micro units
                $order->total_amount = $order->quantity * $order->price;
                $order->order_date = date('Y-m-d H:i:s', $orderData['create_time']);
                //  $order->order_status = $orderData['status'];

                if ($order->save()) {
                    $count++;
                } else {
                    Yii::error('Failed to save TikTok order: ' . Json::encode($order->errors), __METHOD__);
                }
            }

        } catch (\Exception $e) {
            Yii::error('Error processing TikTok order ' . $order_id . ': ' . $e->getMessage(), __METHOD__);
        }

        return $count;
    }

    /**
     * Refresh Shopee access token
     * @param ShopeeToken $tokenModel
     * @return bool
     */
    private function refreshShopeeToken($tokenModel)
    {
        try {
            $partner_id = 2012399; // ใส่ partner_id จริง
            $partner_key = 'shpk72476151525864414e4b6e475449626679624f695a696162696570417043'; // ใส่ partner_key จริง
            $refresh_token = $tokenModel->refresh_token;
            $shop_id = $tokenModel->shop_id;

            $timestamp = time();
            $path = "/api/v2/auth/access_token/get";

            // ✅ แก้ base_string ให้ถูกต้อง (ไม่รวม shop_id และ refresh_token)
            $base_string = $partner_id . $path . $timestamp;
            $sign = hash_hmac('sha256', $base_string, $partner_key);

            // ✅ แยก partner_id และ timestamp ไปเป็น query parameters
            $queryParams = [
                'partner_id' => $partner_id,
                'timestamp' => $timestamp,
                'sign' => $sign,
            ];

            $jsonPayload = [
                'shop_id' => (int)$shop_id,
                'partner_id' => (int)$partner_id,
                'refresh_token' => $refresh_token,
            ];

            // ✅ เปลี่ยนจาก form_params เป็น json
            $response = $this->httpClient->post('https://partner.shopeemobile.com' . $path, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'query' => $queryParams,
                'json' => $jsonPayload,
                'timeout' => 30
            ]);

            Yii::info('Http token refresh is: ' . $response->getStatusCode());

            // ✅ เช็ค HTTP status code
            if ($response->getStatusCode() !== 200) {
                Yii::error('HTTP Error: ' . $response->getStatusCode(), __METHOD__);
                return false;
            }

            $body = $response->getBody()->getContents();
            $data = Json::decode($body);

            // ✅ เพิ่มการเช็ค error response
            if (isset($data['error'])) {
                if (!empty($data['error'])) {
                    Yii::error("Shopee API Error: {$data['error']} - " . ($data['message'] ?? 'Unknown error'), __METHOD__);
                    return false;
                }

            }

            if (isset($data['access_token'])) {
                $expiresAt = date('Y-m-d H:i:s', time() + (int)($data['expire_in'] ?? 14400));

                $tokenModel->access_token = $data['access_token'];
                $tokenModel->refresh_token = $data['refresh_token'];
                $tokenModel->expires_at = $expiresAt;
                $tokenModel->updated_at = date('Y-m-d H:i:s');

                if ($tokenModel->save()) {
                    Yii::info("Token refreshed successfully for shop_id: $shop_id", __METHOD__);
                    return true;
                } else {
                    Yii::error('Failed to save token model: ' . Json::encode($tokenModel->errors), __METHOD__);
                    return false;
                }
            } else {
                Yii::error('No access_token in response: ' . $body, __METHOD__);
                return false;
            }

        } catch (\Exception $e) {
            Yii::error('Failed to refresh Shopee token: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Refresh TikTok access token
     * @param TiktokToken $tokenModel
     * @return bool
     */
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
     * Sample TikTok orders for demo
     */
    private function syncTikTokSampleOrders($channel)
    {
        $count = 0;
        $products = [
            ['sku' => 'TT-001', 'name' => 'เสื้อยืด Cotton 100%', 'price' => 299],
            ['sku' => 'TT-002', 'name' => 'กางเกงยีนส์ Slim Fit', 'price' => 590],
            ['sku' => 'TT-003', 'name' => 'รองเท้าผ้าใบ Sport', 'price' => 1290],
            ['sku' => 'TT-004', 'name' => 'กระเป๋าสะพายข้าง', 'price' => 450],
            ['sku' => 'TT-005', 'name' => 'หมวกแก๊ป Baseball', 'price' => 199],
        ];

        for ($i = 0; $i < 5; $i++) {
            $product = $products[array_rand($products)];
            $orderId = 'TT' . date('Ymd') . sprintf('%04d', rand(1, 9999));

            $order = Order::findOne(['order_id' => $orderId]);

            if (!$order) {
                $order = new Order();
                $order->order_id = $orderId;
                $order->channel_id = $channel->id;
                $order->sku = $product['sku'];
                $order->product_name = $product['name'];
                $order->quantity = rand(1, 5);
                $order->price = $product['price'];
                $order->total_amount = $order->quantity * $order->price;
                $order->order_date = date('Y-m-d H:i:s', strtotime('-' . rand(0, 7) . ' days -' . rand(0, 23) . ' hours'));
                $order->order_status = 'SHIPPED';

                if ($order->save()) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Sample Shopee orders for demo
     */
    private function shopeeSampleOrders($channel)
    {
        $count = 0;
        $products = [
            ['sku' => 'SH-001', 'name' => 'ชุดเดรสลายดอกไม้', 'price' => 390],
            ['sku' => 'SH-002', 'name' => 'เครื่องสำอาง Lipstick', 'price' => 159],
            ['sku' => 'SH-003', 'name' => 'อาหารเสริมวิตามิน C', 'price' => 290],
            ['sku' => 'SH-004', 'name' => 'หูฟัง Bluetooth 5.0', 'price' => 890],
            ['sku' => 'SH-005', 'name' => 'เคสโทรศัพท์ iPhone', 'price' => 99],
        ];

        for ($i = 0; $i < 5; $i++) {
            $product = $products[array_rand($products)];
            $orderId = 'SH' . date('Ymd') . sprintf('%04d', rand(1, 9999));

            $order = Order::findOne(['order_id' => $orderId]);

            if (!$order) {
                $order = new Order();
                $order->order_id = $orderId;
                $order->channel_id = $channel->id;
                $order->sku = $product['sku'];
                $order->product_name = $product['name'];
                $order->quantity = rand(1, 10);
                $order->price = $product['price'];
                $order->total_amount = $order->quantity * $order->price;
                $order->order_date = date('Y-m-d H:i:s', strtotime('-' . rand(0, 7) . ' days -' . rand(0, 23) . ' hours'));
                $order->order_status = 'COMPLETED';

                if ($order->save()) {
                    $count++;
                }
            }
        }

        return $count;
    }
}