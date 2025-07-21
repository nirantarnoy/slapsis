<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\AuthitemSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="authitem-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
                'id'=>'form-search',
            'data-pjax' => 1
        ],
    ]); ?>
    <div class="input-group">
        <!--         <span class="input-group-addon" id="basic-addon1"><i class="fa fa-search"></i></span>-->
        <?= $form->field($model, 'globalSearch')->textInput(['placeholder'=>'ค้นหา','class'=>'form-control','aria-describedby'=>'basic-addon1'])->label(false) ?>
<!--        --><?php //echo $form->field($model, 'type')->Widget(\kartik\select2\Select2::className(),[
//                'data'=>\yii\helpers\ArrayHelper::map(\backend\helpers\AuthType::asArrayObject(),'id','name'),
//                 'options'=>[
//                         'onchange'=>'$("#form-search").submit()',
//                 ]
//
//        ])->label(false) ?>
    </div>
    <br />
    <?php ActiveForm::end(); ?>

</div>
