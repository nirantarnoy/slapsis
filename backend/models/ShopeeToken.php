<?php

namespace backend\models;

use Yii;
use yii\db\ActiveRecord;

class ShopeeToken extends ActiveRecord
{
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_REVOKED = 'revoked';

    public static function tableName()
    {
        return 'shopee_tokens';
    }

    public function rules()
    {
        return [
            [['shop_id', 'access_token'], 'required'],
            [['expire_in',], 'integer'],
            [['created_at', 'updated_at','expires_at'],'safe'],
            [['shop_id', 'access_token', 'refresh_token'], 'string', 'max' => 500],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_EXPIRED, self::STATUS_REVOKED]],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'shop_id' => 'Shop ID',
            'access_token' => 'Access Token',
            'refresh_token' => 'Refresh Token',
            'expire_in' => 'Expires In',
            'status' => 'Status',
            'expires_at'=>'Expires At',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getChannel()
    {
        return $this->hasOne(OnlineChannel::class, ['id' => 'channel_id']);
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $now = date('Y-m-d H:i:s');
            if ($insert) {
                $this->created_at = $now;
                if (empty($this->status)) {
                    $this->status = self::STATUS_ACTIVE;
                }
            }
            $this->updated_at = $now;
            return true;
        }
        return false;
    }

    /**
     * Check if token is expired
     * @return bool
     */
    public function isExpired()
    {
        if (empty($this->expires_at)) {
            return true;
        }
        return strtotime($this->expires_at) < time();
    }
}
