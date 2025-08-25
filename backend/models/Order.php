<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "order".
 *
 * @property int $id
 * @property string $order_id
 * @property int $channel_id
 * @property string|null $sku
 * @property string $product_name
 * @property string|null $product_detail
 * @property int $quantity
 * @property float $price
 * @property float $total_amount
 * @property string $order_date
 * @property int $created_at
 * @property int $updated_at
 *
 * @property OnlineChannel $channel
 */
class Order extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'order';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['order_id', 'channel_id', 'product_name', 'price', 'total_amount', 'order_date'], 'required'],
            [['channel_id', 'quantity',], 'integer'],
            [['product_detail'], 'string'],
            [['price', 'total_amount'], 'number'],
            [['order_date', 'created_at', 'updated_at'], 'safe'],
            [['order_id', 'sku'], 'string', 'max' => 100],
            [['product_name'], 'string', 'max' => 255],
            [['order_id'], 'unique'],
            [['quantity'], 'default', 'value' => 1],
            [['channel_id'], 'exist', 'skipOnError' => true, 'targetClass' => OnlineChannel::class, 'targetAttribute' => ['channel_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'เลขที่คำสั่งซื้อ',
            'channel_id' => 'ช่องทางการขาย',
            'sku' => 'SKU',
            'product_name' => 'ชื่อสินค้า',
            'product_detail' => 'รายละเอียดสินค้า',
            'quantity' => 'จำนวน',
            'price' => 'ราคาต่อหน่วย',
            'total_amount' => 'ยอดรวม',
            'order_date' => 'วันที่สั่งซื้อ',
            'created_at' => 'วันที่ดึงข้อมูล',
            'updated_at' => 'วันที่อัพเดท',
        ];
    }

    public function getChannel()
    {
        return $this->hasOne(OnlineChannel::class, ['id' => 'channel_id']);
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // คำนวณยอดรวมอัตโนมัติ
            $this->total_amount = $this->price * $this->quantity;
            $this->created_at = date('Y-m-d H:i:s');
            $this->updated_at = date('Y-m-d H:i:s');
            return true;
        }
        return false;
    }
}