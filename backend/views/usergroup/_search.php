<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UsergroupSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="usergroup-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <div class="input-group">
        <!--         <span class="input-group-addon" id="basic-addon1"><i class="fa fa-search"></i></span>-->
        <?= $form->field($model, 'globalSearch')->textInput(['placeholder'=>'ค้นหา','class'=>'form-control','aria-describedby'=>'basic-addon1'])->label(false) ?>
    </div>
    <?php ActiveForm::end(); ?>

</div>
