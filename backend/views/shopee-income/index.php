<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\ShopeeIncomeSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'รายงานรายได้ Shopee';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="shopee-income-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="shopee-income-search">
        <?php $form = ActiveForm::begin([
            'action' => ['report'],
            'method' => 'get',
        ]); ?>

        <div class="row">
            <div class="col-md-4">
                <?= $form->field($searchModel, 'order_sn')->textInput(['placeholder' => 'ระบุเลขคำสั่งซื้อ (ถ้ามี)']) ?>
            </div>
            <div class="col-md-4">
                <label class="control-label">ช่วงวันที่</label>
                <?= DatePicker::widget([
                    'model' => $searchModel,
                    'attribute' => 'start_date',
                    'attribute2' => 'end_date',
                    'options' => ['placeholder' => 'วันที่เริ่มต้น'],
                    'options2' => ['placeholder' => 'วันที่สิ้นสุด'],
                    'type' => DatePicker::TYPE_RANGE,
                    'form' => $form,
                    'pluginOptions' => [
                        'format' => 'yyyy-mm-dd',
                        'autoclose' => true,
                    ]
                ]); ?>
            </div>
            <div class="col-md-4" style="margin-top: 25px;">
                <?= Html::submitButton('ค้นหา / ดูรายงาน', ['class' => 'btn btn-primary']) ?>
                <?= Html::resetButton('ล้างค่า', ['class' => 'btn btn-default']) ?>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </div>

</div>
