<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "journal_trans_line".
 *
 * @property int $id
 * @property int $journal_trans_id
 * @property int $product_id
 * @property int $warehouse_id
 * @property float|null $qty
 * @property string|null $remark
 * @property int|null $status
 *
 * @property JournalTrans $journalTrans
 */
class JournalTransLine extends \yii\db\ActiveRecord
{
    // Status
    const STATUS_ACTIVE = 1;
    const STATUS_CANCELLED = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'journal_trans_line';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['journal_trans_id', 'product_id', 'warehouse_id'], 'required'],
            [['journal_trans_id', 'product_id', 'warehouse_id', 'status'], 'integer'],
            [['qty'], 'number'],
            [['qty'], 'compare', 'compareValue' => 0, 'operator' => '>', 'message' => 'จำนวนต้องมากกว่า 0'],
            [['remark'], 'string', 'max' => 255],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['journal_trans_id'], 'exist', 'skipOnError' => true, 'targetClass' => JournalTrans::class, 'targetAttribute' => ['journal_trans_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'journal_trans_id' => 'Journal Trans ID',
            'product_id' => 'สินค้า',
            'warehouse_id' => 'คลังสินค้า',
            'qty' => 'จำนวน',
            'remark' => 'หมายเหตุ',
            'status' => 'สถานะ',
        ];
    }

    /**
     * Gets query for [[JournalTrans]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getJournalTrans()
    {
        return $this->hasOne(JournalTrans::class, ['id' => 'journal_trans_id']);
    }

    /**
     * Get status list
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_ACTIVE => 'ใช้งาน',
            self::STATUS_CANCELLED => 'ยกเลิก',
        ];
    }

    /**
     * Get status name
     */
    public function getStatusName()
    {
        $list = self::getStatusList();
        return isset($list[$this->status]) ? $list[$this->status] : '';
    }

    /**
     * After save event
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        // Update master total quantity
        if ($this->journalTrans) {
            $this->journalTrans->calculateTotalQty();
        }
    }

    /**
     * After delete event
     */
    public function afterDelete()
    {
        parent::afterDelete();

        // Update master total quantity
        if ($this->journalTrans) {
            $this->journalTrans->calculateTotalQty();
        }
    }
}