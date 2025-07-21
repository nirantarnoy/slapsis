<?php

namespace backend\models;

use Yii;
use yii\db\ActiveRecord;

date_default_timezone_set('Asia/Bangkok');

class Employee extends \common\models\Employee
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
            'timestampudate' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'updated_at',
                ],
                'value' => time(),
            ],
            'timestampcby' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'created_by',
                ],
                'value' => Yii::$app->user->id,
            ],
            'timestamuby' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'updated_by',
                ],
                'value' => Yii::$app->user->id,
            ],
//            'timestampcompany'=>[
//                'class'=> \yii\behaviors\AttributeBehavior::className(),
//                'attributes'=>[
//                    ActiveRecord::EVENT_BEFORE_INSERT=>'company_id',
//                ],
//                'value'=> isset($_SESSION['user_company_id'])? $_SESSION['user_company_id']:1,
//            ],
//            'timestampbranch'=>[
//                'class'=> \yii\behaviors\AttributeBehavior::className(),
//                'attributes'=>[
//                    ActiveRecord::EVENT_BEFORE_INSERT=>'branch_id',
//                ],
//                'value'=> isset($_SESSION['user_branch_id'])? $_SESSION['user_branch_id']:1,
//            ],
            'timestampupdate' => [
                'class' => \yii\behaviors\AttributeBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'updated_at',
                ],
                'value' => time(),
            ],
        ];
    }

//    public function findUnitname($id){
//        $model = Unit::find()->where(['id'=>$id])->one();
//        return count($model)>0?$model->name:'';
//    }
    public static function findEmpcompanyid($id)
    {
        $model = Employee::find()->where(['id' => $id])->one();
        return $model != null ? $model->company_id : 0;
    }
    public static function findCode($id)
    {
        $model = Employee::find()->where(['id' => $id])->one();
        return $model != null ? $model->code : '';
    }

    public static function findIdFromUserId($user_id){
        $model = \backend\models\User::find()->where(['id' => $user_id])->one();
        return $model != null ? $model->employee_ref_id : 0;
    }

    public static function findFullName($id)
    {
        $model = Employee::find()->where(['id' => $id])->one();
        return $model != null ? $model->fname . ' ' . $model->lname : '';
    }

    public static function findName2($id)
    {
        $model = Employee::find()->where(['id' => $id])->one();
        return $model != null ? $model->fname : '';
    }

    public static function findPositionName($id)
    {
        $model = null;
        $model_x = User::find()->where(['id' => $id])->one();
        if ($model_x) {
            $model_emp = Employee::find()->where(['id' => $model_x->employee_ref_id])->one();
            if ($model_emp) {
                $model = Position::find()->where(['id' => $model_emp->position])->one();
            }
        }

        return $model != null ? $model->name : '';
    }
    public static function findNameFromUserId($id)
    {
        $model = null;
        $model_x = User::find()->where(['id' => $id])->one();
        if ($model_x) {
            $model = Employee::find()->where(['id' => $model_x->employee_ref_id])->one();
        }

        return $model != null ? $model->fname.' '.$model->lname : '';
    }

    public static function isPosUser($id)
    {
        $model = null;
        $model_x = User::find()->where(['id' => $id])->one();
        if ($model_x) {
            $model_emp = Employee::find()->where(['id' => $model_x->employee_ref_id])->one();
            if ($model_emp) {
                $model = $model_emp->is_sale_operator;
            }
        }

        return $model != null ? $model : 0;
    }

    public static function findUserId($code)
    {
        $model_emp_id = \backend\models\Employee::find()->where(['code' => trim($code),'status'=>1])->one();
        if ($model_emp_id) {
            $model = User::find()->where(['employee_ref_id' => $model_emp_id->id])->one();
            return $model != null ? $model->id : 0;
        } else {
            return 0;
        }

    }

    public static function findCostLivingPrice($id)
    {
        $price = 0;
        $model_x = Car::find()->where(['id' => $id])->one();
        if ($model_x) {
            $model_emp = Employee::find()->where(['id' => $model_x->driver_id])->one();
            if ($model_emp) {
                $price = $model_emp->cost_living_price;
            }
        }

        return $price;
    }
    public static function findSocialPrice($id)
    {
        $price = 0;
        $model_x = Car::find()->where(['id' => $id])->one();
        if ($model_x) {
            $model_emp = Employee::find()->where(['id' => $model_x->driver_id])->one();
            if ($model_emp) {
                $price = $model_emp->social_price;
            }
        }

        return $price;
    }
    public static function findAdvanceTestPrice($id)
    {
        $price = 0;
        $model_x = Car::find()->where(['id' => $id])->one();
        if ($model_x) {
            $model_emp = Employee::find()->where(['id' => $model_x->driver_id])->one();
            if ($model_emp) {

            }
        }

        return $price;
    }
    public static function findSocialPricePer($id)
    {
        $price = '';
        $model_x = Car::find()->where(['id' => $id])->one();
        if ($model_x) {
            $model_emp = Employee::find()->where(['id' => $model_x->driver_id])->one();
            if ($model_emp) {
              $price = $model_emp->social_price." %";
            }
        }

        return $price;
    }
//    public function findUnitid($code){
//        $model = Unit::find()->where(['name'=>$code])->one();
//        return count($model)>0?$model->id:0;
//    }

}
