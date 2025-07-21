<?php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;

class TiktokToken extends ActiveRecord
{
    public static function tableName()
    {
        return 'tiktok_tokens';
    }

    public static function saveToken($shopId, $accessToken, $refreshToken, $expiresIn)
    {
        $model = self::findOne(['shop_id' => $shopId]);
        if (!$model) {
            $model = new self();
            $model->shop_id = $shopId;
        }
        $model->access_token = $accessToken;
        $model->refresh_token = $refreshToken;
        $model->expires_in = $expiresIn;
        $model->updated_at = date('Y-m-d H:i:s');
        return $model->save();
    }

    public static function getValidToken($shopId)
    {
        $model = self::findOne(['shop_id' => $shopId]);
        if (!$model) return null;

        $expireAt = strtotime($model->updated_at) + $model->expires_in - 300;
        if (time() > $expireAt) {
            return false; // expired
        }
        return $model->access_token;
    }
}
