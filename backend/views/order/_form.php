<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use kartik\select2\Select2;
use kartik\datetime\DateTimePicker;
use backend\models\OnlineChannel;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model backend\models\Order */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="order-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'order_id')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'channel_id')->widget(Select2::class, [
                        'data' => ArrayHelper::map(OnlineChannel::find()->where(['status' => OnlineChannel::STATUS_ACTIVE])->all(), 'id', 'name'),
                        'theme' => Select2::THEME_BOOTSTRAP,
                        'options' => ['placeholder' => 'เลือกช่องทางการขาย...'],
                        'pluginOptions' => [
                            'allowClear' => false,
                        ],
                    ]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'sku')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'product_name')->textInput(['maxlength' => true]) ?>
                </div>
            </div>

            <?= $form->field($model, 'product_detail')->textarea(['rows' => 3]) ?>

            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($model, 'quantity')->textInput(['type' => 'number', 'min' => 1]) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'price')->textInput(['type' => 'number', 'step' => '0.01', 'min' => 0]) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'total_amount')->textInput(['type' => 'number', 'step' => '0.01', 'readonly' => true]) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'order_date')->widget(DateTimePicker::class, [
                        'pluginOptions' => [
                            'autoclose' => true,
                            'format' => 'yyyy-mm-dd hh:ii:ss',
                        ],
                    ]) ?>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <?= Html::submitButton('<i class="fas fa-save"></i> บันทึก', ['class' => 'btn btn-success']) ?>
            <?= Html::a('<i class="fas fa-times"></i> ยกเลิก', ['index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<?php
$js = <<<JS
// Auto calculate total amount
$('#order-quantity, #order-price').on('change keyup', function() {
    var quantity = parseFloat($('#order-quantity').val()) || 0;
    var price = parseFloat($('#order-price').val()) || 0;
    var total = quantity * price;
    $('#order-total_amount').val(total.toFixed(2));
});
JS;
$this->registerJs($js);
?>