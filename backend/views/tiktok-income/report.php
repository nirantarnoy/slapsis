<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\TiktokIncomeSearch */
/* @var $summary array */

$this->title = 'สรุปรายได้ TikTok Shop';
$this->params['breadcrumbs'][] = ['label' => 'รายงานรายได้ TikTok Shop', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="tiktok-income-report">

    <h1><?= Html::encode($this->title) ?></h1>
    
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">เงื่อนไขการค้นหา</h3>
        </div>
        <div class="panel-body">
            <?php if ($searchModel->order_id): ?>
                <p><strong>เลขคำสั่งซื้อ:</strong> <?= Html::encode($searchModel->order_id) ?></p>
            <?php endif; ?>
            <?php if ($searchModel->start_date && $searchModel->end_date): ?>
                <p><strong>ช่วงวันที่:</strong> <?= Html::encode($searchModel->start_date) ?> ถึง <?= Html::encode($searchModel->end_date) ?></p>
            <?php endif; ?>
        </div>
    </div>

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
