<?php

namespace backend\services;

use backend\models\OnlineChannel;
use backend\models\Order;
use backend\models\TiktokIncomeDetails;
use backend\models\TiktokToken;
use GuzzleHttp\Client;
use Yii;
use yii\helpers\Json;
use common\models\SyncLog;

class TiktokIncomeService
{
    private $httpClient;
    private $appKey = '6h9n461r774e1';
    private $appSecret = '1c45a0c25224293abd7de681049f90de3363389a';

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }

    /**
     * Sync income details for all TikTok orders
     * @return int
     */
    public function syncAllOrders()
    {
        $log = new SyncLog();
        $log->type = SyncLog::TYPE_INCOME;
        $log->platform = SyncLog::PLATFORM_TIKTOK;
        $log->start_time = date('Y-m-d H:i:s');
        $log->status = SyncLog::STATUS_PENDING;
        $log->save();

        try {
            $tiktokChannel = OnlineChannel::findOne(['name' => 'Tiktok']);
            if (!$tiktokChannel) {
                throw new \Exception('Tiktok channel not found');
            }

            // Get list of already synced orders
            $syncedOrderIds = TiktokIncomeDetails::find()
                ->select('order_id')
                ->column();

            $orderIds = Order::find()
                ->select('order_id')
                ->where(['channel_id' => $tiktokChannel->id])
                ->andWhere(['IS NOT', 'order_id', null])
                ->andWhere(['NOT IN', 'order_id', $syncedOrderIds])
                ->distinct()
                ->column();

            $count = 0;
            $total = count($orderIds);
            Yii::info("Found {$total} TikTok orders to sync income details", __METHOD__);

            foreach ($orderIds as $index => $order_id) {
                if (empty($order_id)) continue;

                // order_id in DB might be combined like 'ORDERID_ITEMID', but API needs pure Order ID
                // Assuming order_id in DB is the actual TikTok Order ID or contains it.
                // If unique_order_id is 'ORDERID_ITEMID', we need to extract ORDERID.
                // Based on OrderSyncService: $unique_order_id = $order_id . '_' . $item['id'];
            
            $parts = explode('_', $order_id);
            $actualOrderId = $parts[0];

                if ($this->syncOrderIncome($actualOrderId,$order_id)) {
                    $count++;
                }
                // Sleep slightly to respect rate limits
                usleep(200000); // 0.2s
                
                if ($count >= 20) {
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
            
            Yii::error('Tiktok income sync error: ' . $e->getMessage(), __METHOD__);
            return 0;
        }
    }

    /**
     * Sync income details for a specific order
     * @param string $order_id
     * @return bool
     */
    public function syncOrderIncome($order_id,$origin_order_id)
    {
        $tokenModel = TiktokToken::find()
            ->where(['status' => 'active'])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if (!$tokenModel) {
            Yii::error('No active TikTok token found', __METHOD__);
            return false;
        }

        if ($tokenModel->expires_at && strtotime($tokenModel->expires_at) < time()) {
            if ($this->refreshTikTokToken($tokenModel)) {
                // Reload model to get new token
                $tokenModel->refresh();
            } else {
                Yii::error('Failed to refresh TikTok token', __METHOD__);
                return false;
            }
        }
        
        // Ensure shop cipher is available
        if (empty($tokenModel->shop_cipher)) {
            $this->fetchShopCipher($tokenModel);
        }

        return $this->fetchAndSaveSettlementDetail($tokenModel, $order_id,$origin_order_id);
    }

    private function fetchShopCipher($tokenModel)
    {
        $timestamp = time();
        $path = '/authorization/202309/shops';

        $params = [
            'app_key' => $this->appKey,
            'timestamp' => $timestamp,
        ];

        $sign = $this->generateSign($this->appSecret, $params, $path);
        
        $url = 'https://open-api.tiktokglobalshop.com' . $path . '?' . http_build_query($params) . '&sign=' . $sign;

        try {
            $response = $this->httpClient->get($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-tts-access-token' => $tokenModel->access_token,
                ],
            ]);

            $result = Json::decode($response->getBody()->getContents());

            if (isset($result['code']) && $result['code'] !== 0) {
                Yii::error("TikTok API error fetching cipher: [{$result['code']}] " . ($result['message'] ?? ''), __METHOD__);
                return null;
            }

            if (!empty($result['data']['shops'][0]['cipher'])) {
                $shopCipher = $result['data']['shops'][0]['cipher'];
                $shopName = $result['data']['shops'][0]['name'] ?? '';

                $tokenModel->shop_cipher = $shopCipher;
                $tokenModel->shop_name = $shopName;
                $tokenModel->updated_at = date('Y-m-d H:i:s');
                $tokenModel->save(false);

                Yii::info("✅ Shop cipher updated: {$shopCipher}", __METHOD__);
                return $shopCipher;
            }
        } catch (\Exception $e) {
            Yii::error("Exception fetching shop cipher: " . $e->getMessage(), __METHOD__);
        }

        return null;
    }

    private function fetchAndSaveSettlementDetail($tokenModel, $order_id,$origin_order_id)
    {
        $accessToken = $tokenModel->access_token;
        $shopCipher = $tokenModel->shop_cipher;
        $timestamp = time();
        
        // Endpoint: /finance/202309/orders/{order_id}/statement_transactions
        $path = "/finance/202309/orders/{$order_id}/statement_transactions";
        
        $queryParams = [
            'app_key' => $this->appKey,
            'shop_cipher' => $shopCipher,
            'timestamp' => $timestamp,
        ];

        $sign = $this->generateSign($this->appSecret, $queryParams, $path);
        $queryParams['sign'] = $sign;
        $queryParams['access_token'] = $accessToken;

        $url = 'https://open-api.tiktokglobalshop.com' . $path . '?' . http_build_query($queryParams);

        try {
            $response = $this->httpClient->get($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-tts-access-token' => $accessToken,
                ],
                'timeout' => 30
            ]);

            if ($response->getStatusCode() !== 200) {
                Yii::error("HTTP Error {$response->getStatusCode()} for order: $order_id", __METHOD__);
                return false;
            }

            $body = $response->getBody()->getContents();
            $data = Json::decode($body);

            if (isset($data['code']) && $data['code'] !== 0) {
                Yii::error("TikTok API Error: {$data['code']} - " . ($data['message'] ?? 'Unknown error'), __METHOD__);
                return false;
            }

            $detail = $data['data'] ?? null;
            if (!$detail) {
                Yii::warning("No data found for order: $order_id", __METHOD__);
                return false;
            }

            Yii::info("TikTok Income Detail for $order_id: " . Json::encode($detail), __METHOD__);

            // Save to database
            // Check if exists by order_id (TikTok's order ID)
            $model = TiktokIncomeDetails::findOne(['order_id' => $order_id]);
            if (!$model) {
                $model = new TiktokIncomeDetails();
                $model->order_id = $order_id;
                $model->created_at = date('Y-m-d H:i:s');
            }

            // Fetch order_date from Order table
            // Note: order_id in TiktokIncomeDetails is the pure order ID.
            // Order table might have order_id as pure ID or combined (ID_ITEMID). 
            $order = Order::find()
                ->where(
                    ['order_id' => $origin_order_id]
                )
                ->one();
                
            if ($order) {
                $model->order_date = $order->order_date;
            }

            $model->settlement_amount = $detail['settlement_amount'] ?? 0;
            $model->revenue_amount = $detail['revenue_amount'] ?? 0;
            $model->shipping_cost_amount = $detail['shipping_cost_amount'] ?? 0;
            $model->fee_and_tax_amount = $detail['fee_and_tax_amount'] ?? 0;
            $model->adjustment_amount = $detail['adjustment_amount'] ?? 0;
            // Currency is usually inside one of the amount objects, e.g. settlement_amount_currency
            // But the structure might be simple float or object. 
            // Based on docs, amounts are usually strings/numbers. 
            // If they are objects {amount: "10.00", currency: "THB"}, we need to parse.
            // Let's assume for now they might be simple values or we check the log later.
            // Actually, usually TikTok API returns { "currency": "THB", "value": "100.00" } for amounts.
            
            // Based on log, 'statement_transactions' is an array of transactions.
            // We should probably take the first one or sum them up if multiple.
            // Usually one order has one main settlement transaction unless split.
            $transaction = $detail['statement_transactions'][0] ?? [];

            // Map fields from the transaction object
            // Note: API returns strings like "138.26", "-30.74"
            $model->settlement_amount = $transaction['settlement_amount'] ?? 0;
            $model->revenue_amount = $transaction['revenue_amount'] ?? 0;
            $model->shipping_cost_amount = $transaction['shipping_cost_amount'] ?? 0;
            $model->fee_and_tax_amount = $transaction['fee_amount'] ?? 0; // 'fee_amount' seems to be the total fee
            $model->adjustment_amount = $transaction['adjustment_amount'] ?? 0;
            
            // Map additional fields
            $model->actual_shipping_fee_amount = $transaction['actual_shipping_fee_amount'] ?? 0;
            $model->affiliate_commission_amount = $transaction['affiliate_commission_amount'] ?? 0;
            $model->customer_payment_amount = $transaction['customer_payment_amount'] ?? 0;
            $model->customer_refund_amount = $transaction['customer_refund_amount'] ?? 0;
            $model->gross_sales_amount = $transaction['gross_sales_amount'] ?? 0;
            $model->gross_sales_refund_amount = $transaction['gross_sales_refund_amount'] ?? 0;
            $model->net_sales_amount = $transaction['net_sales_amount'] ?? 0;
            $model->platform_commission_amount = $transaction['platform_commission_amount'] ?? 0;
            $model->platform_discount_amount = $transaction['platform_discount_amount'] ?? 0;
            $model->platform_discount_refund_amount = $transaction['platform_discount_refund_amount'] ?? 0;
            $model->platform_shipping_fee_discount_amount = $transaction['platform_shipping_fee_discount_amount'] ?? 0;
            $model->sales_tax_amount = $transaction['sales_tax_amount'] ?? 0;
            $model->sales_tax_payment_amount = $transaction['sales_tax_payment_amount'] ?? 0;
            $model->sales_tax_refund_amount = $transaction['sales_tax_refund_amount'] ?? 0;
            $model->shipping_fee_amount = $transaction['shipping_fee_amount'] ?? 0;
            $model->shipping_fee_subsidy_amount = $transaction['shipping_fee_subsidy_amount'] ?? 0;
            $model->transaction_fee_amount = $transaction['transaction_fee_amount'] ?? 0;
            
            $model->currency = $transaction['currency'] ?? 'THB';

            $model->statement_transactions = $detail['statement_transactions'] ?? [];
            // sku_statement_transactions is inside each transaction object in the log
            $model->sku_transactions = $transaction['sku_statement_transactions'] ?? [];
            
            $model->updated_at = date('Y-m-d H:i:s');

            if ($model->save()) {
                Yii::info("Saved TikTok income details for order: $order_id", __METHOD__);
                return true;
            } else {
                Yii::error("Failed to save TikTok income details for order $order_id: " . Json::encode($model->errors), __METHOD__);
                return false;
            }

        } catch (\Exception $e) {
            Yii::error("Exception fetching TikTok income for order $order_id: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }
    
    private function parseAmount($value) {
        if (is_array($value)) {
            return $value['value'] ?? $value['amount'] ?? 0;
        }
        return $value;
    }
    
    private function parseCurrency($value) {
        if (is_array($value)) {
            return $value['currency'] ?? 'THB';
        }
        return 'THB';
    }

    private function generateSign($appSecret, $params, $path)
    {
        ksort($params);
        $stringToSign = $appSecret . $path;
        foreach ($params as $key => $value) {
            $stringToSign .= $key . $value;
        }
        $stringToSign .= $appSecret;
        return hash_hmac('sha256', $stringToSign, $appSecret);
    }

    private function refreshTikTokToken($tokenModel)
    {
        try {
            $refreshToken = $tokenModel->refresh_token;
            $params = [
                'app_key' => $this->appKey,
                'app_secret' => $this->appSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ];

            $url = 'https://auth.tiktok-shops.com/api/v2/token/refresh?' . http_build_query($params);
            $response = $this->httpClient->get($url, ['timeout' => 30]);
            
            $data = Json::decode($response->getBody()->getContents());
            
            if (isset($data['data']['access_token'])) {
                $tokenModel->access_token = $data['data']['access_token'];
                $tokenModel->refresh_token = $data['data']['refresh_token'];
                $tokenModel->expires_at = date('Y-m-d H:i:s', time() + $data['data']['access_token_expire_in']);
                $tokenModel->updated_at = date('Y-m-d H:i:s');
                $tokenModel->save();
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Yii::error("Failed to refresh TikTok token: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }
}
