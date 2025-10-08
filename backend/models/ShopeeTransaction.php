<?php
namespace backend\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "shopee_transaction".
 *
 * @property int $id
 * @property string $transaction_id
 * @property int $channel_id
 * @property string $shop_id
 * @property string|null $transaction_type
 * @property string|null $status
 * @property string|null $reason
 * @property float $amount
 * @property float|null $current_balance
 * @property string|null $order_sn
 * @property string|null $fee_category
 * @property string $transaction_date
 * @property string $created_at
 * @property string $updated_at
 *
 * @property OnlineChannel $channel
 * @property Order $order
 */
class ShopeeTransaction extends ActiveRecord
{
    /**
     * Fee categories constants
     */
    const CATEGORY_INCOME = 'INCOME';
    const CATEGORY_COMMISSION_FEE = 'COMMISSION_FEE';
    const CATEGORY_TRANSACTION_FEE = 'TRANSACTION_FEE';
    const CATEGORY_SERVICE_FEE = 'SERVICE_FEE';
    const CATEGORY_SHIPPING_FEE = 'SHIPPING_FEE';
    const CATEGORY_CAMPAIGN_FEE = 'CAMPAIGN_FEE';
    const CATEGORY_PENALTY_FEE = 'PENALTY_FEE';
    const CATEGORY_REFUND = 'REFUND';
    const CATEGORY_ADJUSTMENT = 'ADJUSTMENT';
    const CATEGORY_WITHDRAWAL = 'WITHDRAWAL';
    const CATEGORY_OTHER = 'OTHER';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'shopee_transaction';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['transaction_id', 'channel_id', 'shop_id', 'amount', 'transaction_date', 'created_at', 'updated_at'], 'required'],
            [['channel_id'], 'integer'],
            [['amount', 'current_balance'], 'number'],
            [['transaction_date', 'created_at', 'updated_at'], 'safe'],
            [['transaction_id'], 'string', 'max' => 100],
            [['shop_id', 'transaction_type', 'status', 'order_sn', 'fee_category'], 'string', 'max' => 50],
            [['reason'], 'string', 'max' => 255],
            [['transaction_id'], 'unique'],
            [['channel_id'], 'exist', 'skipOnError' => true, 'targetClass' => OnlineChannel::class, 'targetAttribute' => ['channel_id' => 'id']],
            [['order_sn'], 'exist', 'skipOnError' => true, 'targetClass' => Order::class, 'targetAttribute' => ['order_sn' => 'order_sn']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'transaction_id' => 'Transaction ID',
            'channel_id' => 'Channel ID',
            'shop_id' => 'Shop ID',
            'transaction_type' => 'Transaction Type',
            'status' => 'Status',
            'reason' => 'Reason',
            'amount' => 'Amount',
            'current_balance' => 'Current Balance',
            'order_sn' => 'Order SN',
            'fee_category' => 'Fee Category',
            'transaction_date' => 'Transaction Date',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Channel]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChannel()
    {
        return $this->hasOne(OnlineChannel::class, ['id' => 'channel_id']);
    }

    /**
     * Gets query for [[Order]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::class, ['order_sn' => 'order_sn']);
    }

    /**
     * Get all fee categories
     *
     * @return array
     */
    public static function getFeeCategoryList()
    {
        return [
            self::CATEGORY_INCOME => 'รายได้',
            self::CATEGORY_COMMISSION_FEE => 'ค่าคอมมิชชัน',
            self::CATEGORY_TRANSACTION_FEE => 'ค่าธรรมเนียมธุรกรรม',
            self::CATEGORY_SERVICE_FEE => 'ค่าบริการ',
            self::CATEGORY_SHIPPING_FEE => 'ค่าขนส่ง',
            self::CATEGORY_CAMPAIGN_FEE => 'ค่าโฆษณา/แคมเปญ',
            self::CATEGORY_PENALTY_FEE => 'ค่าปรับ',
            self::CATEGORY_REFUND => 'เงินคืน',
            self::CATEGORY_ADJUSTMENT => 'ปรับปรุง',
            self::CATEGORY_WITHDRAWAL => 'ถอนเงิน',
            self::CATEGORY_OTHER => 'อื่นๆ',
        ];
    }

    /**
     * Check if transaction is expense (negative amount)
     *
     * @return bool
     */
    public function isExpense()
    {
        return $this->amount < 0;
    }

    /**
     * Get absolute amount
     *
     * @return float
     */
    public function getAbsAmount()
    {
        return abs($this->amount);
    }
}