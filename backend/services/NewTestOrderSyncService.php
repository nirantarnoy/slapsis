<?php

namespace backend\services;

use backend\models\ShopeeSettlement;
use backend\models\ShopeeTransaction;
use Yii;
use backend\models\Order;
use backend\models\OnlineChannel;
use backend\models\ShopeeToken;
use backend\models\TiktokToken;
use yii\base\Exception;
use GuzzleHttp\Client;
use yii\helpers\Json;

class NewTestOrderSyncService
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
     * Sync orders from Shopee using real API (Improved for Debugging)
     * @param OnlineChannel $channel
     * @return int
     */
    public function syncShopeeOrders($channel)
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
            Yii::info('Access Token is expired, attempting to refresh...', __METHOD__);
            if (!$this->refreshShopeeToken($tokenModel)) {
                Yii::warning('Failed to refresh Shopee token for channel: ' . $channel->id, __METHOD__);
                return 0;
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
                    'time_from' => strtotime('-15 day'), // ขยายเวลาเป็น 15 วัน
                    'time_to' => time(),
                    'page_size' => $page_size,
                    'response_optional_fields' => 'order_status',
                    // 'order_status' => 'SHIPPED', // เอาออกเพื่อให้ดึงทุกสถานะ
                ];

                if (!empty($cursor)) {
                    $params['cursor'] = $cursor;
                }

                Yii::info("Fetching Shopee order list with params: " . Json::encode($params), __METHOD__);

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

                if (!empty($data['error'])) {
                    Yii::error("Shopee API Error Sync: {$data['error']} - " . ($data['message'] ?? 'Unknown error'), __METHOD__);
                    break;
                }

                if (empty($data['response']['order_list'])) {
                    Yii::info('Shopee response valid but no order_list found', __METHOD__);
                    break;
                }

                $orderList = $data['response']['order_list'];
                Yii::info("Found " . count($orderList) . " orders in this page", __METHOD__);

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
                    usleep(200000);
                }

            } while (!empty($cursor));

            Yii::info("Synced {$count} Shopee orders for channel: " . $channel->id, __METHOD__);

        } catch (\Exception $e) {
            Yii::error('Shopee API error: ' . $e->getMessage(), __METHOD__);
            throw $e;
        }

        return $count;
    }

    private function processShopeeOrder($channel, $orderData, $partner_id, $partner_key, $access_token, $shop_id)
    {
        $order_sn = $orderData['order_sn'];
        $count = 0;

        try {
            $timestamp = time();
            $path = "/api/v2/order/get_order_detail";
            $base_string = $partner_id . $path . $timestamp . $access_token . $shop_id;
            $sign = hash_hmac('sha256', $base_string, $partner_key);

            $response = $this->httpClient->get('https://partner.shopeemobile.com' . $path, [
                'query' => [
                    'partner_id' => (int)$partner_id,
                    'timestamp' => $timestamp,
                    'access_token' => $access_token,
                    'shop_id' => (int)$shop_id,
                    'sign' => $sign,
                    'order_sn_list' => $order_sn,
                    'response_optional_fields' => 'item_list,buyer_paid_amount,buyer_total_amount,total_amount,payment_info,escrow_amount,commission_fee,service_fee',
                ],
                'timeout' => 30
            ]);

            if ($response->getStatusCode() !== 200) {
                Yii::error("HTTP Error {$response->getStatusCode()} for order: $order_sn", __METHOD__);
                return 0;
            }

            $body = $response->getBody()->getContents();
            $data = Json::decode($body);

            if (!empty($data['error'])) {
                Yii::error("Shopee API Error for order $order_sn: {$data['error']} - " . ($data['message'] ?? 'Unknown error'), __METHOD__);
                return 0;
            }

            if (!isset($data['response']['order_list']) || empty($data['response']['order_list'])) {
                Yii::warning("No order detail found for order: $order_sn", __METHOD__);
                return 0;
            }

            $orderDetail = $data['response']['order_list'][0];
            $order_status = strtoupper($orderDetail['order_status'] ?? 'UNKNOWN');

            // ขยายสถานะที่ยอมรับ
            $allowed_statuses = ['READY_TO_SHIP', 'PROCESSED', 'SHIPPED', 'COMPLETED', 'TO_CONFIRM_RECEIVE'];
            if (!in_array($order_status, $allowed_statuses)) {
                Yii::info("Skip order $order_sn with status $order_status (Not in allowed list)", __METHOD__);
                return 0;
            }

            if (!isset($orderDetail['item_list']) || !is_array($orderDetail['item_list'])) {
                Yii::warning("No items found for order: $order_sn", __METHOD__);
                return 0;
            }

            foreach ($orderDetail['item_list'] as $item) {
                $unique_order_id = $order_sn . '_' . $item['item_id'];

                $existingOrder = Order::findOne(['order_id' => $unique_order_id]);
                if ($existingOrder) {
                    Yii::debug("Order $unique_order_id already exists, skipping.", __METHOD__);
                    continue;
                }

                $order = new Order();
                $order->order_id = $unique_order_id;
                $order->channel_id = $channel->id;
                $order->shop_id = $shop_id;
                $order->order_sn = $order_sn;

                $order->sku = $item['model_sku'] ?? $item['item_sku'] ?? 'UNKNOWN_SKU';
                $order->product_name = $item['item_name'];

                $this->checkSaveNewProduct($order->sku, $order->product_name);

                $quantity = $item['model_quantity_purchased'] ?? $item['quantity_purchased'] ?? 0;
                if ($quantity <= 0) {
                    Yii::warning("Invalid quantity for item {$item['item_id']} in order: $order_sn", __METHOD__);
                    continue;
                }
                $order->quantity = $quantity;

                $price_value = $item['model_discounted_price'] ?? $item['discounted_price'] ?? $item['model_original_price'] ?? $item['original_price'] ?? 0;
                $order->price = (float)$price_value;
                $order->total_amount = $order->quantity * $order->price;

                $create_time = $orderDetail['create_time'] ?? time();
                $order->order_date = date('Y-m-d H:i:s', $create_time);

                $order->order_status = $order_status;
                $order->created_at = date('Y-m-d H:i:s');
                $order->updated_at = date('Y-m-d H:i:s');

                if ($order->save()) {
                    $count++;
                    Yii::info("Successfully saved order: $unique_order_id", __METHOD__);
                } else {
                    Yii::error("Failed to save Shopee order $unique_order_id: " . Json::encode($order->errors), __METHOD__);
                }
            }

        } catch (\Exception $e) {
            Yii::error('Error processing Shopee order ' . $order_sn . ': ' . $e->getMessage(), __METHOD__);
            return 0;
        }

        return $count;
    }

    private function checkSaveNewProduct($sku, $name)
    {
        $model = \backend\models\Product::find()->where(['sku' => trim($sku), 'name' => trim($name)])->one();
        if (!$model) {
            $model_new = new \backend\models\Product();
            $model_new->product_group_id = 1;
            $model_new->sku = trim($sku);
            $model_new->name = trim($name);
            $model_new->status = 1;
            $model_new->save(false);
        }
    }

    private function refreshShopeeToken($tokenModel)
    {
        try {
            $partner_id = 2012399;
            $partner_key = 'shpk72476151525864414e4b6e475449626679624f695a696162696570417043';
            $refresh_token = $tokenModel->refresh_token;
            $shop_id = $tokenModel->shop_id;

            $timestamp = time();
            $path = "/api/v2/auth/access_token/get";

            $base_string = $partner_id . $path . $timestamp;
            $sign = hash_hmac('sha256', $base_string, $partner_key);

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

            $response = $this->httpClient->post('https://partner.shopeemobile.com' . $path, [
                'headers' => ['Content-Type' => 'application/json'],
                'query' => $queryParams,
                'json' => $jsonPayload,
                'timeout' => 30
            ]);

            if ($response->getStatusCode() !== 200) {
                Yii::error('Token refresh HTTP Error: ' . $response->getStatusCode(), __METHOD__);
                return false;
            }

            $data = Json::decode($response->getBody()->getContents());

            if (!empty($data['error'])) {
                Yii::error("Shopee Token Refresh API Error: {$data['error']} - " . ($data['message'] ?? 'Unknown error'), __METHOD__);
                return false;
            }

            if (isset($data['access_token'])) {
                $tokenModel->access_token = $data['access_token'];
                $tokenModel->refresh_token = $data['refresh_token'];
                $tokenModel->expires_at = date('Y-m-d H:i:s', time() + (int)($data['expire_in'] ?? 14400));
                $tokenModel->updated_at = date('Y-m-d H:i:s');

                if ($tokenModel->save()) {
                    Yii::info("Token refreshed successfully for shop_id: $shop_id", __METHOD__);
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            Yii::error('Failed to refresh Shopee token: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }
}
