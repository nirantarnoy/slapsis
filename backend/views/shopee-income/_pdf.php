<?php
use yii\helpers\Html;
?>
<div class="pdf-report">
    <h2 class="text-center">รายงานรายได้ Shopee</h2>
    <p class="text-center">
        <?php if ($searchModel->order_sn): ?>
            เลขคำสั่งซื้อ: <?= Html::encode($searchModel->order_sn) ?><br>
        <?php endif; ?>
        <?php if ($searchModel->start_date && $searchModel->end_date): ?>
            ช่วงวันที่: <?= Html::encode($searchModel->start_date) ?> ถึง <?= Html::encode($searchModel->end_date) ?>
        <?php endif; ?>
    </p>

    <table class="table table-bordered" style="width: 100%; border-collapse: collapse;" border="1" cellpadding="5">
        <thead>
            <tr style="background-color: #f5f5f5;">
                <th colspan="2">รายการ (Items)</th>
                <th style="text-align: right;">จำนวนเงิน (Amount)</th>
            </tr>
        </thead>
        <tbody>
            <!-- Income Section -->
            <tr style="background-color: #d9edf7;">
                <td colspan="3"><strong>รายได้ (Income)</strong></td>
            </tr>
            <?php foreach ($summary['income'] as $label => $amount): ?>
            <?php if ($amount != 0): ?>
            <tr>
                <td style="width: 20px;"></td>
                <td><?= $label ?></td>
                <td style="text-align: right;"><?= number_format($amount, 2) ?></td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
            <tr style="background-color: #dff0d8;">
                <td colspan="2" style="text-align: right;"><strong>รวมรายได้ (Total Income)</strong></td>
                <td style="text-align: right;"><strong><?= number_format($summary['total_income'], 2) ?></strong></td>
            </tr>

            <!-- Expense Section -->
            <tr style="background-color: #fcf8e3;">
                <td colspan="3"><strong>ค่าใช้จ่าย (Expenses)</strong></td>
            </tr>
            <?php foreach ($summary['expense'] as $label => $amount): ?>
            <?php if ($amount != 0): ?>
            <tr>
                <td></td>
                <td><?= $label ?></td>
                <td style="text-align: right;"><?= number_format($amount, 2) ?></td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
            <tr style="background-color: #f2dede;">
                <td colspan="2" style="text-align: right;"><strong>รวมค่าใช้จ่าย (Total Expenses)</strong></td>
                <td style="text-align: right;"><strong><?= number_format($summary['total_expense'], 2) ?></strong></td>
            </tr>

            <!-- Net Settlement -->
            <tr>
                <td colspan="2" style="text-align: right; font-size: 1.2em;"><strong>ยอดเงินโอนสุทธิ (Net Settlement)</strong></td>
                <td style="text-align: right; font-size: 1.2em; border-bottom: 3px double #000;"><strong><?= number_format($summary['net_settlement'], 2) ?></strong></td>
            </tr>
        </tbody>
    </table>
</div>
