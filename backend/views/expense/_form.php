<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use kartik\file\FileInput;

?>

<div class="expense-form">

    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'category')->dropDownList(
                $model->getCategoryOptions(),
                ['prompt' => 'เลือกหัวข้อ...']
            ) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'expense_date')->widget(DatePicker::class, [
                'options' => ['placeholder' => 'เลือกวันที่...'],
                'pluginOptions' => [
                    'format' => 'yyyy-mm-dd',
                    'todayHighlight' => true,
                    'autoclose' => true
                ]
            ]) ?>
        </div>
    </div>

    <?= $form->field($model, 'description')->textarea(['rows' => 3]) ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'amount')->textInput([
                'maxlength' => true,
                'placeholder' => '0.00'
            ]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'status')->dropDownList(
                $model->getStatusOptions(),
                ['prompt' => 'เลือกสถานะ...']
            ) ?>
        </div>
    </div>

    <?= $form->field($model, 'receiptUpload')->widget(FileInput::class, [
        'options' => ['accept' => 'image/*,.pdf'],
        'pluginOptions' => [
            'showCaption' => false,
            'showRemove' => false,
            'showUpload' => false,
            'browseClass' => 'btn btn-primary btn-block',
            'browseIcon' => '<i class="glyphicon glyphicon-camera"></i> ',
            'browseLabel' =>  'เลือกไฟล์ใบเสร็จ'
        ],
    ]) ?>

    <?php if ($model->receipt_file): ?>
        <div class="alert alert-info">
            <strong>ไฟล์ปัจจุบัน:</strong>
            <?= Html::a($model->receipt_file, $model->getReceiptUrl(), ['target' => '_blank']) ?>
        </div>
    <?php endif; ?>

    <div class="form-group">
        <?= Html::submitButton('บันทึก', ['class' => 'btn btn-success']) ?>
        <?= Html::a('ยกเลิก', ['index'], ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
