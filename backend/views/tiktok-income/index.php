<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\TiktokIncomeSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'รายงานรายได้ TikTok Shop';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="tiktok-income-index">


    <div class="tiktok-income-search">
        <?php $form = ActiveForm::begin([
            'action' => ['report'],
            'method' => 'get',
        ]); ?>

        <div class="row">
            <div class="col-md-4">
                <?= $form->field($searchModel, 'order_id')->textInput(['placeholder' => 'ระบุเลขคำสั่งซื้อ (ถ้ามี)']) ?>
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

    <?php if (isset($summary)): ?>
    <hr>
    <div class="tiktok-income-report">
        <div class="row">
            <div class="col-md-12">
                <div class="btn-group pull-right" style="margin-bottom: 10px;">
                    <?= Html::a('<i class="fa fa-file-excel-o"></i> Export Excel', array_merge(['export-excel'], Yii::$app->request->queryParams), ['class' => 'btn btn-success', 'target' => '_blank']) ?>
                    <?= Html::a('<i class="fa fa-file-pdf-o"></i> Export PDF', array_merge(['export-pdf'], Yii::$app->request->queryParams), ['class' => 'btn btn-danger', 'target' => '_blank']) ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th colspan="2" class="text-center bg-primary" style="color: white;">รายการ (Items)</th>
                            <th class="text-center bg-primary" style="color: white; width: 200px;">จำนวนเงิน (Amount)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Income Section -->
                        <tr class="info">
                            <td colspan="3"><strong>รายได้ (Income)</strong></td>
                        </tr>
                        <?php foreach ($summary['income'] as $label => $amount): ?>
                        <?php if ($amount != 0): ?>
                        <tr>
                            <td style="width: 50px;"></td>
                            <td><?= $label ?></td>
                            <td class="text-right"><?= number_format($amount, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        <tr class="success">
                            <td colspan="2" class="text-right"><strong>รวมรายได้ (Total Income)</strong></td>
                            <td class="text-right"><strong><?= number_format($summary['total_income'], 2) ?></strong></td>
                        </tr>

                        <!-- Expense Section -->
                        <tr class="warning">
                            <td colspan="3"><strong>ค่าใช้จ่าย (Expenses)</strong></td>
                        </tr>
                        <?php foreach ($summary['expense'] as $label => $amount): ?>
                        <?php if ($amount != 0): ?>
                        <tr>
                            <td></td>
                            <td><?= $label ?></td>
                            <td class="text-right"><?= number_format($amount, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        <tr class="danger">
                            <td colspan="2" class="text-right"><strong>รวมค่าใช้จ่าย (Total Expenses)</strong></td>
                            <td class="text-right"><strong><?= number_format($summary['total_expense'], 2) ?></strong></td>
                        </tr>

                        <!-- Net Settlement -->
                        <tr class="active" style="font-size: 1.2em;">
                            <td colspan="2" class="text-right"><strong>ยอดเงินโอนสุทธิ (Net Settlement)</strong></td>
                            <td class="text-right" style="border-bottom: 3px double #333;"><strong><?= number_format($summary['net_settlement'], 2) ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
