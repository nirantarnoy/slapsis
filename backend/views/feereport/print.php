<?php

use yii\helpers\Html;

$this->title = 'รายงานค่าธรรมเนียม';

$channelName = $channel == '1' ? 'Shopee' : ($channel == '2' ? 'TikTok' : 'ทุกช่องทาง');
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= Html::encode($this->title) ?></title>
    <style>
        body {
            font-family: "Sarabun", "TH SarabunPSK", Arial, sans-serif;
            font-size: 14px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 10px 0;
            font-size: 24px;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-section p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th,
        table td {
            border: 1px solid #000;
            padding: 8px;
            font-size: 12px;
        }
        table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-left {
            text-align: left;
        }
        .total-row {
            font-weight: bold;
            background-color: #e8f5e9;
        }
        @media print {
            body {
                margin: 10px;
            }
            .no-print {
                display: none;
            }
            @page {
                margin: 1cm;
            }
        }
    </style>
</head>
<body>

<div class="no-print" style="margin-bottom: 20px;">
    <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">
        <i class="fa fa-print"></i> พิมพ์รายงาน
    </button>
    <button onclick="window.close()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; margin-left: 10px;">
        ปิดหน้าต่าง
    </button>
</div>

<div class="header">
    <h1><?= Html::encode($this->title) ?></h1>
    <p>ช่วงเวลา: <?= date('d/m/Y', strtotime($dateFrom)) ?> - <?= date('d/m/Y', strtotime($dateTo)) ?></p>
</div>

<div class="info-section">
    <p><strong>ช่องทาง:</strong> <?= $channelName ?></p>
    <p><strong>ประเภทรายงาน:</strong> <?= $reportType == 'detail' ? 'รายละเอียด' : ($reportType == 'monthly' ? 'รายเดือน' : 'รายปี') ?></p>
    <p><strong>วันที่พิมพ์:</strong> <?= date('d/m/Y H:i:s') ?></p>
</div>

<?php if ($reportType == 'detail'): ?>
    <!-- รายงานรายละเอียด -->
    <table>
        <thead>
        <tr>
            <th style="width: 40px;">#</th>
            <th>Order No.</th>
            <th style="width: 80px;">Channel</th>
            <th>สินค้า</th>
            <th style="width: 60px;">จำนวน</th>
            <th style="width: 90px;">ยอดรวม</th>
            <th style="width: 90px;">คอมมิชชั่น</th>
            <th style="width: 90px;">ธุรกรรม</th>
            <th style="width: 90px;">บริการ</th>
            <th style="width: 90px;">ชำระเงิน</th>
            <th style="width: 90px;">รายได้สุทธิ</th>
            <th style="width: 90px;">วันที่</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $no = 1;
        $totalAmount = 0;
        $totalCommission = 0;
        $totalTransaction = 0;
        $totalService = 0;
        $totalPayment = 0;
        $totalIncome = 0;

        foreach ($data as $item):
            $totalAmount += $item->total_amount;
            $totalCommission += $item->commission_fee;
            $totalTransaction += $item->transaction_fee;
            $totalService += $item->service_fee;
            $totalPayment += $item->payment_fee;
            $totalIncome += $item->actual_income;
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= Html::encode($item->order_sn) ?></td>
                <td class="text-center"><?= $item->channel_id == 1 ? 'Shopee' : 'TikTok' ?></td>
                <td><?= Html::encode($item->product_name) ?></td>
                <td class="text-center"><?= $item->quantity ?></td>
                <td class="text-right"><?= number_format($item->total_amount, 2) ?></td>
                <td class="text-right"><?= number_format($item->commission_fee, 2) ?></td>
                <td class="text-right"><?= number_format($item->transaction_fee, 2) ?></td>
                <td class="text-right"><?= number_format($item->service_fee, 2) ?></td>
                <td class="text-right"><?= number_format($item->payment_fee, 2) ?></td>
                <td class="text-right"><?= number_format($item->actual_income, 2) ?></td>
                <td class="text-center"><?= date('d/m/Y', strtotime($item->order_date)) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <tr class="total-row">
            <td colspan="5" class="text-center">รวมทั้งหมด</td>
            <td class="text-right"><?= number_format($totalAmount, 2) ?></td>
            <td class="text-right"><?= number_format($totalCommission, 2) ?></td>
            <td class="text-right"><?= number_format($totalTransaction, 2) ?></td>
            <td class="text-right"><?= number_format($totalService, 2) ?></td>
            <td class="text-right"><?= number_format($totalPayment, 2) ?></td>
            <td class="text-right"><?= number_format($totalIncome, 2) ?></td>
            <td></td>
        </tr>
        </tfoot>
    </table>

<?php else: ?>
    <!-- รายงานสรุป -->
    <table>
        <thead>
        <tr>
            <th style="width: 40px;">#</th>
            <th>ช่วงเวลา</th>
            <th style="width: 80px;">ช่องทาง</th>
            <th style="width: 90px;">จำนวนออเดอร์</th>
            <th style="width: 100px;">ยอดขายรวม</th>
            <th style="width: 90px;">คอมมิชชั่น</th>
            <th style="width: 90px;">ธุรกรรม</th>
            <th style="width: 90px;">บริการ</th>
            <th style="width: 90px;">ชำระเงิน</th>
            <th style="width: 100px;">รายได้สุทธิ</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $no = 1;
        $totalOrders = 0;
        $totalSales = 0;
        $totalCommission = 0;
        $totalTransaction = 0;
        $totalService = 0;
        $totalPayment = 0;
        $totalIncome = 0;

        foreach ($data as $row):
            $totalOrders += $row['total_orders'];
            $totalSales += $row['total_sales'];
            $totalCommission += $row['total_commission'];
            $totalTransaction += $row['total_transaction_fee'];
            $totalService += $row['total_service_fee'];
            $totalPayment += $row['total_payment_fee'];
            $totalIncome += $row['total_income'];
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td class="text-center"><?= $row['period'] ?></td>
                <td class="text-center"><?= $row['channel_id'] == 1 ? 'Shopee' : 'TikTok' ?></td>
                <td class="text-center"><?= number_format($row['total_orders']) ?></td>
                <td class="text-right"><?= number_format($row['total_sales'], 2) ?></td>
                <td class="text-right"><?= number_format($row['total_commission'], 2) ?></td>
                <td class="text-right"><?= number_format($row['total_transaction_fee'], 2) ?></td>
                <td class="text-right"><?= number_format($row['total_service_fee'], 2) ?></td>
                <td class="text-right"><?= number_format($row['total_payment_fee'], 2) ?></td>
                <td class="text-right"><?= number_format($row['total_income'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <tr class="total-row">
            <td colspan="3" class="text-center">รวมทั้งหมด</td>
            <td class="text-center"><?= number_format($totalOrders) ?></td>
            <td class="text-right"><?= number_format($totalSales, 2) ?></td>
            <td class="text-right"><?= number_format($totalCommission, 2) ?></td>
            <td class="text-right"><?= number_format($totalTransaction, 2) ?></td>
            <td class="text-right"><?= number_format($totalService, 2) ?></td>
            <td class="text-right"><?= number_format($totalPayment, 2) ?></td>
            <td class="text-right"><?= number_format($totalIncome, 2) ?></td>
        </tr>
        </tfoot>
    </table>
<?php endif; ?>

<div style="margin-top: 50px;">
    <p>ลงชื่อ: _________________________ ผู้พิมพ์</p>
    <p>วันที่: <?= date('d/m/Y') ?></p>
</div>

<script>
    // Auto print เมื่อโหลดหน้า (ถ้าต้องการ)
    // window.onload = function() { window.print(); }
</script>

</body>
</html>