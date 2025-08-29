<?php
namespace backend\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\BlameableBehavior;
use yii\web\UploadedFile;

/**
 * Expense model
 *
 * @property int $id
 * @property string $category
 * @property string $description
 * @property string $expense_date
 * @property float $amount
 * @property string $receipt_file
 * @property string $status
 * @property int $created_by
 * @property int $created_at
 * @property int $updated_at
 */
class Expense extends ActiveRecord
{
    public $receiptUpload;

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    public static function tableName()
    {
        return '{{%expenses}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
            [
                'class' => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => false,
            ],
        ];
    }

    public function rules()
    {
        return [
            [['category', 'expense_date', 'amount'], 'required'],
            [['description'], 'string'],
            [['expense_date'], 'date', 'format' => 'yyyy-MM-dd'],
            [['amount'], 'number', 'min' => 0],
            [['created_by'], 'integer'],
            [['category'], 'string', 'max' => 100],
            [['receipt_file'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            [['receiptUpload'], 'file', 'extensions' => 'png, jpg, jpeg, pdf', 'maxSize' => 1024 * 1024 * 5], // 5MB
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category' => 'หัวข้อการบันทึก',
            'description' => 'รายละเอียด',
            'expense_date' => 'วันที่',
            'amount' => 'จำนวนเงิน (บาท)',
            'receipt_file' => 'ไฟล์ใบเสร็จ',
            'receiptUpload' => 'อัพโหลดใบเสร็จ',
            'status' => 'สถานะ',
            'created_by' => 'ผู้สร้าง',
            'created_at' => 'วันที่สร้าง',
            'updated_at' => 'วันที่แก้ไข',
        ];
    }

    public function getStatusOptions()
    {
        return [
            self::STATUS_ACTIVE => 'ใช้งาน',
            self::STATUS_INACTIVE => 'ไม่ใช้งาน',
        ];
    }

    public function getCategoryOptions()
    {
        return [
            'เดินทาง' => 'เดินทาง',
            'อาหาร' => 'อาหาร',
            'เครื่องเขียน' => 'เครื่องเขียน',
            'โฆษณา' => 'โฆษณา',
            'สาธารณูปโภค' => 'สาธารณูปโภค',
            'ซ่อมแซม' => 'ซ่อมแซม',
            'เช่า' => 'เช่า',
            'อื่นๆ' => 'อื่นๆ',
        ];
    }

    public function upload()
    {
        if ($this->receiptUpload) {
            $uploadPath = Yii::getAlias('@backend/web/uploads/receipts/');
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $fileName = uniqid() . '.' . $this->receiptUpload->extension;
            $this->receiptUpload->saveAs($uploadPath . $fileName);
            $this->receipt_file = $fileName;
            return true;
        }
        return false;
    }

    public function getReceiptUrl()
    {
        if ($this->receipt_file) {
            return Yii::getAlias('@web/uploads/receipts/') . $this->receipt_file;
        }
        return null;
    }

    // Static methods สำหรับ Reports
    public static function getMonthlyExpenses($year = null, $month = null)
    {
        $year = $year ?: date('Y');
        $month = $month ?: date('m');

        return static::find()
            ->where(['YEAR(expense_date)' => $year, 'MONTH(expense_date)' => $month])
            ->andWhere(['status' => self::STATUS_ACTIVE])
            ->sum('amount') ?: 0;
    }

    public static function getExpensesByDateRange($startDate, $endDate)
    {
        return static::find()
            ->where(['between', 'expense_date', $startDate, $endDate])
            ->andWhere(['status' => self::STATUS_ACTIVE])
            ->orderBy('expense_date DESC')
            ->all();
    }

    public static function getExpensesByCategory($startDate, $endDate)
    {
        return static::find()
            ->select(['category', 'SUM(amount) as total_amount', 'COUNT(*) as count'])
            ->where(['between', 'expense_date', $startDate, $endDate])
            ->andWhere(['status' => self::STATUS_ACTIVE])
            ->groupBy('category')
            ->orderBy('total_amount DESC')
            ->asArray()
            ->all();
    }
}