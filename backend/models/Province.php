<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "province".
 *
 * @property int $PROVINCE_ID
 * @property string $PROVINCE_CODE
 * @property string $PROVINCE_NAME
 * @property int $GEO_ID
 */
class Province extends \common\models\Province
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'province';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['PROVINCE_CODE', 'PROVINCE_NAME'], 'required'],
            [['GEO_ID'], 'integer'],
            [['PROVINCE_CODE'], 'string', 'max' => 2],
            [['PROVINCE_NAME'], 'string', 'max' => 150],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'PROVINCE_ID' => 'Province ID',
            'PROVINCE_CODE' => 'Province Code',
            'PROVINCE_NAME' => 'Province Name',
            'GEO_ID' => 'Geo ID',
        ];
    }

    public static function findProvinceName($id)
    {
        $model = Province::find()->where(['PROVINCE_ID' => $id])->one();
        return $model != null ? $model->PROVINCE_NAME : '';
    }
}
