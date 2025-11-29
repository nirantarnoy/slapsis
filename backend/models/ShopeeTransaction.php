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

    // Granular Fee Categories
    const CATEGORY_AMS_COMMISSION = 'AMS_COMMISSION'; // Affiliate Marketing Solutions
    const CATEGORY_AFFILIATE_COMMISSION = 'AFFILIATE_COMMISSION'; // General Affiliate
    const CATEGORY_CCB_SERVICE_FEE = 'CCB_SERVICE_FEE'; // Coins Cashback
    const CATEGORY_FSS_SERVICE_FEE = 'FSS_SERVICE_FEE'; // Free Shipping Special
    const CATEGORY_VOUCHER_CODE = 'VOUCHER_CODE'; // Voucher usage
    const CATEGORY_SHIPPING_DISCOUNT = 'SHIPPING_DISCOUNT'; // Shipping discount support

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