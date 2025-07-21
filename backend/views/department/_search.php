<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var backend\models\DepartmentSearch $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="department-search">

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
    </div>

    <?php ActiveForm::end(); ?>

</div>
