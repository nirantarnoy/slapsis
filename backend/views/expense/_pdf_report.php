<?php
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>รายงานค่าใช้จ่าย</title>
    <style>
        body { font-family: 'THSarabunNew', sans-serif; font-size: 14px; }
        .header { text-align: center; margin-bottom: 20px; }
        .summary { margin: 20px 0; }
        .summary-item { display: inline-block; margin: 0 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { background-color: #f9f9f9; font-weight: bold; }
    </style>
</head>
<body>
<div class="header">
    <h1>รายงานค่าใช้จ่าย</h1>
    <p>ระหว่างวันที่ <?= Yii::$app->formatter->asDate($startDate) ?> ถึง <?= Yii::$app->formatter->asDate($endDate) ?></p>
</div>

<div class="summary">
    <div class="summary-item">
        <strong>ยอดรวมทั้งหมด:</strong> <?= number_format($totalAmount, 2) ?> บาท
    </div>
    <div class="summary-item">
        <strong>จำนวนรายการ:</strong> <?= count($expenses) ?> รายการ
    </div>
</div>

<!-- Category Summary -->
<h3>สรุปตามหมวดหมู่</h3>
<table>
    <thead>
    <tr>
        <th>หมวดหมู่</th>
        <th class="text-center">จำนวนครั้ง</th>
        <th class="text-right">จำนวนเงิน (บาท)</th>
        <th class="text-right">เปอร์เซ็นต์</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($categoryData as $category): ?>
        <tr>
            <td><?= $category['category'] ?></td>
            <td class="text-center"><?= number_format($category['count']) ?></td>
            <td class="text-right"><?= number_format($category['total_amount'], 2) ?></td>
            <td class="text-right"><?= number_format(($category['total_amount'] / max(1, $totalAmount)) * 100, 1) ?>%</td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
    <tr class="total-row">
        <td><strong>รวมทั้งหมด</strong></td>
        <td class="text-center"><strong><?= count($expenses) ?></strong></td>
        <td class="text-right"><strong><?= number_format($totalAmount, 2) ?></strong></td>
        <td class="text-right"><strong>100.0%</strong></td>
    </tr>
    </tfoot>
</table>

<!-- Detailed Expenses -->
<h3>รายการค่าใช้จ่ายทั้งหมด</h3>
<table>
    <thead>
    <tr>
        <th>วันที่</th>
        <th>หมวดหมู่</th>
        <th>รายละเอียด</th>
        <th class="text-right">จำนวนเงิน (บาท)</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($expenses as $expense): ?>
        <tr>
            <td><?= Yii::$app->formatter->asDate($expense->expense_date) ?></td>
            <td><?= $expense->category ?></td>
            <td><?= $expense->description ?></td>
            <td class="text-right"><?= number_format($expense->amount, 2) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
    <tr class="total-row">
        <td colspan="3" class="text-right"><strong>รวมทั้งหมด:</strong></td>
        <td class="text-right"><strong><?= number_format($totalAmount, 2) ?></strong></td>
    </tr>
    </tfoot>
</table>

<div style="margin-top: 30px; font-size: 12px; color: #666;">
    <p>รายงานสร้างเมื่อ: <?= date('d/m/Y H:i:s') ?></p>
</div>

</body>
</html>
