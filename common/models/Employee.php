<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "employee".
 *
 * @property int $id
 * @property string|null $emp_code
 * @property string|null $fname
 * @property string|null $lname
 * @property int|null $position_id
 * @property string|null $description
 * @property int|null $status
 * @property int|null $created_at
 * @property int|null $created_by
 * @property int|null $updated_at
 * @property int|null $updated_by
 */
class Employee extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'employee';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fname', 'lname'], 'required'],
            [['position_id', 'status', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['emp_code', 'fname', 'lname', 'description'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'emp_code' => 'Emp Code',
            'fname' => 'ชื่อ',
            'lname' => 'นามสกุล',
            'position_id' => 'ตำแหน่งงาน',
            'description' => 'รายละเอียด',
            'status' => 'สถานะ',
            'created_at' => 'Created At',
            'created_by' => 'Created By',
            'updated_at' => 'Updated At',
            'updated_by' => 'Updated By',
        ];
    }
}
