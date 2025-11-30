<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "sync_log".
 *
 * @property int $id
 * @property string $type Type of sync: order, income
 * @property string $platform Platform: tiktok, shopee
 * @property string|null $start_time
 * @property string|null $end_time
 * @property int|null $status 0: Pending, 1: Success, 2: Failed
 * @property int|null $total_records
 * @property string|null $message
 * @property string|null $created_at
 */
class SyncLog extends \yii\db\ActiveRecord
{
    const STATUS_PENDING = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 2;

    const TYPE_ORDER = 'order';
    const TYPE_INCOME = 'income';

    const PLATFORM_TIKTOK = 'tiktok';
    const PLATFORM_SHOPEE = 'shopee';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'sync_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'platform'], 'required'],
            [['start_time', 'end_time', 'created_at'], 'safe'],
            [['status', 'total_records'], 'integer'],
            [['message'], 'string'],
            [['type', 'platform'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'platform' => 'Platform',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'status' => 'Status',
            'total_records' => 'Total Records',
            'message' => 'Message',
            'created_at' => 'Created At',
        ];
    }
}
