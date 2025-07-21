<?php

namespace backend\models;

use common\models\LoginLog;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

date_default_timezone_set('Asia/Bangkok');

class User extends \common\models\User
{
    public function rules()
    {
        return
            [
                [['username','user_group_id'],'required'],
                [['username', 'pwd'], 'string'],
                [['user_group_id','emp_ref_id','status'],'integer'],
                [['roles'], 'safe'],
            ];
    }

    public function attributeLabels()
    {
        return [
            'roles' => 'Role',
            'username' =>'Username',
            'password' => 'Password',

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
        $model = User::find()->where(['id' => $id])->one();
        return $model != null ? $model->username : '';
    }

    public static function findEmpId($id)
    {
        $model = User::find()->where(['id' => $id])->one();
        return $model != null ? $model->employee_ref_id : 0;
    }
    public static function findUserIdEmpId($id)
    {
        $model = User::find()->where(['employee_ref_id' => $id])->one();
        return $model != null ? $model->id : 0;
    }
    public static function findCustomerId($id)
    {
        $model = User::find()->where(['id' => $id])->one();
        return $model != null ? $model->customer_ref_id : 0;
    }

    public static function findGroup($id)
    {
        $model = User::find()->where(['id' => $id])->one();
        return $model != null ? $model->group_id : 0;
    }

    public static function getIdFromUsername($username)
    {
        $model = User::find()->where(['username' => $username])->one();
        return $model != null ? $model->employee_ref_id : 0;
    }


    public static function findLogintime($id)
    {
        $model = LoginLog::find()->select('MAX(login_date) as login_date')->where(['user_id' => $id])->one();
        return $model != null ? date('H:i', strtotime($model->login_date)) : '';
    }

    public static function findLogindatetime($id)
    {
        $c_date = date('Y-m-d');
        $model = LoginLog::find()->where(['user_id' => $id, 'status' => 1])->one();
        return $model != null ? date('Y-m-d H:i:s', strtotime($model->login_date)) : '';
    }

    public static function findUserType($id)
    {
        $model = \backend\models\Suplier::find()->where(['user_id' => $id])->all();
        return count($model) > 0 ? "suplier" : "user";
    }

    public static function getAllRoles()
    {
        $auth = \Yii::$app->authManager;
        return ArrayHelper::map($auth->getRoles(), 'name', 'name');
    }

    public static function getUserinfo($id)
    {
        $model = User::find()->where(['id' => $id])->one();
        return count($model) > 0 ? $model : null;

    }

    public function getRoleByUser()
    {
        $auth = \Yii::$app->authManager;
        $rolesUser = $auth->getRolesByUser($this->id);
        $roleItems = $this->getAllRoles();
        $roleSelect = [];

        foreach ($roleItems as $key => $roleName) {
            foreach ($rolesUser as $role) {
                if ($key == $role->name) {
                    $roleSelect[$key] = $roleName;
                }
            }
        }
        $this->roles = $roleSelect;
    }

    public function assignment()
    {
        $auth = \Yii::$app->authManager;
        $roleUser = $auth->getRolesByUser($this->id);
        $auth->revokeAll($this->id);
        if ($this->roles != null) {
            foreach ($this->roles as $key => $roleName) {
                $auth->assign($auth->getRole($roleName), $this->id);
            }
        }

    }

    //// Add


    public function findRoleByUser($id)
    {
        $auth = \Yii::$app->authManager;
        $roleUser = $auth->getRolesByUser($id);
        $roleSelect = [];

        foreach ($roleUser as $roleName) {
            array_push($roleSelect, $roleName->name);
        }
        return $roleSelect;
    }

    public static function checkhasrole($user_id,$role_name){
        $res = 0;
        $user_roles = \Yii::$app->authManager->getRolesByUser($user_id);
        if ($user_roles != null) {
            foreach ($user_roles as $value) {
                if($value->name == $role_name){
                    $res = 1;
                }
            }
        }
        return $res;
    }
}
