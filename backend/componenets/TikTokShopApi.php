<?php

namespace backend\components;

use yii\base\Component;
use yii\httpclient\Client;

class TikTokShopApi extends Component
{
    public $appKey;
    public $appSecret;
    public $accessToken;
    public $shopId;
    public $apiUrl = 'https://open-api.tiktokglobalshop.com';

    private $httpClient;

    public function init()
    {
        parent::init();
        $this->httpClient = new Client([
            'baseUrl' => $this->apiUrl,
        ]);
    }

    /**
     * Get orders from TikTok Shop
     * @param array $params
     * @return array
     */
    public function getOrders($params = [])
    {
        $defaultParams = [
            'page_size' => 50,
            'page' => 1,
            'order_status' => 'UNPAID,PROCESSING,COMPLETED',
        ];

        $params = array_merge($defaultParams, $params);

        try {
            $response = $this->request('/api/orders/search', 'GET', $params);

            if ($response['code'] == 0) {
                return $response['data']['orders'] ?? [];
            }

            throw new \Exception($response['message'] ?? 'Unknown error');

        } catch (\Exception $e) {
            \Yii::error('TikTok Shop API Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Make API request
     * @param string $path
     * @param string $method
     * @param array $params
     * @return array
     */
    private function request($path, $method = 'GET', $params = [])
    {
        $timestamp = time();
        $sign = $this->generateSign($path, $params, $timestamp);

        $headers = [
            'x-tts-access-token' => $this->accessToken,
            'x-tts-app-key' => $this->appKey,
            'x-tts-timestamp' => $timestamp,
            'x-tts-sign' => $sign,
        ];

        $request = $this->httpClient->createRequest()
            ->setMethod($method)
            ->setUrl($path)
            ->setHeaders($headers);

        if ($method === 'GET') {
            $request->setData($params);
        } else {
            $request->setContent(json_encode($params));
        }

        $response = $request->send();

        return $response->data;
    }

    /**
     * Generate signature for API request
     * @param string $path
     * @param array $params
     * @param int $timestamp
     * @return string
     */
    private function generateSign($path, $params, $timestamp)
    {
        $signString = $this->appKey . $path . $timestamp;

        if (!empty($params)) {
            ksort($params);
            $signString .= json_encode($params);
        }

        return hash_hmac('sha256', $signString, $this->appSecret);
    }
}