<?php

namespace backend\components;

use yii\base\Component;
use yii\httpclient\Client;

class ShopeeApi extends Component
{
    public $partnerId;
    public $partnerKey;
    public $accessToken;
    public $shopId;
    public $apiUrl = 'https://partner.shopeemobile.com';

    private $httpClient;

    public function init()
    {
        parent::init();
        $this->httpClient = new Client([
            'baseUrl' => $this->apiUrl,
        ]);
    }

    /**
     * Get orders from Shopee
     * @param array $params
     * @return array
     */
    public function getOrders($params = [])
    {
        $defaultParams = [
            'time_range_field' => 'create_time',
            'time_from' => strtotime('-7 days'),
            'time_to' => time(),
            'page_size' => 50,
            'offset' => 0,
        ];

        $params = array_merge($defaultParams, $params);

        try {
            $response = $this->request('/api/v2/order/get_order_list', $params);

            if (!isset($response['error'])) {
                return $response['response']['order_list'] ?? [];
            }

            throw new \Exception($response['message'] ?? 'Unknown error');

        } catch (\Exception $e) {
            \Yii::error('Shopee API Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get order detail
     * @param string $orderId
     * @return array
     */
    public function getOrderDetail($orderId)
    {
        $params = [
            'order_sn_list' => $orderId,
        ];

        try {
            $response = $this->request('/api/v2/order/get_order_detail', $params);

            if (!isset($response['error'])) {
                return $response['response']['order_list'][0] ?? [];
            }

            return [];

        } catch (\Exception $e) {
            \Yii::error('Shopee API Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Make API request
     * @param string $path
     * @param array $params
     * @return array
     */
    private function request($path, $params = [])
    {
        $timestamp = time();
        $sign = $this->generateSign($path, $timestamp);

        $commonParams = [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'access_token' => $this->accessToken,
            'shop_id' => $this->shopId,
            'sign' => $sign,
        ];

        $params = array_merge($params, $commonParams);

        $response = $this->httpClient->createRequest()
            ->setMethod('GET')
            ->setUrl($path)
            ->setData($params)
            ->send();

        return $response->data;
    }

    /**
     * Generate signature for API request
     * @param string $path
     * @param int $timestamp
     * @return string
     */
    private function generateSign($path, $timestamp)
    {
        $baseString = $this->partnerId . $path . $timestamp . $this->accessToken . $this->shopId;
        return hash_hmac('sha256', $baseString, $this->partnerKey);
    }
}