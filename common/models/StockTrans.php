<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "stock_trans".
 *
 * @property int $id
 * @property int|null $journal_trans_id
 * @property string|null $trans_date
 * @property int|null $product_id
 * @property int|null $trans_type_id
 * @property float|null $qty
 * @property int|null $created_at
 * @property int|null $created_by
 * @property int|null $status
 * @property string|null $remark
 * @property int|null $stock_type_id
 */
class StockTrans extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'stock_trans';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['journal_trans_id', 'product_id', 'trans_type_id', 'created_at', 'created_by', 'status', 'stock_type_id','warehouse_id'], 'integer'],
            [['trans_date'], 'safe'],
            [['qty'], 'number'],
            [['remark'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'journal_trans_id' => 'เลขที่อ้างอิง',
            'trans_date' => 'วันที่ทำรายการ',
            'product_id' => 'สินค้า',
            'trans_type_id' => 'ประเภทการทำรายการ',
            'qty' => 'จำนวน',
            'created_at' => 'Created At',
            'created_by' => 'ผู้ดำเนินการ',
            'status' => 'สถานะ',
            'remark' => 'Remark',
            'stock_type_id' => 'ประเภทสต็อก',
            'warehouse_id' => 'คลังจัดเก็บ',
        ];
    }
}
