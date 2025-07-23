<?php
namespace console\controllers;

use yii\console\Controller;
use backend\models\TiktokToken;
use Yii;

class TiktokController extends Controller
{
    public function actionRefreshToken()
    {
        $tokens = TiktokToken::find()->all();

        foreach ($tokens as $token) {
            $response = $this->refreshTokenRequest($token->refresh_token);

            if (isset($response['data']['access_token'])) {
                TiktokToken::saveToken(
                    $token->shop_id,
                    $response['data']['access_token'],
                    $response['data']['refresh_token'],
                    $response['data']['expires_in']
                );
                echo "Token refreshed for shop: {$token->shop_id}\n";
            }
        }
    }

    private function refreshTokenRequest($refreshToken)
    {
        $appKey = 'YOUR_APP_KEY';
        $appSecret = 'YOUR_APP_SECRET';
        $url = "https://open-api.tiktokglobalshop.com/auth/token/refresh";

        $params = [
            'app_key' => $appKey,
            'app_secret' => $appSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken
        ];

        $client = new \GuzzleHttp\Client();
        $res = $client->post($url, ['form_params' => $params]);
        return json_decode($res->getBody(), true);
    }

    public function actionFetchOrders()
    {
        $shopId = 'YOUR_SHOP_ID';
        $accessToken = TiktokToken::getValidToken($shopId);
        if (!$accessToken) {
            echo "Token invalid or expired.\n";
            return;
        }

        $url = "https://open-api.tiktokglobalshop.com/order/list";
        $params = [
            'shop_id' => $shopId,
            'create_time_from' => strtotime('-1 day'),
            'create_time_to' => time(),
            'page_size' => 20
        ];

        $client = new \GuzzleHttp\Client();
        $res = $client->post($url, [
            'headers' => ['Access-Token' => $accessToken],
            'form_params' => $params
        ]);

        $orders = json_decode($res->getBody(), true);
        print_r($orders);
    }

}
