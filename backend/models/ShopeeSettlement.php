<?php
/**
 * Model Class สำหรับตาราง shopee_settlement
 *
 * บันทึกไฟล์นี้ที่: models/ShopeeSettlement.php
 */
namespace backend\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Json;

/**
 * This is the model class for table "shopee_settlement".
 *
 * @property int $id
 * @property string $settlement_id
 * @property int $channel_id
 * @property string $shop_id
 * @property float $settlement_amount
 * @property float $settlement_fee
 * @property float $net_settlement_amount
 * @property string|null $status
 * @property string|null $payment_method
 * @property string|null $bank_account
 * @property string $payout_time
 * @property string|null $settlement_period_from
 * @property string|null $settlement_period_to
 * @property int $order_count
 * @property float $total_sales
 * @property float $total_commission
 * @property float $total_transaction_fee
 * @property float $total_refund
 * @property string|null $details
 * @property string|null $remark
 * @property string $created_at
 * @property string $updated_at
 *
 * @property OnlineChannel $channel
 */
class ShopeeSettlement extends ActiveRecord
{
    /**
     * Status constants
     */
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_PENDING = 'PENDING';
    const STATUS_PROCESSING = 'PROCESSING';
    const STATUS_FAILED = 'FAILED';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'shopee_settlement';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['settlement_id', 'channel_id', 'shop_id', 'settlement_amount', 'net_settlement_amount', 'payout_time', 'created_at', 'updated_at'], 'required'],
            [['channel_id', 'order_count'], 'integer'],
            [['settlement_amount', 'settlement_fee', 'net_settlement_amount', 'total_sales', 'total_commission', 'total_transaction_fee', 'total_refund'], 'number'],
            [['payout_time', 'settlement_period_from', 'settlement_period_to', 'created_at', 'updated_at'], 'safe'],
            [['details'], 'string'],
            [['settlement_id', 'bank_account'], 'string', 'max' => 100],
            [['shop_id', 'status', 'payment_method'], 'string', 'max' => 50],
            [['remark'], 'string', 'max' => 255],
            [['settlement_id'], 'unique'],
            [['channel_id'], 'exist', 'skipOnError' => true, 'targetClass' => OnlineChannel::class, 'targetAttribute' => ['channel_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'settlement_id' => 'Settlement ID',
            'channel_id' => 'Channel ID',
            'shop_id' => 'Shop ID',
            'settlement_amount' => 'Settlement Amount',
            'settlement_fee' => 'Settlement Fee',
            'net_settlement_amount' => 'Net Settlement Amount',
            'status' => 'Status',
            'payment_method' => 'Payment Method',
            'bank_account' => 'Bank Account',
            'payout_time' => 'Payout Time',
            'settlement_period_from' => 'Settlement Period From',
            'settlement_period_to' => 'Settlement Period To',
            'order_count' => 'Order Count',
            'total_sales' => 'Total Sales',
            'total_commission' => 'Total Commission',
            'total_transaction_fee' => 'Total Transaction Fee',
            'total_refund' => 'Total Refund',
            'details' => 'Details',
            'remark' => 'Remark',
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
     * Get status list
     *
     * @return array
     */
    public static function getStatusList()
    {
        return [
            self::STATUS_COMPLETED => 'เสร็จสิ้น',
            self::STATUS_PENDING => 'รอดำเนินการ',
            self::STATUS_PROCESSING => 'กำลังดำเนินการ',
            self::STATUS_FAILED => 'ล้มเหลว',
        ];
    }

    /**
     * Get details as array
     *
     * @return array
     */
    public function getDetailsArray()
    {
        if (empty($this->details)) {
            return [];
        }
        return Json::decode($this->details);
    }

    /**
     * Set details from array
     *
     * @param array $data
     */
    public function setDetailsArray($data)
    {
        $this->details = Json::encode($data);
    }

    /**
     * Calculate fee percentage
     *
     * @return float
     */
    public function getFeePercentage()
    {
        if ($this->settlement_amount <= 0) {
            return 0;
        }
        return ($this->settlement_fee / $this->settlement_amount) * 100;
    }

    /**
     * Get net income percentage
     *
     * @return float
     */
    public function getNetIncomePercentage()
    {
        if ($this->total_sales <= 0) {
            return 0;
        }
        return ($this->net_settlement_amount / $this->total_sales) * 100;
    }
}