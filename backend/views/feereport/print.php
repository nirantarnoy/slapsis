<?php

use yii\helpers\Html;

$this->title = 'รายงานค่าธรรมเนียม';

$channelName = $channel == '1' ? 'Shopee' : ($channel == '2' ? 'TikTok' : 'ทุกช่องทาง');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Html::encode($this->title) ?></title>
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Sarabun", "TH SarabunPSK", Arial, sans-serif;
            font-size: 14px;
            margin: 20px;
            line-height: 1.5;
            color: #333;
        }

        /* Header Section */
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }

        .header h1 {
            margin: 10px 0;
            font-size: 28px;
            color: #2c3e50;
            font-weight: bold;
        }

        .header p {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }

        /* Info Section */
        .info-section {
            margin-bottom: 20px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }

        .info-section p {
            margin: 5px 0;
            font-size: 14px;
        }

        .info-section strong {
            color: #2c3e50;
            display: inline-block;
            min-width: 120px;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        table th,
        table td {
            border: 1px solid #dee2e6;
            padding: 10px 8px;
            font-size: 12px;
        }

        table th {
            background: linear-gradient(to bottom, #495057 0%, #343a40 100%);
            color: white;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        table tbody tr:hover {
            background-color: #e9ecef;
        }

        /* Text Alignment */
        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        /* Total Row */
        .total-row {
            font-weight: bold;
            background: linear-gradient(to right, #d4edda 0%, #c3e6cb 100%) !important;
            border-top: 2px solid #28a745 !important;
        }

        .total-row td {
            font-size: 13px;
            color: #155724;
        }

        /* No Print Elements */
        .no-print {
            margin-bottom: 20px;
            text-align: center;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }

        .print-button {
            padding: 12px 30px;
            font-size: 16px;
            cursor: pointer;
            background: linear-gradient(to bottom, #007bff 0%, #0056b3 100%);
            color: white;
            border: none;
            border-radius: 5px;
            margin: 0 5px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .print-button:hover {
            background: linear-gradient(to bottom, #0056b3 0%, #004085 100%);
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            transform: translateY(-2px);
        }

        .print-button:active {
            transform: translateY(0);
            box-shadow: 0 2px 3px rgba(0,0,0,0.2);
        }

        .close-button {
            background: linear-gradient(to bottom, #6c757d 0%, #545b62 100%);
        }

        .close-button:hover {
            background: linear-gradient(to bottom, #545b62 0%, #3d4349 100%);
        }

        /* Footer Section */
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }

        .footer p {
            margin: 10px 0;
            font-size: 14px;
        }

        .signature-line {
            display: inline-block;
            width: 300px;
            border-bottom: 1px solid #333;
            margin-left: 10px;
        }

        /* Channel Badges */
        .badge-shopee {
            background-color: #ee4d2d;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }

        .badge-tiktok {
            background-color: #000000;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }

        /* Print Styles */
        @media print {
            body {
                margin: 0;
                padding: 10mm;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: A4 landscape;
                margin: 10mm;
            }

            table {
                page-break-inside: auto;
                box-shadow: none;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            thead {
                display: table-header-group;
            }

            tfoot {
                display: table-footer-group;
            }

            .header {
                page-break-after: avoid;
            }

            .info-section {
                page-break-after: avoid;
                background-color: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            table th {
                background-color: #343a40 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            table tbody tr:nth-child(even) {
                background-color: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .total-row {
                background-color: #d4edda !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        /* Responsive for smaller screens */
        @media screen and (max-width: 768px) {
            body {
                margin: 10px;
            }

            table {
                font-size: 10px;
            }

            table th,
            table td {
                padding: 5px 3px;
            }

            .header h1 {
                font-size: 20px;
            }

            .print-button {
                padding: 10px 20px;
                font-size: 14px;
                display: block;
                width: 100%;
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>

<!-- Print/Close Buttons -->
<div class="no-print">
    <button onclick="handlePrint()" class="print-button">
        🖨️ พิมพ์รายงาน
    </button>
    <button onclick="window.close()" class="print-button close-button">
        ✖️ ปิดหน้าต่าง
    </button>
</div>

<!-- Header -->
<div class="header">
    <h1><?= Html::encode($this->title) ?></h1>
    <p style="font-size: 16px; margin-top: 10px;">
        <strong>ช่วงเวลา:</strong>
        <?= date('d/m/Y', strtotime($dateFrom)) ?> - <?= date('d/m/Y', strtotime($dateTo)) ?>
    </p>
</div>

<!-- Info Section -->
<div class="info-section">
    <p><strong>ช่องทาง:</strong> <?= $channelName ?></p>
    <p><strong>ประเภทรายงาน:</strong> <?= $reportType == 'detail' ? 'รายละเอียด' : ($reportType == 'monthly' ? 'รายเดือน' : 'รายปี') ?></p>
    <p><strong>วันที่พิมพ์:</strong> <?= date('d/m/Y H:i:s') ?></p>
</div>

<?php if ($reportType == 'detail'): ?>
    <!-- Detail Report -->
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
                <td class="text-center">
                    <?php if ($item->channel_id == 1): ?>
                        <span class="badge-shopee">Shopee</span>
                    <?php else: ?>
                        <span class="badge-tiktok">TikTok</span>
                    <?php endif; ?>
                </td>
                <td><?= Html::encode($item->product_name) ?></td>
                <td class="text-center"><?= number_format($item->quantity) ?></td>
                <td class="text-right"><?= number_format($item->total_amount, 2) ?></td>
                <td class="text-right"><?= number_format($item->commission_fee, 2) ?></td>
                <td class="text-right"><?= number_format($item->transaction_fee, 2) ?></td>
                <td class="text-right"><?= number_format($item->service_fee, 2) ?></td>
                <td class="text-right"><?= number_format($item->payment_fee, 2) ?></td>
                <td class="text-right" style="font-weight: bold; color: #155724;">
                    <?= number_format($item->actual_income, 2) ?>
                </td>
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
    <!-- Summary Report -->
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
                <td class="text-center">
                    <?php if ($row['channel_id'] == 1): ?>
                        <span class="badge-shopee">Shopee</span>
                    <?php else: ?>
                        <span class="badge-tiktok">TikTok</span>
                    <?php endif; ?>
                </td>
                <td class="text-center"><?= number_format($row['total_orders']) ?></td>
                <td class="text-right"><?= number_format($row['total_sales'], 2) ?></td>
                <td class="text-right"><?= number_format($row['total_commission'], 2) ?></td>
                <td class="text-right"><?= number_format($row['total_transaction_fee'], 2) ?></td>
                <td class="text-right"><?= number_format($row['total_service_fee'], 2) ?></td>
                <td class="text-right"><?= number_format($row['total_payment_fee'], 2) ?></td>
                <td class="text-right" style="font-weight: bold; color: #155724;">
                    <?= number_format($row['total_income'], 2) ?>
                </td>
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

<!-- Footer with Signature -->
<div class="footer">
    <p>ผู้พิมพ์: <span class="signature-line"></span></p>
    <p>วันที่: <?= date('d/m/Y') ?></p>
</div>

<script>
    /**
     * Handle print function
     */
    function handlePrint() {
        window.print();
    }

    /**
     * Auto-adjust table font size if too wide
     */
    window.onload = function() {
        adjustTableSize();
    };

    function adjustTableSize() {
        var table = document.querySelector('table');
        if (!table) return;

        var tableWidth = table.offsetWidth;
        var bodyWidth = document.body.offsetWidth;

        if (tableWidth > bodyWidth - 40) {
            var scale = (bodyWidth - 40) / tableWidth;
            if (scale < 1) {
                var currentFontSize = parseInt(window.getComputedStyle(table).fontSize);
                var newFontSize = Math.floor(currentFontSize * scale);
                table.style.fontSize = newFontSize + 'px';
            }
        }
    }

    /**
     * Handle window resize
     */
    window.onresize = function() {
        adjustTableSize();
    };

    /**
     * Keyboard shortcuts
     */
    document.addEventListener('keydown', function(e) {
        // Ctrl+P or Cmd+P for print
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            handlePrint();
        }

        // ESC to close
        if (e.key === 'Escape') {
            window.close();
        }
    });

    /**
     * Add print event listeners
     */
    window.onbeforeprint = function() {
        console.log('Preparing to print...');
    };

    window.onafterprint = function() {
        console.log('Print completed or cancelled.');
    };
</script>

</body>
</html>