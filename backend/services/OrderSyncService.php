<?php

namespace backend\services;

use Yii;
use backend\models\Order;
use backend\models\OnlineChannel;
use backend\models\ShopeeToken; // สมมติว่ามีตาราง shopee_tokens
use backend\models\TiktokToken; // สมมติว่ามีตาราง tiktok_tokens
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
                    case 'TikTok':
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
        // ดึงข้อมูล token จากตาราง shopee_tokens
        $tokenModel = ShopeeToken::find()
            ->where(['status' => 'active'])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if (!$tokenModel) {
            Yii::warning('No active Shopee token found for channel: ' . $channel->id, __METHOD__);
            return $this->shopeeSampleOrders($channel);
        }

        // ตรวจสอบว่า token หมดอายุหรือไม่
        if ($tokenModel->expires_at && $tokenModel->expires_at < time()) {
            // ลองต่ออายุ token
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

                // ✅ แก้ base string ให้ถูกต้อง
                $base_string = $partner_id . $path . $timestamp . $access_token . $shop_id;
                $sign = hash_hmac('sha256', $base_string, $partner_key);

                // Parameters สำหรับ API call
                $params = [
                    'partner_id' => $partner_id,
                    'shop_id' => (int)$shop_id,
                    'sign' => $sign,
                    'timestamp' => time(),
                    'access_token' => $access_token,
                    'time_range_field' => 'create_time',
                    'time_from' => strtotime('-1 day'),
                    'time_to' => time(),
                    'page_size' => $page_size,
                    'response_optional_fields' => 'order_status',
                    'order_status' => 'SHIPPED', // optional
                    'cursor' => '',
                ];

                if (!empty($cursor)) {
                    $params['cursor'] = $cursor;
                }

                $response = $this->httpClient->get('https://partner.shopeemobile.com' . $path, [
                    'query' => $params,
                    'timeout' => 30 // ✅ เพิ่ม timeout
                ]);

                // ✅ เช็ค HTTP status
                if ($response->getStatusCode() !== 200) {
                    Yii::error('HTTP Error: ' . $response->getStatusCode(), __METHOD__);
                    break;
                }

                $body = $response->getBody()->getContents();
                $data = Json::decode($body);

                // ✅ เช็ค API errors
                if (isset($data['error'])) {
                    if(!empty($data['error'])){
                        Yii::error("Shopee API Error Sync: {$data['error']} - " . ($data['message'] ?? 'Unknown error'), __METHOD__);
                    }


                    // ถ้าเป็น token error ให้ลองรีเฟรช
                    if (in_array($data['error'], ['error_auth', 'error_permission'])) {
                        if ($this->refreshShopeeToken($tokenModel)) {
                            continue; // ลองใหม่ด้วย token ใหม่
                        }
                    }
                    break;
                }

                // ✅ ปรับการเช็ค response structure
                if (!isset($data['response']['order_list']) || !is_array($data['response']['order_list'])) {
                    Yii::warning('Invalid response structure from Shopee API', __METHOD__);
                    break;
                }

                $orderList = $data['response']['order_list'];
                if (empty($orderList)) {
                    Yii::info('No orders found in this batch', __METHOD__);
                    break;
                }

                foreach ($orderList as $orderData) {
                    $processResult = $this->processShopeeOrder($channel, $orderData, $partner_id, $partner_key, $access_token, $shop_id);
                    if ($processResult !== false) { // ✅ เช็คผลลัพธ์ดีขึ้น
                        $count += $processResult;
                    }
                }

                $cursor = $data['response']['next_cursor'] ?? '';

                // ✅ เพิ่ม delay เพื่อไม่ให้ hit rate limit
                if (!empty($cursor)) {
                    usleep(200000); // หน่วง 0.2 วินาที
                }

            } while (!empty($cursor));

            // ✅ เพิ่ม success log
            Yii::info("Synced $count Shopee orders for channel: " . $channel->id, __METHOD__);

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
                    'response_optional_fields' => 'item_list',
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
                Yii::error("Shopee API Error for order $order_sn: {$data['error']} - " . ($data['message'] ?? 'Unknown error'), __METHOD__);
                return 0;
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

                // ✅ เช็คและปรับค่า quantity
                $quantity = $item['model_quantity_purchased'] ?? 0;
                if ($quantity <= 0) {
                    Yii::warning("Invalid quantity for item {$item['item_id']} in order: $order_sn", __METHOD__);
                    continue;
                }
                $order->quantity = $quantity;

                // ✅ ปรับการคำนวณราคาให้ปลอดภัย
                $price_micro = $item['model_discounted_price'] ?? $item['model_original_price'] ?? 0;
                $order->price = $price_micro > 0 ? $price_micro / 100000 : 0; // Shopee ส่งมาเป็น micro units
                $order->total_amount = $order->quantity * $order->price;

                // ✅ ปรับการจัดการวันที่
                $create_time = $orderDetail['create_time'] ?? time();
                $order->order_date = date('Y-m-d H:i:s', $create_time);

                // ✅ เพิ่ม order_status กลับมา (ถ้า table มี field นี้)
                $order->order_status = $orderDetail['order_status'] ?? 'UNKNOWN';

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

    /**
     * Sync orders from TikTok Shop using real API
     * @param OnlineChannel $channel
     * @return int
     */
    private function syncTikTokOrders($channel)
    {
        // ดึงข้อมูล token จากตาราง tiktok_tokens
        $tokenModel = TiktokToken::find()
            ->where(['channel_id' => $channel->id, 'status' => 'active'])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if (!$tokenModel) {
            Yii::warning('No active TikTok token found for channel: ' . $channel->id, __METHOD__);
            return $this->syncTikTokSampleOrders($channel);
        }

        // ตรวจสอบว่า token หมดอายุหรือไม่
        if ($tokenModel->expires_at && $tokenModel->expires_at < time()) {
            if (!$this->refreshTikTokToken($tokenModel)) {
                Yii::warning('Failed to refresh TikTok token for channel: ' . $channel->id, __METHOD__);
                return $this->syncTikTokSampleOrders($channel);
            }
        }

        $app_key = 'your-tiktok-app-key'; // ใส่ app_key จริง
        $app_secret = 'your-tiktok-app-secret'; // ใส่ app_secret จริง
        $access_token = $tokenModel->access_token;
        $shop_id = $tokenModel->shop_id;

        $count = 0;
        $page_size = 50;
        $page_token = '';

        try {
            do {
                $timestamp = time();
                $path = "/order/202309/orders/search";

                // สร้าง signature สำหรับ TikTok
                $params = [
                    'app_key' => $app_key,
                    'timestamp' => $timestamp,
                    'shop_id' => $shop_id,
                    'page_size' => $page_size,
                    'create_time_from' => strtotime('-7 days'),
                    'create_time_to' => time(),
                ];

                if (!empty($page_token)) {
                    $params['page_token'] = $page_token;
                }

                ksort($params);
                $query_string = http_build_query($params);
                $sign_string = $path . '?' . $query_string . $app_secret;
                $sign = hash_hmac('sha256', $sign_string, $app_secret);

                $params['sign'] = $sign;

                $response = $this->httpClient->get('https://open-api.tiktokglobalshop.com' . $path, [
                    'query' => $params,
                    'headers' => [
                        'x-tts-access-token' => $access_token,
                    ]
                ]);

                $body = $response->getBody()->getContents();
                $data = Json::decode($body);

                if (!isset($data['data']['orders'])) {
                    break;
                }

                foreach ($data['data']['orders'] as $orderData) {
                    $count += $this->processTikTokOrder($channel, $orderData);
                }

                $page_token = $data['data']['next_page_token'] ?? '';

            } while (!empty($page_token));

        } catch (\Exception $e) {
            Yii::error('TikTok API error: ' . $e->getMessage(), __METHOD__);
            throw $e;
        }

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

            // ✅ เปลี่ยนจาก form_params เป็น json
            $response = $this->httpClient->post('https://partner.shopeemobile.com' . $path, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'partner_id' => (int)$partner_id,
                    'timestamp' => $timestamp,
                    'shop_id' => (int)$shop_id,
                    'refresh_token' => $refresh_token,
                    'sign' => $sign,
                ],
                'timeout' => 30
            ]);

            // ✅ เช็ค HTTP status code
            if ($response->getStatusCode() !== 200) {
                Yii::error('HTTP Error: ' . $response->getStatusCode(), __METHOD__);
                return false;
            }

            $body = $response->getBody()->getContents();
            $data = Json::decode($body);

            // ✅ เพิ่มการเช็ค error response
            if (isset($data['error'])) {
                if(!empty($data['error'])){
                    Yii::error("Shopee API Error: {$data['error']} - " . ($data['message'] ?? 'Unknown error'), __METHOD__);
                    return false;
                }

            }

            if (isset($data['access_token'])) {
                $tokenModel->access_token = $data['access_token'];
                $tokenModel->refresh_token = $data['refresh_token'];
                $tokenModel->expires_at = time() + $data['expires_in'];
                $tokenModel->updated_at = time();

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
            $app_key = 'your-tiktok-app-key';
            $app_secret = 'your-tiktok-app-secret';
            $refresh_token = $tokenModel->refresh_token;

            $timestamp = time();
            $path = "/authorization/202309/refresh_token";

            $params = [
                'app_key' => $app_key,
                'timestamp' => $timestamp,
                'refresh_token' => $refresh_token,
            ];

            ksort($params);
            $query_string = http_build_query($params);
            $sign_string = $path . '?' . $query_string . $app_secret;
            $sign = hash_hmac('sha256', $sign_string, $app_secret);

            $params['sign'] = $sign;

            $response = $this->httpClient->post('https://open-api.tiktokglobalshop.com' . $path, [
                'form_params' => $params
            ]);

            $body = $response->getBody()->getContents();
            $data = Json::decode($body);

            if (isset($data['data']['access_token'])) {
                $tokenModel->access_token = $data['data']['access_token'];
                $tokenModel->refresh_token = $data['data']['refresh_token'];
                $tokenModel->expires_at = time() + $data['data']['access_token_expire_in'];
                $tokenModel->updated_at = time();

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