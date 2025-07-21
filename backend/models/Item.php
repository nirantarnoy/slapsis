<?php

namespace backend\models;

use common\models\LoginLog;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

date_default_timezone_set('Asia/Bangkok');

class Item extends \common\models\Item
{
    public function rules()
    {
        return [
            [['status', 'created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['name', 'description'], 'string', 'max' => 255],
        ];
    }

//    public function behaviors()
//    {
//        return [
//            'timestampcdate'=>[
//                'class'=> \yii\behaviors\AttributeBehavior::className(),
//                'attributes'=>[
//                    ActiveRecord::EVENT_BEFORE_INSERT=>'created_at',
//                ],
//                'value'=> time(),
//            ],
//            'timestampudate'=>[
//                'class'=> \yii\behaviors\AttributeBehavior::className(),
//                'attributes'=>[
//                    ActiveRecord::EVENT_BEFORE_INSERT=>'updated_at',
//                ],
//                'value'=> time(),
//            ],
////            'timestampcby'=>[
////                'class'=> \yii\behaviors\AttributeBehavior::className(),
////                'attributes'=>[
////                    ActiveRecord::EVENT_BEFORE_INSERT=>'created_by',
////                ],
////                'value'=> Yii::$app->user->identity->id,
////            ],
////            'timestamuby'=>[
////                'class'=> \yii\behaviors\AttributeBehavior::className(),
////                'attributes'=>[
////                    ActiveRecord::EVENT_BEFORE_UPDATE=>'updated_by',
////                ],
////                'value'=> Yii::$app->user->identity->id,
////            ],
//            'timestampupdate'=>[
//                'class'=> \yii\behaviors\AttributeBehavior::className(),
//                'attributes'=>[
//                    ActiveRecord::EVENT_BEFORE_UPDATE=>'updated_at',
//                ],
//                'value'=> time(),
//            ],
//        ];
//    }

    public static function findName($id)
    {
        $model = Item::find()->where(['id' => $id])->one();
        return $model != null ? $model->name : '';
    }
    public static function findDescription($id)
    {
        $model = Item::find()->where(['id' => $id])->one();
        return $model != null ? $model->description : '';
    }



}
