<?php

namespace backend\services;

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

    function generateSign($appSecret, $params, $path) {
        ksort($params); // เรียง key
        $stringToSign = $appSecret . $path;
        foreach ($params as $key => $value) {
            $stringToSign .= $key . $value;
        }
        $stringToSign .= $appSecret;

        return hash_hmac('sha256', $stringToSign, $appSecret); // HMAC-SHA256
    }
    function generateSignForOrder($appSecret, $params, $path, $body='') {
        ksort($params); // เรียง key
        $stringToSign = $appSecret . $path;
        foreach ($params as $key => $value) {
            $stringToSign .= $key . $value;
        }
        if (!empty($body)) {
            $stringToSign .= $body;
        }

        $stringToSign .= $appSecret;

        // สร้าง HMAC-SHA256 signature และคืนค่าเป็น hex
        return hash_hmac('sha256', $stringToSign, $appSecret);
    }


    private function fetchShopCipher($tokenModel)
    {

        $appKey     = '6h9n461r774e1';
        $appSecret  = '1c45a0c25224293abd7de681049f90de3363389a';
        $timestamp  = time();
        $path       = '/authorization/202309/shops';

        $params = [
            'app_key'   => $appKey,
            'timestamp' => $timestamp,
         //   'access_token' => $tokenModel->access_token, // ถ้าต้องใช้ใน endpoint
        ];

        // ✅ สร้าง sign ถูกต้องตาม TikTok

       // $sign = strtolower(hash('sha256', $appSecret . $path . $signString . $appSecret));
        $sign = $this->generateSign($appSecret, $params, $path);

        $url = 'https://open-api.tiktokglobalshop.com/authorization/202309/shops?' . http_build_query($params) . '&sign=' . $sign;


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

                $now = date('Y-m-d H:i:s');

                $tokenModel->shop_cipher = $shopCipher;
                $tokenModel->shop_name   = $shopName;
                $tokenModel->updated_at = $now;
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
            Yii::info("Access token is: " . substr($tokenModel->access_token, 0, 20) . "...", __METHOD__);
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

        try {
            do {
                $pageCount++;
                $timestamp = time();

                // ✅ Parameters ที่จะส่งใน query string (ไม่รวม sign และ access_token)
                $queryParams = [
                    'app_key'     => $appKey,
                    'page_size'   => $pageSize,
                    'shop_cipher' => $shopCipher,
                    'sort_field'  => 'create_time',
                    'sort_order'  => 'DESC',
                    'timestamp'   => $timestamp,
                ];

                if ($pageToken) {
                    $queryParams['page_token'] = $pageToken;
                }

                // ✅ Body JSON สำหรับ POST request
                $body = [
                    'order_status' => 'DELIVERED', // UNPAID , ON_HOLD , IN_TRANSIT , DELIVERED , COMPLETED , CANCELLED
                    'create_time_ge' => strtotime('-7 days'),
                    'create_time_lt' => $timestamp,
                ];
                $bodyJson = json_encode($body);

                // ✅ สร้าง signature ด้วย HMAC-SHA256 พร้อม body
                $sign = $this->generateSignForOrder($appSecret, $queryParams, $path, $bodyJson);

                // ✅ Debug signature process
                //$this->debugSignature($appSecret, $queryParams, $path, $bodyJson);

                // ✅ เพิ่ม sign และ access_token เข้าไปใน URL
                $queryParams['sign'] = $sign;
                $queryParams['access_token'] = $accessToken;

                $url = 'https://open-api.tiktokglobalshop.com' . $path . '?' . http_build_query($queryParams);

                Yii::info("Request URL: $url", __METHOD__);
                Yii::info("Request body: $bodyJson", __METHOD__);

                $client = new \GuzzleHttp\Client([
                    'timeout' => 30,
                ]);

                $response = $client->post($url, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'x-tts-access-token' => $accessToken,
                    ],
                    'body' => $bodyJson
                ]);

                $result = json_decode($response->getBody(), true);
                Yii::info('Orders API response: ' . json_encode($result), __METHOD__);

                if (isset($result['code']) && $result['code'] !== 0) {
                    throw new \Exception("TikTok API error [{$result['code']}] {$result['message']}");
                }

                $orders = $result['data']['orders'] ?? [];
                foreach ($orders as $order) {
                    $save_row_count = $this->processTikTokOrder($channel, $order);
                    if($save_row_count >0){
                        $count++;
                    }
                }

                $pageToken = $result['data']['next_page_token'] ?? '';
                if ($pageCount >= 5) break; // จำกัดการ loop

            } while (!empty($pageToken));

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            $body = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : 'No response';
            Yii::error("ClientException [$statusCode]: $body", __METHOD__);

            // ถ้าได้ 401 ลองใช้วิธีอื่น
            if ($statusCode == 401) {
                Yii::error("401 Unauthorized - trying alternative signature method", __METHOD__);
                // อาจจะต้อง retry ด้วยวิธีอื่น
            }

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

                // ✅ กำหนดค่าเริ่มต้น
                $order_qty = 1; // default quantity
                $skuId = '';
                $productName = $item['product_name'] ?? '';

                // ✅ ตรวจสอบว่ามี combined_listing_skus หรือไม่
                if (isset($item['combined_listing_skus']) && is_array($item['combined_listing_skus']) && !empty($item['combined_listing_skus'])) {
                    // มี combined_listing_skus
                    foreach ($item['combined_listing_skus'] as $sku) {
                        $skuId = $sku['sku_id'] ?? '';
                        $order_qty = $sku['sku_count'] ?? 1;
                        // ถ้ามีหลาย SKU ให้รวม quantity
                        // หรือจะสร้างแยก order แต่ละ SKU ก็ได้
                        break; // เอาตัวแรกก่อน หรือจะ loop ทั้งหมดก็ได้
                    }
                } else {
                    // ไม่มี combined_listing_skus ให้ใช้ข้อมูลจาก line_item โดยตรง
                    $skuId = $item['sku_id'] ?? $item['seller_sku'] ?? '';
                    $order_qty = $item['quantity'] ?? $item['sku_count'] ?? 1;
                }

                // ✅ ตรวจสอบข้อมูลที่จำเป็น
                if (empty($productName)) {
                    Yii::warning("Missing product name for order {$order_id}, item {$item['id']}", __METHOD__);
                    continue;
                }

                $order = new Order();
                $order->order_id = $unique_order_id;
                $order->channel_id = $channel->id;
                $order->sku = $skuId;
                $order->product_name = $productName;
                $order->quantity = $order_qty;

                // ✅ แก้ไขการคำนวณราคา - ตรวจสอบว่าเป็น micro units หรือไม่
                $salePrice = floatval($item['sale_price'] ?? 0);
                if ($salePrice > 1000) {
                    // ถ้าราคาสูงมาก อาจเป็น micro units (หารด้วย 1,000,000)
                    $order->price = $salePrice / 1000000;
                } else {
                    // ถ้าราคาน้อย อาจเป็นหน่วยปกติแล้ว
                    $order->price = $salePrice;
                }

                $order->total_amount = $order->quantity * $order->price;
                $order->order_date = date('Y-m-d H:i:s', $orderData['create_time']);

                // ✅ เพิ่ม debug log เพื่อดูข้อมูล
                Yii::info("Processing order: {$unique_order_id}, SKU: {$skuId}, Qty: {$order_qty}, Price: {$order->price}", __METHOD__);

                if ($order->save()) {
                    $count++;
                } else {
                    Yii::error('Failed to save TikTok order: ' . Json::encode($order->errors), __METHOD__);
                }
            }

        } catch (\Exception $e) {
            Yii::error('Error processing TikTok order ' . $order_id . ': ' . $e->getMessage(), __METHOD__);
            Yii::error('Order data: ' . Json::encode($orderData), __METHOD__);
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



    /////////////////// sync shopee other fee
    ///
    ///
    ///

    public function syncShopeeFree($channelId = null)
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
                        $totalSynced += $this->syncShopeeTransactionFees($channel);
                        break;
                    case 'Shopee':
                        $totalSynced += $this->syncShopeeTransactionFees($channel);
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

    private function syncShopeeTransactionFees($channel, $fromTime = null, $toTime = null)
    {
        $tokenModel = ShopeeToken::find()
            ->where(['status' => 'active'])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if (!$tokenModel) {
            Yii::warning('No active Shopee token found for channel: ' . $channel->id, __METHOD__);
            return 0;
        }

        // ตรวจสอบ token หมดอายุ
        if (strtotime($tokenModel->expires_at) < time()) {
            Yii::error('Access Token is expired');
            if (!$this->refreshShopeeToken($tokenModel)) {
                Yii::warning('Failed to refresh Shopee token for channel: ' . $channel->id, __METHOD__);
                return 0;
            }
        }

        $partner_id = 2012399;
        $partner_key = 'shpk72476151525864414e4b6e475449626679624f695a696162696570417043';
        $shop_id = $tokenModel->shop_id;
        $access_token = $tokenModel->access_token;

        // Default to last 30 days if not specified
        if ($fromTime === null) {
            $fromTime = strtotime('-30 day');
        }
        if ($toTime === null) {
            $toTime = time();
        }

        $count = 0;
        $page_no = 1;
        $page_size = 100;

        try {
            do {
                $timestamp = time();
                $path = "/api/v2/payment/get_wallet_transaction_list";
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
                    'page_no' => $page_no,
                    'page_size' => $page_size,
                    'create_time_from' => $fromTime,
                    'create_time_to' => $toTime,
                ];

                $response = $this->httpClient->post('https://partner.shopeemobile.com' . $path, [
                    'query' => $params,
                    'json' => $body,
                    'timeout' => 30
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode !== 200) {
                    Yii::error("HTTP Shopee Fee Sync Error: {$statusCode}", __METHOD__);
                    break;
                }

                $rawBody = (string)$response->getBody();
                Yii::debug("Shopee Transaction Raw Body: " . $rawBody, __METHOD__);

                $data = Json::decode($rawBody);

                // เช็ค API error
                if (!empty($data['error'])) {
                    Yii::error("Shopee API Error Fee Sync: {$data['error']} - " . ($data['message'] ?? 'Unknown error'), __METHOD__);

                    if (in_array($data['error'], ['error_auth', 'error_permission'])) {
                        if ($this->refreshShopeeToken($tokenModel)) {
                            continue;
                        }
                    }
                    break;
                }

                // เช็ค response
                if (empty($data['response']['transaction_list'])) {
                    Yii::info('Shopee response valid but no transaction_list found', __METHOD__);
                    break;
                }

                $transactionList = $data['response']['transaction_list'];
                foreach ($transactionList as $transaction) {
                    $processResult = $this->processShopeeTransaction($channel, $transaction, $shop_id);
                    if ($processResult) {
                        $count++;
                    }
                }

                // เช็คว่ามีหน้าถัดไปไหม
                $more = $data['response']['more'] ?? false;
                if ($more) {
                    $page_no++;
                    usleep(200000); // 0.2 วินาที กัน rate limit
                } else {
                    break;
                }

            } while (true);

            Yii::info("Synced {$count} Shopee transactions for channel: " . $channel->id, __METHOD__);

        } catch (\Exception $e) {
            Yii::error('Shopee Transaction API error: ' . $e->getMessage(), __METHOD__);
            throw $e;
        }

        return $count;
    }

    /**
     * Process individual Shopee transaction
     * @param OnlineChannel $channel
     * @param array $transaction
     * @param string $shop_id
     * @return bool
     */
    private function processShopeeTransaction($channel, $transaction, $shop_id)
    {
        try {
            $transaction_id = $transaction['transaction_id'] ?? null;
            if (empty($transaction_id)) {
                Yii::warning("Missing transaction_id", __METHOD__);
                return false;
            }

            // เช็คว่ามีอยู่แล้วหรือไม่
            $existingTransaction = ShopeeTransaction::findOne(['transaction_id' => $transaction_id]);
            if ($existingTransaction) {
                return false; // ข้ามถ้ามีแล้ว
            }

            $feeTransaction = new ShopeeTransaction();
            $feeTransaction->transaction_id = $transaction_id;
            $feeTransaction->channel_id = $channel->id;
            $feeTransaction->shop_id = $shop_id;

            // Transaction details
            $feeTransaction->transaction_type = $transaction['transaction_type'] ?? 'UNKNOWN';
            $feeTransaction->status = $transaction['status'] ?? 'UNKNOWN';
            $feeTransaction->reason = $transaction['reason'] ?? '';

            // Amount (Shopee ส่งมาเป็น float)
            $feeTransaction->amount = (float)($transaction['amount'] ?? 0);
            $feeTransaction->current_balance = (float)($transaction['current_balance'] ?? 0);

            // Order reference
            $feeTransaction->order_sn = $transaction['order_sn'] ?? null;

            // Timestamps
            $create_time = $transaction['create_time'] ?? time();
            $feeTransaction->transaction_date = date('Y-m-d H:i:s', $create_time);
            $feeTransaction->created_at = date('Y-m-d H:i:s');
            $feeTransaction->updated_at = date('Y-m-d H:i:s');

            // จัดหมวดหมู่ค่าธรรมเนียม
            $feeTransaction->fee_category = $this->categorizeFee($transaction);

            if ($feeTransaction->save()) {
                Yii::info("Saved transaction: $transaction_id", __METHOD__);
                return true;
            } else {
                Yii::error("Failed to save transaction $transaction_id: " . Json::encode($feeTransaction->errors), __METHOD__);
                return false;
            }

        } catch (\Exception $e) {
            Yii::error('Error processing transaction: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Categorize fee type based on transaction data
     * @param array $transaction
     * @return string
     */
    private function categorizeFee($transaction)
    {
        $reason = strtolower($transaction['reason'] ?? '');
        $type = strtolower($transaction['transaction_type'] ?? '');

        // เฉพาะรายการที่เป็นค่าใช้จ่าย (amount < 0)
        $amount = (float)($transaction['amount'] ?? 0);
        if ($amount >= 0) {
            return 'INCOME'; // รายได้
        }

        // จัดหมวดหมู่ตามเหตุผล
        if (strpos($reason, 'commission') !== false) {
            return 'COMMISSION_FEE';
        } elseif (strpos($reason, 'transaction fee') !== false || strpos($reason, 'payment fee') !== false) {
            return 'TRANSACTION_FEE';
        } elseif (strpos($reason, 'service fee') !== false) {
            return 'SERVICE_FEE';
        } elseif (strpos($reason, 'shipping') !== false) {
            return 'SHIPPING_FEE';
        } elseif (strpos($reason, 'campaign') !== false || strpos($reason, 'ads') !== false || strpos($reason, 'promotion') !== false) {
            return 'CAMPAIGN_FEE';
        } elseif (strpos($reason, 'penalty') !== false || strpos($reason, 'fine') !== false) {
            return 'PENALTY_FEE';
        } elseif (strpos($reason, 'refund') !== false || strpos($reason, 'return') !== false) {
            return 'REFUND';
        } elseif (strpos($reason, 'adjustment') !== false) {
            return 'ADJUSTMENT';
        } elseif (strpos($reason, 'withdrawal') !== false) {
            return 'WITHDRAWAL';
        } else {
            return 'OTHER';
        }
    }

    /**
     * Get order income details (fees breakdown for specific orders)
     * @param OnlineChannel $channel
     * @param array $orderSnList Array of order_sn
     * @return array
     */
    private function getShopeeOrderIncome($channel, $orderSnList)
    {
        if (empty($orderSnList)) {
            return [];
        }

        $tokenModel = ShopeeToken::find()
            ->where(['status' => 'active'])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if (!$tokenModel) {
            Yii::warning('No active Shopee token found for channel: ' . $channel->id, __METHOD__);
            return [];
        }

        // ตรวจสอบ token หมดอายุ
        if (strtotime($tokenModel->expires_at) < time()) {
            if (!$this->refreshShopeeToken($tokenModel)) {
                return [];
            }
        }

        $partner_id = 2012399;
        $partner_key = 'shpk72476151525864414e4b6e475449626679624f695a696162696570417043';
        $shop_id = $tokenModel->shop_id;
        $access_token = $tokenModel->access_token;

        try {
            $timestamp = time();
            $path = "/api/v2/payment/get_order_income";
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
                'order_sn_list' => array_values($orderSnList),
            ];

            $response = $this->httpClient->post('https://partner.shopeemobile.com' . $path, [
                'query' => $params,
                'json' => $body,
                'timeout' => 30
            ]);

            if ($response->getStatusCode() !== 200) {
                Yii::error("HTTP Error getting order income: {$response->getStatusCode()}", __METHOD__);
                return [];
            }

            $rawBody = (string)$response->getBody();
            $data = Json::decode($rawBody);

            if (!empty($data['error'])) {
                Yii::error("Shopee API Error getting order income: {$data['error']}", __METHOD__);
                return [];
            }

            return $data['response']['order_income_list'] ?? [];

        } catch (\Exception $e) {
            Yii::error('Error getting order income: ' . $e->getMessage(), __METHOD__);
            return [];
        }
    }

    /**
     * Update order with income details (fees)
     * @param string $order_sn
     * @param array $incomeData
     * @return bool
     */
    private function updateOrderWithIncome($order_sn, $incomeData)
    {
        try {
            $orders = Order::find()
                ->where(['order_sn' => $order_sn])
                ->all();

            if (empty($orders)) {
                return false;
            }

            foreach ($orders as $order) {
                // Shopee ส่งมาเป็น float
                $order->commission_fee = (float)($incomeData['commission_fee'] ?? 0);
                $order->transaction_fee = (float)($incomeData['transaction_fee'] ?? 0);
                $order->service_fee = (float)($incomeData['service_fee'] ?? 0);
                $order->escrow_amount = (float)($incomeData['escrow_amount'] ?? 0);
                $order->actual_income = (float)($incomeData['actual_income'] ?? 0);

                $order->updated_at = date('Y-m-d H:i:s');

                if (!$order->save()) {
                    Yii::error("Failed to update order fees for $order_sn: " . Json::encode($order->errors), __METHOD__);
                }
            }

            return true;

        } catch (\Exception $e) {
            Yii::error('Error updating order with income: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }
}