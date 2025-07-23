<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "order".
 *
 * @property int $id
 * @property string $order_id
 * @property int $channel_id
 * @property string|null $sku
 * @property string|null $product_name
 * @property string|null $product_detail
 * @property int|null $quantity
 * @property float|null $price
 * @property float|null $total_amount
 * @property string|null $order_date
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class Order extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sku', 'product_name', 'product_detail', 'price', 'total_amount', 'order_date'], 'default', 'value' => null],
            [['quantity'], 'default', 'value' => 1],
            [['order_id', 'channel_id'], 'required'],
            [['channel_id', 'quantity'], 'integer'],
            [['product_detail'], 'string'],
            [['price', 'total_amount'], 'number'],
            [['order_date', 'created_at', 'updated_at'], 'safe'],
            [['order_id', 'sku'], 'string', 'max' => 100],
            [['product_name'], 'string', 'max' => 255],
            [['order_id', 'channel_id'], 'unique', 'targetAttribute' => ['order_id', 'channel_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'channel_id' => 'Channel ID',
            'sku' => 'Sku',
            'product_name' => 'Product Name',
            'product_detail' => 'Product Detail',
            'quantity' => 'Quantity',
            'price' => 'Price',
            'total_amount' => 'Total Amount',
            'order_date' => 'Order Date',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

}
