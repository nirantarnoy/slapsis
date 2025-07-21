<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "product".
 *
 * @property int $id
 * @property string|null $code
 * @property string|null $name
 * @property string|null $description
 * @property int|null $status
 * @property int|null $product_group_id
 * @property int|null $unit_id
 * @property float|null $cost_price
 * @property float|null $sale_price
 * @property float|null $stock_qty
 * @property string|null $remark
 * @property string|null $photo
 * @property int|null $created_at
 * @property int|null $created_by
 * @property int|null $updated_at
 * @property int|null $updated_by
 */
class Product extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['status', 'product_category_id', 'unit_id', 'created_at', 'created_by', 'updated_at', 'updated_by','type_id','product_type_id','brand_id'], 'integer'],
            [['cost_price', 'sale_price', 'stock_qty'], 'number'],
            [['code', 'name', 'description', 'remark', 'photo'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'รหัสสินค้า',
            'name' => 'ชื่อสินค้า',
            'description' => 'รายละเอียด',
            'status' => 'สถานะ',
            'product_category_id' => 'กลุ่มสินค้า',
            'unit_id' => 'หน่วยนับ',
            'cost_price' => 'ต้นทุน',
            'sale_price' => 'ราคาขาย',
            'stock_qty' => 'จำนวนคงเหลือ',
            'remark' => 'Remark',
            'photo' => 'รูปภาพ',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'updated_at' => 'Updated At',
            'updated_by' => 'Updated By',
            'type_id' => 'สภาพสินค้า',
            'product_type_id' => 'ประเภทสินค้า',
            'brand_id' => 'ยี่ห้อ',
        ];
    }
}
