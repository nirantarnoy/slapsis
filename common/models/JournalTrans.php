<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;

/**
 * This is the model class for table "journal_trans".
 *
 * @property int $id
 * @property string|null $trans_date
 * @property string|null $journal_no
 * @property int $trans_type_id
 * @property int $stock_type_id
 * @property int $customer_id
 * @property string|null $customer_name
 * @property float|null $qty
 * @property string|null $remark
 * @property int|null $status
 * @property int|null $created_at
 * @property int|null $created_by
 * @property int|null $updated_at
 * @property int|null $updated_by
 * @property int|null $party_id
 * @property int|null $party_type_id
 * @property int|null $warehouse_id
 *
 * @property JournalTransLine[] $journalTransLines
 */
class JournalTrans extends \yii\db\ActiveRecord
{
    // Transaction Types

    const TYPE_OPENING = 1;     // ปรับยอดยกมา
    const TYPE_ADJUST = 2;     // ปรับยอด
    const TYPE_SALE = 3;       // ขาย
    const TYPE_RETURN_SALE = 4; // คืนขาย
    const TYPE_LOAN = 5;       // ยืม
    const TYPE_RETURN_LOAN = 6; // คืนยืม
    const TYPE_SEND = 7;       // เบิกส่งช่าง
    const TYPE_RETURN_SEND = 8; // คืนส่งช่าง
    const TYPE_DROP = 9;       // ขาย Dropship




//'1' => 'ปรับยอดยกมา',
//'2' => 'ปรับยอด',
//'3' => 'ขาย',
//'4' => 'คืนขาย',
//'5' => 'ยืม',
//'6' => 'คืนยืม',
//'7' => 'เบิกส่งช่าง',
//'8' => 'คืนส่งช่าง',
//'9' => 'ขาย Dropship',

    // Status
    const STATUS_DRAFT = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_CANCELLED = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'journal_trans';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            BlameableBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['trans_type_id', 'warehouse_id'], 'required'],
            [['trans_date'], 'safe'],
            [['trans_type_id', 'stock_type_id', 'customer_id', 'status', 'created_at', 'created_by', 'updated_at', 'updated_by', 'party_id', 'party_type_id', 'warehouse_id'], 'integer'],
            [['qty'], 'number'],
            [['journal_no', 'customer_name', 'remark'], 'string', 'max' => 255],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['trans_date'], 'default', 'value' => date('Y-m-d H:i:s')],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'trans_date' => 'วันที่ทำรายการ',
            'journal_no' => 'เลขที่เอกสาร',
            'trans_type_id' => 'ประเภทรายการ',
            'stock_type_id' => 'ประเภทสต็อก',
            'customer_id' => 'ลูกค้า',
            'customer_name' => 'ชื่อลูกค้า',
            'qty' => 'จำนวนรวม',
            'remark' => 'หมายเหตุ',
            'status' => 'สถานะ',
            'created_at' => 'สร้างเมื่อ',
            'created_by' => 'สร้างโดย',
            'updated_at' => 'แก้ไขเมื่อ',
            'updated_by' => 'แก้ไขโดย',
            'party_id' => 'ชื่อช่าง',
            'party_type_id' => 'Party Type',
            'warehouse_id' => 'คลังสินค้า',
        ];
    }

    /**
     * Gets query for [[JournalTransLines]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getJournalTransLines()
    {
        return $this->hasMany(JournalTransLine::class, ['journal_trans_id' => 'id']);
    }

    /**
     * Before save event
     */
    public function beforeSave($insert)
    {
        if ($insert) {
            $this->journal_no = $this->generateJournalNo();
        }

        return parent::beforeSave($insert);
    }

    /**
     * Generate journal number
     */
    private function generateJournalNo()
    {
        $prefix = '';
        switch ($this->trans_type_id) {
            case self::TYPE_OPENING:
                $prefix = 'OPN';
                break;
            case self::TYPE_ADJUST:
                $prefix = 'ADJ';
                break;
            case self::TYPE_SALE:
                $prefix = 'SAL';
                break;
            case self::TYPE_RETURN_SALE:
                $prefix = 'RSA';
                break;
            case self::TYPE_LOAN:
                $prefix = 'LOA';
                break;
            case self::TYPE_RETURN_LOAN:
                $prefix = 'RLO';
                break;
            case self::TYPE_SEND:
                $prefix = 'SEN';
                break;
            case self::TYPE_RETURN_SEND:
                $prefix = 'RSE';
                break;
            case self::TYPE_DROP:
                $prefix = 'DRO';
                break;

        }

        $lastRecord = self::find()
            ->where(['trans_type_id' => $this->trans_type_id])
            ->andWhere(['like', 'journal_no', $prefix . date('Ym')])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        if ($lastRecord && preg_match('/(\d+)$/', $lastRecord->journal_no, $matches)) {
            $number = intval($matches[1]) + 1;
        } else {
            $number = 1;
        }

        return $prefix . date('Ym') . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get transaction type list
     */
    public static function getTransactionTypeList()
    {
        return [
            self::TYPE_OPENING => 'ปรับยอดยกมา',
            self::TYPE_ADJUST => 'ปรับยอด',
            self::TYPE_SALE => 'ขาย',
            self::TYPE_RETURN_SALE => 'คืนขาย',
            self::TYPE_LOAN => 'ยืม',
            self::TYPE_RETURN_LOAN => 'คืนยืม',
            self::TYPE_SEND => 'ส่งช่าง',
            self::TYPE_RETURN_SEND => 'คืนส่งช่าง',
            self::TYPE_DROP => 'ขาย Drop Ship',
        ];
    }

    /**
     * Get transaction type name
     */
    public function getTransactionTypeName()
    {
        $list = self::getTransactionTypeList();
        return isset($list[$this->trans_type_id]) ? $list[$this->trans_type_id] : '';
    }

    /**
     * Get status list
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_DRAFT => 'แบบร่าง',
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
     * Calculate total quantity from lines
     */
    public function calculateTotalQty()
    {
        $total = 0;
        foreach ($this->journalTransLines as $line) {
            if ($line->status == JournalTransLine::STATUS_ACTIVE) {
                $total += $line->qty;
            }
        }
        $this->qty = $total;
        $this->save(false, ['qty']);
    }
}