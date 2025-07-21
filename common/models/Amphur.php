<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "amphur".
 *
 * @property int $AMPHUR_ID
 * @property string $AMPHUR_CODE
 * @property string $AMPHUR_NAME
 * @property string $POSTCODE
 * @property int $GEO_ID
 * @property int $PROVINCE_ID
 */
class Amphur extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'amphur';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['AMPHUR_CODE', 'AMPHUR_NAME', 'POSTCODE'], 'required'],
            [['GEO_ID', 'PROVINCE_ID'], 'integer'],
            [['AMPHUR_CODE'], 'string', 'max' => 4],
            [['AMPHUR_NAME'], 'string', 'max' => 150],
            [['POSTCODE'], 'string', 'max' => 5],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'AMPHUR_ID' => 'Amphur ID',
            'AMPHUR_CODE' => 'Amphur Code',
            'AMPHUR_NAME' => 'Amphur Name',
            'POSTCODE' => 'Postcode',
            'GEO_ID' => 'Geo ID',
            'PROVINCE_ID' => 'Province ID',
        ];
    }
}
