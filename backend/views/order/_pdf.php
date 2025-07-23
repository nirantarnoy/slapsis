<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>รายงานคำสั่งซื้อ</title>
    <style>
        body {
            font-family: 'sarabun', sans-serif;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        h1 {
            text-align: center;
        }
    </style>
</head>
<body>
<h1>รายงานคำสั่งซื้อ</h1>
<p>วันที่พิมพ์: <?= date('d/m/Y H:i:s') ?></p>

<table>
    <thead>
    <tr>
        <th class="text-center">#</th>
        <th>เลขที่คำสั่งซื้อ</th>
        <th>ช่องทาง</th>
        <th>ชื่อสินค้า</th>
        <th class="text-center">จำนวน</th>
        <th class="text-right">ราคา</th>
        <th class="text-right">ยอดรวม</th>
        <th>วันที่สั่งซื้อ</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $i = 1;
    $totalQuantity = 0;
    $totalAmount = 0;
    foreach ($dataProvider->models as $model):
        $totalQuantity += $model->quantity;
        $totalAmount += $model->total_amount;
        ?>
        <tr>
            <td class="text-center"><?= $i++ ?></td>
            <td><?= Html::encode($model->order_id) ?></td>
            <td><?= Html::encode($model->channel->name) ?></td>
            <td><?= Html::encode($model->product_name) ?></td>
            <td class="text-center"><?= number_format($model->quantity) ?></td>
            <td class="text-right"><?= number_format($model->price, 2) ?></td>
            <td class="text-right"><?= number_format($model->total_amount, 2) ?></td>
            <td><?= Yii::$app->formatter->asDatetime($model->order_date, 'php:d/m/Y H:i') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
    <tr>
        <th colspan="4" class="text-right">รวม</th>
        <th class="text-center"><?= number_format($totalQuantity) ?></th>
        <th></th>
        <th class="text-right"><?= number_format($totalAmount, 2) ?></th>
        <th></th>
    </tr>
    </tfoot>
</table>
</body>
</html>