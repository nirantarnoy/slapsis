<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the model class for table "online_channel".
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $logo
 * @property string|null $access_token
 * @property string|null $refresh_token
 * @property string|null $app_token
 * @property int|null $status
 * @property int $created_at
 * @property int|null $created_by
 * @property int $updated_at
 * @property int|null $updated_by
 *
 * @property Order[] $orders
 */
class OnlineChannel extends \yii\db\ActiveRecord
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    public static function tableName()
    {
        return 'online_channel';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            BlameableBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['description', 'access_token', 'refresh_token'], 'string'],
            [['status', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['logo', 'app_token'], 'string', 'max' => 255],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'ชื่อช่องทาง',
            'description' => 'รายละเอียด',
            'logo' => 'โลโก้',
            'access_token' => 'Access Token',
            'refresh_token' => 'Refresh Token',
            'app_token' => 'App Token',
            'status' => 'สถานะ',
            'created_at' => 'สร้างเมื่อ',
            'created_by' => 'สร้างโดย',
            'updated_at' => 'แก้ไขเมื่อ',
            'updated_by' => 'แก้ไขโดย',
        ];
    }

    public function getOrders()
    {
        return $this->hasMany(Order::class, ['channel_id' => 'id']);
    }

    public static function getStatusList()
    {
        return [
            self::STATUS_ACTIVE => 'เปิดใช้งาน',
            self::STATUS_INACTIVE => 'ปิดใช้งาน',
        ];
    }

    public function getStatusName()
    {
        $list = self::getStatusList();
        return isset($list[$this->status]) ? $list[$this->status] : '-';
    }
}