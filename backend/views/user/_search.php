<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UserSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="user-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <div class="row">
        <div class="col-lg-3">
            <!--         <span class="input-group-addon" id="basic-addon1"><i class="fa fa-search"></i></span>-->
            <?= $form->field($model, 'globalSearch')->textInput(['placeholder' => 'ค้นหา', 'class' => 'form-control', 'aria-describedby' => 'basic-addon1'])->label(false) ?>

        </div>
        <div class="col-lg-3">
            <?php
            $check_role = \backend\models\User::checkhasrole(\Yii::$app->user->id, 'System Administrator');
            if ($check_role) {
                echo \kartik\select2\Select2::widget([
                    'value' => $viewstatus,
                    'name' => 'viewstatus',
                    'data' => \yii\helpers\ArrayHelper::map(\backend\helpers\ViewstatusType::asArrayObject(), 'id', 'name'),
                    'options' => [
                        'onchange' => 'this.form.submit();',
                    ],
                ]);
            }

            ?>
        </div>
    </div>


    <?php ActiveForm::end(); ?>

</div>
