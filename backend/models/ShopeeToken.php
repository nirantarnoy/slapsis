<?php

namespace backend\models;

use Yii;
use yii\db\ActiveRecord;

class ShopeeToken extends ActiveRecord
{
    public static function tableName()
    {
        return 'shopee_token';
    }

    public function rules()
    {
        return [
            [['shop_id', 'access_token', 'refresh_token', 'expire_time'], 'required'],
            [['shop_id'], 'integer'],
            [['access_token', 'refresh_token'], 'string'],
            [['expire_time'], 'safe'],
        ];
    }
}
