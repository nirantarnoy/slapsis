<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var backend\models\ProductSearch $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="product-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <div class="row">
        <div class="col-lg-2">
            <!--         <span class="input-group-addon" id="basic-addon1"><i class="fa fa-search"></i></span>-->
            <?= $form->field($model, 'globalSearch')->textInput(['placeholder' => 'ค้นหา', 'class' => 'form-control', 'aria-describedby' => 'basic-addon1'])->label(false) ?>

        </div>
        <div class="col-lg-2">
            <?= $form->field($model, 'product_group_id')->widget(\kartik\select2\Select2::className(),['data'=>\yii\helpers\ArrayHelper::map(\backend\models\Productgroup::find()->where(['status'=>1])->all(),'id','name'),'options'=>['placeholder'=>'-- เลือกกลุ่มสินค้า --','onchange'=>'$(this).submit()'],'pluginOptions'=>['allowClear'=>true,]])->label(false) ?>
        </div>
        <div class="col-lg-2">
            <?= $form->field($model, 'brand_id')->widget(\kartik\select2\Select2::className(),['data'=>\yii\helpers\ArrayHelper::map(\backend\models\Productbrand::find()->where(['status'=>1])->all(),'id','name'),'options'=>['placeholder'=>'-- เลือกยี่ห้อ --','onchange'=>'$(this).submit()'],'pluginOptions'=>['allowClear'=>true,]])->label(false) ?>
        </div>
        <div class="col-lg-2">
            <?= $form->field($model, 'product_type_id')->widget(\kartik\select2\Select2::className(),['data'=>\yii\helpers\ArrayHelper::map(\backend\helpers\ProductType::asArrayObject(),'id','name'),'options'=>['placeholder'=>'-- เลือกประเภทสินค้า --','onchange'=>'$(this).submit()',],'pluginOptions'=>['allowClear'=>true,]])->label(false) ?>
        </div>
        <div class="col-lg-2">
            <?= $form->field($model, 'type_id')->widget(\kartik\select2\Select2::className(),['data'=>\yii\helpers\ArrayHelper::map(\backend\helpers\CatType::asArrayObject(),'id','name'),'options'=>['placeholder'=>'-- เลือกสภาพสินค้า --','onchange'=>'$(this).submit()',],'pluginOptions'=>['allowClear'=>true,]])->label(false) ?>
        </div>
    </div>


    <?php ActiveForm::end(); ?>

</div>
