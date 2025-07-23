<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "tiktok_tokens".
 *
 * @property int $id
 * @property string|null $shop_id
 * @property string|null $access_token
 * @property string|null $refresh_token
 * @property int|null $expires_in
 * @property string|null $updated_at
 */
class TiktokTokens extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tiktok_tokens';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['shop_id', 'access_token', 'refresh_token', 'expires_in', 'updated_at'], 'default', 'value' => null],
            [['access_token', 'refresh_token'], 'string'],
            [['expires_in'], 'integer'],
            [['updated_at'], 'safe'],
            [['shop_id'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'shop_id' => 'Shop ID',
            'access_token' => 'Access Token',
            'refresh_token' => 'Refresh Token',
            'expires_in' => 'Expires In',
            'updated_at' => 'Updated At',
        ];
    }

}
