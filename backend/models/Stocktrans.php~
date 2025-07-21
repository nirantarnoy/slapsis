<?php

namespace backend\models;

use Yii;
use yii\db\ActiveRecord;

date_default_timezone_set('Asia/Bangkok');

class Stocktrans extends \common\models\StockTrans
{
    public function behaviors()
    {
        return [
            'timestampcdate' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'created_at',
                ],
                'value' => time(),
            ],
//            'timestampudate' => [
//                'class' => \yii\behaviors\AttributeBehavior::className(),
//                'attributes' => [
//                    ActiveRecord::EVENT_BEFORE_INSERT => 'updated_at',
//                ],
//                'value' => time(),
//            ],
            'timestampcby' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'created_by',
                ],
                'value' => Yii::$app->user->id,
            ],
//            'timestamuby' => [
//                'class' => \yii\behaviors\AttributeBehavior::className(),
//                'attributes' => [
//                    ActiveRecord::EVENT_BEFORE_UPDATE => 'updated_by',
//                ],
//                'value' => Yii::$app->user->id,
//            ],
//            'timestampcompany' => [
//                'class' => \yii\behaviors\AttributeBehavior::className(),
//                'attributes' => [
//                    ActiveRecord::EVENT_BEFORE_INSERT => 'company_id',
//                ],
//                'value' => isset($_SESSION['user_company_id']) ? $_SESSION['user_company_id'] : 1,
//            ],
//            'timestampbranch' => [
//                'class' => \yii\behaviors\AttributeBehavior::className(),
//                'attributes' => [
//                    ActiveRecord::EVENT_BEFORE_INSERT => 'branch_id',
//                ],
//                'value' => isset($_SESSION['user_branch_id']) ? $_SESSION['user_branch_id'] : 1,
//            ],
//            'timestampupdate' => [
//                'class' => \yii\behaviors\AttributeBehavior::className(),
//                'attributes' => [
//                    ActiveRecord::EVENT_BEFORE_UPDATE => 'updated_at',
//                ],
//                'value' => time(),
//            ],
        ];
    }

    public static function findName($id){
        $model = Warehouse::find()->where(['id'=>$id])->one();
        return $model != null ?$model->name:'';
    }
    public static function findDesc($id){
        $model = Warehouse::find()->where(['id'=>$id])->one();
        return $model != null ?$model->description:'';
    }

//    public static function findName($id){
//        $model = \common\models\RoutePlan::find()->where(['id'=>$id])->one();
//        return $model!= null?$model->name:'';
//    }
//    public function findUnitid($code){
//        $model = Unit::find()->where(['name'=>$code])->one();
//        return count($model)>0?$model->id:0;
//    }

    public static function getIssueLastNo()
    {
        $model = Stocktrans::find()->where(['activity_type_id' => 5])->MAX('journal_no');

        $pre = "IS";

        if ($model != null) {
//            $prefix = $pre.substr(date("Y"),2,2);
//            $cnum = substr((string)$model,4,strlen($model));
//            $len = strlen($cnum);
//            $clen = strlen($cnum + 1);
//            $loop = $len - $clen;
            $prefix = $pre . '-' . substr(date("Y"), 2, 2);
            $cnum = substr((string)$model, 5, strlen($model));
            $len = strlen($cnum);
            $clen = strlen($cnum + 1);
            $loop = $len - $clen;
            for ($i = 1; $i <= $loop; $i++) {
                $prefix .= "0";
            }
            $prefix .= $cnum + 1;
            return $prefix;
        } else {
            $prefix = $pre . '-' . substr(date("Y"), 2, 2);
            return $prefix . '00001';
        }
    }

    public static function getRecieveLastNo()
    {
        $model = Stocktrans::find()->where(['trans_module_id' => 3])->MAX('journal_no');
        $pre = "RC";
        if ($model != null) {
            $prefix = $pre . '-' . substr(date("Y"), 2, 2);
            $cnum = substr((string)$model, 5, 5);
            $len = strlen($cnum);
            $clen = strlen($cnum + 1);
            $loop = $len - $clen;
            for ($i = 1; $i <= $loop; $i++) {
                $prefix .= "0";
            }
            $prefix .= $cnum + 1;
            return $prefix;
        } else {
            $prefix = $pre . '-' . substr(date("Y"), 2, 2) ;
            return $prefix . '00001';
        }
    }
//    public static function getIssueLastNo()
//    {
//        $model = Stocktrans::find()->where(['trans_module_id' => 4])->MAX('trans_no');
//        $pre = "IS";
//        if ($model != null) {
//            $prefix = $pre . '-' . substr(date("Y"), 2, 2);
//            $cnum = substr((string)$model, 5, 5);
//            $len = strlen($cnum);
//            $clen = strlen($cnum + 1);
//            $loop = $len - $clen;
//            for ($i = 1; $i <= $loop; $i++) {
//                $prefix .= "0";
//            }
//            $prefix .= $cnum + 1;
//            return $prefix;
//        } else {
//            $prefix = $pre . '-' . substr(date("Y"), 2, 2) ;
//            return $prefix . '00001';
//        }
//    }

}
