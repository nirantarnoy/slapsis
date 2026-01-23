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
     * Sync orders from Shopee using real API (Improved for Debugging and Performance)
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
        $page_size = 50; // ลดขนาดหน้าลงเพื่อป้องกัน timeout
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
                    'time_from' => strtotime('-15 day'),
                    'time_to' => time(),
                    'page_size' => $page_size,
                    'response_optional_fields' => 'order_status',
                ];

                if (!empty($cursor)) {
                    $params['cursor'] = $cursor;
                }

                Yii::info("Shopee Sync: Fetching order list. Path: $path, Params: " . Json::encode($params), __METHOD__);

                $response = $this->httpClient->get('https://partner.shopeemobile.com' . $path, [
                    'query' => $params,
                    'timeout' => 30
                ]);

                if ($response->getStatusCode() !== 200) {
                    Yii::error("HTTP Shopee Sync Error: {$response->getStatusCode()}", __METHOD__);
                    break;
                }

                $rawBody = (string)$response->getBody();
                Yii::info("Shopee Sync: Raw Response: " . $rawBody, __METHOD__);
                $data = Json::decode($rawBody);

                if (!empty($data['error'])) {
                    Yii::error("Shopee API Error Sync: {$data['error']} - " . ($data['message'] ?? 'Unknown error'), __METHOD__);
                    
                    // Retry logic for auth errors
                    if (in_array($data['error'], ['error_auth', 'error_permission', 'error_token', 'error_invalid_token'])) {
                        Yii::info("Attempting to refresh token due to error: {$data['error']}", __METHOD__);
                        if ($this->refreshShopeeToken($tokenModel)) {
                            $access_token = $tokenModel->access_token;
                            $shop_id = $tokenModel->shop_id;
                            Yii::info("Token refreshed. Retrying with new token...", __METHOD__);
                            continue; // Retry the loop with new token
                        }
                    }
                    break;
                }

                if (empty($data['response']['order_list'])) {
                    Yii::info('Shopee response valid but no order_list found', __METHOD__);
                    break;
                }

                $orderList = $data['response']['order_list'];
                $orderSns = array_column($orderList, 'order_sn');

                // Batch process order details (Shopee allows up to 50 SNs per call)
                if (!empty($orderSns)) {
                    $processResult = $this->processShopeeOrdersBatch(
                        $channel,
                        $orderSns,
                        $partner_id,
                        $partner_key,
                        $access_token,
                        $shop_id
                    );
                    $count += $processResult;
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

    /**
     * Process orders in batch to reduce API calls and prevent timeout
     */
    private function processShopeeOrdersBatch($channel, $orderSns, $partner_id, $partner_key, $access_token, $shop_id)
    {
        $count = 0;
        $orderSnString = implode(',', $orderSns);

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
                    'order_sn_list' => $orderSnString,
                    'response_optional_fields' => 'item_list,buyer_paid_amount,buyer_total_amount,total_amount,payment_info,escrow_amount,commission_fee,service_fee',
                ],
                'timeout' => 30
            ]);

            if ($response->getStatusCode() !== 200) {
                Yii::error("HTTP Error {$response->getStatusCode()} for batch orders", __METHOD__);
                return 0;
            }

            $rawBody = $response->getBody()->getContents();
            Yii::info("Shopee Sync: Batch Detail Raw Response: " . $rawBody, __METHOD__);
            $data = Json::decode($rawBody);

            if (!empty($data['error'])) {
                Yii::error("Shopee API Error for batch orders: {$data['error']} - " . ($data['message'] ?? 'Unknown error'), __METHOD__);
                return 0;
            }

            if (empty($data['response']['order_list'])) {
                return 0;
            }

            $allowed_statuses = ['READY_TO_SHIP', 'PROCESSED', 'SHIPPED', 'COMPLETED', 'TO_CONFIRM_RECEIVE'];

            foreach ($data['response']['order_list'] as $orderDetail) {
                $order_sn = $orderDetail['order_sn'];
                $order_status = strtoupper($orderDetail['order_status'] ?? 'UNKNOWN');

                if (!in_array($order_status, $allowed_statuses)) {
                    Yii::debug("Skip order $order_sn with status $order_status", __METHOD__);
                    continue;
                }

                if (empty($orderDetail['item_list'])) continue;

                foreach ($orderDetail['item_list'] as $item) {
                    $unique_order_id = $order_sn . '_' . $item['item_id'];

                    $existingOrder = Order::findOne(['order_id' => $unique_order_id]);
                    if ($existingOrder) continue;

                    $order = new Order();
                    $order->order_id = $unique_order_id;
                    $order->channel_id = $channel->id;
                    $order->shop_id = $shop_id;
                    $order->order_sn = $order_sn;
                    $order->sku = $item['model_sku'] ?? $item['item_sku'] ?? 'UNKNOWN_SKU';
                    $order->product_name = $item['item_name'];

                    $this->checkSaveNewProduct($order->sku, $order->product_name);

                    $quantity = $item['model_quantity_purchased'] ?? $item['quantity_purchased'] ?? 0;
                    $order->quantity = $quantity > 0 ? $quantity : 0;

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
                    }
                }
            }

        } catch (\Exception $e) {
            Yii::error('Error processing Shopee batch: ' . $e->getMessage(), __METHOD__);
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

            $response = $this->httpClient->post('https://partner.shopeemobile.com' . $path, [
                'headers' => ['Content-Type' => 'application/json'],
                'query' => ['partner_id' => $partner_id, 'timestamp' => $timestamp, 'sign' => $sign],
                'json' => ['shop_id' => (int)$shop_id, 'partner_id' => (int)$partner_id, 'refresh_token' => $refresh_token],
                'timeout' => 30
            ]);

            if ($response->getStatusCode() !== 200) {
                Yii::error("Refresh Token HTTP Error: " . $response->getStatusCode(), __METHOD__);
                return false;
            }
            $body = $response->getBody()->getContents();
            Yii::info("Refresh Token Response: " . $body, __METHOD__);
            $data = Json::decode($body);
            if (!empty($data['error'])) {
                Yii::error("Refresh Token API Error: {$data['error']} - " . ($data['message'] ?? 'Unknown error'), __METHOD__);
                return false;
            }

            if (isset($data['access_token'])) {
                $tokenModel->access_token = $data['access_token'];
                $tokenModel->refresh_token = $data['refresh_token'];
                $tokenModel->expires_at = date('Y-m-d H:i:s', time() + (int)($data['expire_in'] ?? 14400));
                $tokenModel->updated_at = date('Y-m-d H:i:s');
                return $tokenModel->save();
            }
            return false;
        } catch (\Exception $e) {
            Yii::error('Failed to refresh Shopee token: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }
}
