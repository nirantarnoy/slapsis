<?php

namespace backend\services;

use backend\models\OnlineChannel;
use backend\models\Order;
use backend\models\ShopeeIncomeDetails;
use backend\models\ShopeeToken;
use GuzzleHttp\Client;
use Yii;
use yii\base\Exception;
use yii\helpers\Json;
use common\models\SyncLog;

class ShopeeIncomeService
{
    private $httpClient;
    private $partner_id = 2012399;
    private $partner_key = 'shpk72476151525864414e4b6e475449626679624f695a696162696570417043';

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }

    /**
     * Sync income details for all Shopee orders
     * @return int
     */
    public function syncAllOrders()
    {
        $log = new SyncLog();
        $log->type = SyncLog::TYPE_INCOME;
        $log->platform = SyncLog::PLATFORM_SHOPEE;
        $log->start_time = date('Y-m-d H:i:s');
        $log->status = SyncLog::STATUS_PENDING;
        $log->save();

        try {
            $shopeeChannel = OnlineChannel::findOne(['name' => 'Shopee']);
            if (!$shopeeChannel) {
                throw new \Exception('Shopee channel not found');
            }

            // Get list of already synced orders
            $syncedOrderSns = ShopeeIncomeDetails::find()
                ->select('order_sn')
                ->column();

            $orderSns = Order::find()
                ->select('order_sn')
                ->where(['channel_id' => $shopeeChannel->id])
                ->andWhere(['IS NOT', 'order_sn', null])
                ->andWhere(['NOT IN', 'order_sn', $syncedOrderSns])
                ->distinct()
                ->orderBy(['id' => SORT_DESC])
                ->column();

            $count = 0;
            $total = count($orderSns);
            Yii::info("Found {$total} Shopee orders to sync income details", __METHOD__);

            foreach ($orderSns as $index => $order_sn) {
                if (empty($order_sn)) continue;
            
                if ($this->syncOrderIncome($order_sn)) {
                    $count++;
                }
                // Sleep slightly to respect rate limits
                usleep(200000); // 0.2s

                if ($count >=50){
                    break;
                }
            }

            Yii::info("Synced income details for {$count}/{$total} orders", __METHOD__);

            $log->end_time = date('Y-m-d H:i:s');
            $log->status = SyncLog::STATUS_SUCCESS;
            $log->total_records = $count;
            $log->save();

            return $count;

        } catch (\Exception $e) {
            $log->end_time = date('Y-m-d H:i:s');
            $log->status = SyncLog::STATUS_FAILED;
            $log->message = $e->getMessage();
            $log->save();

            Yii::error('Shopee income sync error: ' . $e->getMessage(), __METHOD__);
            return 0;
        }
    }

    /**
     * Sync income details for a specific order
     * @param string $order_sn
     * @return bool
     */
    public function syncOrderIncome($order_sn)
    {
        $tokenModel = ShopeeToken::find()
            ->where(['status' => 'active'])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if (!$tokenModel) {
            Yii::error('No active Shopee token found', __METHOD__);
            return false;
        }

        if (strtotime($tokenModel->expires_at) < time()) {
            if (!$this->refreshShopeeToken($tokenModel)) {
                Yii::error('Failed to refresh Shopee token', __METHOD__);
                return false;
            }
        }

        return $this->fetchAndSaveEscrowDetail($tokenModel, $order_sn);
    }

    private function fetchAndSaveEscrowDetail($tokenModel, $order_sn)
    {
        $shop_id = $tokenModel->shop_id;
        $access_token = $tokenModel->access_token;
        $timestamp = time();
        $path = "/api/v2/payment/get_escrow_detail";
        $base_string = $this->partner_id . $path . $timestamp . $access_token . $shop_id;
        $sign = hash_hmac('sha256', $base_string, $this->partner_key);

        try {
            $response = $this->httpClient->get('https://partner.shopeemobile.com' . $path, [
                'query' => [
                    'partner_id' => (int)$this->partner_id,
                    'shop_id' => (int)$shop_id,
                    'sign' => $sign,
                    'timestamp' => $timestamp,
                    'access_token' => $access_token,
                    'order_sn' => $order_sn
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                Yii::error("HTTP Error {$response->getStatusCode()} for order: $order_sn", __METHOD__);
                return false;
            }

            $body = $response->getBody()->getContents();
            $data = Json::decode($body);

            if (!empty($data['error'])) {
                Yii::error("Shopee API Error: {$data['error']} - " . ($data['message'] ?? 'Unknown error'), __METHOD__);
                return false;
            }

            if (empty($data['response'])) {
                Yii::warning("No response data for order: $order_sn", __METHOD__);
                return false;
            }

            $detail = $data['response'];
            Yii::info("Shopee Income Detail for $order_sn: " . Json::encode($detail), __METHOD__);
            
            // Save to database
            $model = ShopeeIncomeDetails::findOne(['order_sn' => $order_sn]);
            if (!$model) {
                $model = new ShopeeIncomeDetails();
                $model->order_sn = $order_sn;
                $model->created_at = date('Y-m-d H:i:s');
            }

            $order = Order::findOne(['order_sn' => $order_sn]);
            if ($order) {
                $model->order_date = $order->order_date;
            }

            // Map fields with multiple possible keys
            // Based on actual log, many fields are inside 'order_income' array
            $income = $detail['order_income'] ?? [];

            $model->buyer_user_name = $detail['buyer_user_name'] ?? $detail['buyer_username'] ?? null;
            
            // Map from 'order_income' if available, otherwise fallback to root or other keys
            $model->buyer_total_amount = $income['buyer_total_amount'] ?? $detail['buyer_total_amount'] ?? $detail['buyer_paid_amount'] ?? $detail['total_amount'] ?? 0;
            $model->original_price = $income['original_price'] ?? $detail['original_price'] ?? $detail['order_original_price'] ?? 0;
            $model->seller_return_refund_amount = $income['seller_return_refund'] ?? $detail['seller_return_refund_amount'] ?? 0;
            $model->shipping_fee_discount_from_3pl = $income['shipping_fee_discount_from_3pl'] ?? $detail['shipping_fee_discount_from_3pl'] ?? 0;
            $model->seller_shipping_discount = $income['seller_shipping_discount'] ?? $detail['seller_shipping_discount'] ?? 0;
            $model->drc_adjustable_refund = $income['drc_adjustable_refund'] ?? $detail['drc_adjustable_refund'] ?? 0;
            $model->cost_of_goods_sold = $income['cost_of_goods_sold'] ?? $detail['cost_of_goods_sold'] ?? 0;
            $model->original_cost_of_goods_sold = $income['original_cost_of_goods_sold'] ?? $detail['original_cost_of_goods_sold'] ?? 0;
            $model->original_shopee_discount = $income['original_shopee_discount'] ?? $detail['original_shopee_discount'] ?? 0;
            $model->seller_coin_cash_back = $income['seller_coin_cash_back'] ?? $detail['seller_coin_cash_back'] ?? 0;
            $model->shopee_shipping_rebate = $income['shopee_shipping_rebate'] ?? $detail['shopee_shipping_rebate'] ?? 0;
            $model->commission_fee = $income['commission_fee'] ?? $detail['commission_fee'] ?? 0;
            $model->transaction_fee = $income['transaction_fee'] ?? $detail['transaction_fee'] ?? 0;
            $model->service_fee = $income['service_fee'] ?? $detail['service_fee'] ?? 0;
            
            // Voucher codes might be arrays or strings, handle accordingly if needed, but for now cast to float/string as per DB
            // DB expects decimal, but API returns array for seller_voucher_code in log: "seller_voucher_code":[]
            // If it's an array, we might need to sum values or take the first one. 
            // However, the log shows "voucher_from_seller":0 and "voucher_from_shopee":16 in order_income. Let's use those.
            $model->seller_voucher_code = $income['voucher_from_seller'] ?? $detail['seller_voucher_code'] ?? 0;
            $model->shopee_voucher_code = $income['voucher_from_shopee'] ?? $detail['shopee_voucher_code'] ?? 0;
            
            $model->escrow_amount = $income['escrow_amount'] ?? $detail['escrow_amount'] ?? $detail['estimated_seller_receive_amount'] ?? 0;
            $model->exchange_rate = $detail['exchange_rate'] ?? 1; // Default to 1 if not present
            $model->reverse_shipping_fee = $income['reverse_shipping_fee'] ?? $detail['reverse_shipping_fee'] ?? 0;
            $model->final_shipping_fee = $income['final_shipping_fee'] ?? $detail['final_shipping_fee'] ?? $detail['actual_shipping_fee'] ?? 0;
            $model->actual_shipping_fee = $income['actual_shipping_fee'] ?? $detail['actual_shipping_fee'] ?? 0;
            $model->order_chargeable_weight = $income['order_chargeable_weight'] ?? $detail['order_chargeable_weight'] ?? 0;
            $model->payment_promotion_amount = $income['payment_promotion'] ?? $detail['payment_promotion_amount'] ?? 0;
            $model->cross_border_tax = $income['cross_border_tax'] ?? $detail['cross_border_tax'] ?? 0;
            $model->shipping_fee_paid_by_buyer = $income['buyer_paid_shipping_fee'] ?? $detail['shipping_fee_paid_by_buyer'] ?? 0;
            $model->items = $income['items'] ?? $detail['items'] ?? [];
            
            $model->updated_at = date('Y-m-d H:i:s');

            if ($model->save()) {
                Yii::info("Saved income details for order: $order_sn", __METHOD__);
                return true;
            } else {
                Yii::error("Failed to save income details for order $order_sn: " . Json::encode($model->errors), __METHOD__);
                return false;
            }

        } catch (\Exception $e) {
            Yii::error("Exception fetching income for order $order_sn: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    private function refreshShopeeToken($tokenModel)
    {
        try {
            $refresh_token = $tokenModel->refresh_token;
            $shop_id = $tokenModel->shop_id;

            $timestamp = time();
            $path = "/api/v2/auth/access_token/get";
            $base_string = $this->partner_id . $path . $timestamp;
            $sign = hash_hmac('sha256', $base_string, $this->partner_key);

            $queryParams = [
                'partner_id' => $this->partner_id,
                'timestamp' => $timestamp,
                'sign' => $sign,
            ];

            $jsonPayload = [
                'shop_id' => (int)$shop_id,
                'partner_id' => (int)$this->partner_id,
                'refresh_token' => $refresh_token,
            ];

            $response = $this->httpClient->post('https://partner.shopeemobile.com' . $path, [
                'headers' => ['Content-Type' => 'application/json'],
                'query' => $queryParams,
                'json' => $jsonPayload,
                'timeout' => 30
            ]);

            if ($response->getStatusCode() !== 200) {
                Yii::error('HTTP Error: ' . $response->getStatusCode(), __METHOD__);
                return false;
            }

            $body = $response->getBody()->getContents();
            $data = Json::decode($body);

            if (!empty($data['error'])) {
                Yii::error("Shopee API Error: {$data['error']} - " . ($data['message'] ?? 'Unknown error'), __METHOD__);
                return false;
            }

            if (isset($data['access_token'])) {
                $expiresAt = date('Y-m-d H:i:s', time() + (int)($data['expire_in'] ?? 14400));
                $tokenModel->access_token = $data['access_token'];
                $tokenModel->refresh_token = $data['refresh_token'];
                $tokenModel->expires_at = $expiresAt;
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
