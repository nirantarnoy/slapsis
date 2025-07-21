<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var backend\models\StocksumSearch $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="stocksum-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options' => [
            'data-pjax' => 1
        ],
    ]); ?>

    <div class="input-group">

        <?= $form->field($model, 'warehouse_id')->widget(\kartik\select2\Select2::className(), [
            'data' => \yii\helpers\ArrayHelper::map(\backend\models\Warehouse::find()->all(), 'id', function ($data) {
                return $data->name;
            }),
            'options' => [
                'placeholder' => '--เลือกคลังสินค้า--',
                'onchange' => 'this.form.submit();'
            ],
            'pluginOptions' => [
                'allowClear' => true,
                'width'=> '300px',
            ]
        ])->label(false) ?>
        <span style="margin-left: 5px;"></span>
        <?= $form->field($model, 'product_id')->widget(\kartik\select2\Select2::className(), [
            'data' => \yii\helpers\ArrayHelper::map(\backend\models\Product::find()->where(['status'=>1])->all(), 'id', function ($data) {
                return $data->code . ' ' . $data->name;
            }),
            'options' => [
                'placeholder' => '--เลือกสินค้า--',
                'onchange' => 'this.form.submit();'
            ],
            'pluginOptions' => [
                'allowClear' => true,
                'width'=> '300px',
            ]
        ])->label(false) ?>
    </div>
    <?php ActiveForm::end(); ?>

</div>
