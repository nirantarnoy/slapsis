<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use kartik\select2\Select2;
use kartik\daterange\DateRangePicker;
use backend\models\OnlineChannel;
use yii\helpers\ArrayHelper;
use miloschuman\highcharts\Highcharts;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\OrderSearch */
/* @var $reportData array */
/* @var $chartData array */

$this->title = 'รายงานยอดขาย';
$this->params['breadcrumbs'][] = ['label' => 'คำสั่งซื้อ', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// คำนวณสรุปยอดรวม
$totalOrders = 0;
$totalQuantity = 0;
$totalSales = 0;
$avgOrderValue = 0;

foreach ($reportData as $data) {
    $totalOrders += $data['order_count'];
    $totalQuantity += $data['total_quantity'];
    $totalSales += $data['total_sales'];
}

if ($totalOrders > 0) {
    $avgOrderValue = $totalSales / $totalOrders;
}
?>

<div class="order-report">
    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">ตัวกรองข้อมูล</h5>
        </div>
        <div class="card-body">
            <?php $form = ActiveForm::begin([
                'method' => 'get',
                'action' => ['report'],
            ]); ?>
            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($searchModel, 'channel_id')->widget(Select2::class, [
                        'data' => ArrayHelper::map(OnlineChannel::find()->all(), 'id', 'name'),
                        'theme' => Select2::THEME_BOOTSTRAP,
                        'options' => ['placeholder' => 'ทุกช่องทาง'],
                        'pluginOptions' => [
                            'allowClear' => true,
                        ],
                    ])->label('ช่องทางการขาย') ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($searchModel, 'dateRange')->widget(DateRangePicker::class, [
                        'convertFormat' => true,
                        'pluginOptions' => [
                            'locale' => [
                                'format' => 'd/m/Y',
                                'separator' => ' - ',
                            ],
                            'opens' => 'left',
                        ],
                    ])->label('ช่วงเวลา') ?>
                </div>
                <div class="col-md-4">
                    <div class="form-group" style="margin-top: 30px;">
                        <?= Html::submitButton('<i class="fas fa-search"></i> ค้นหา', ['class' => 'btn btn-primary']) ?>
                        <?= Html::a('<i class="fas fa-file-excel"></i> Export Excel', ['export-excel-report', 'OrderSearch' => $searchModel->attributes], ['class' => 'btn btn-success']) ?>
                        <?= Html::a('<i class="fas fa-file-pdf"></i> Export PDF', ['export-pdf-report', 'OrderSearch' => $searchModel->attributes], ['class' => 'btn btn-danger']) ?>
                        <?= Html::a('<i class="fas fa-print"></i> พิมพ์', '#', ['class' => 'btn btn-info', 'onclick' => 'window.print(); return false;']) ?>
                    </div>
                </div>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-info"><i class="fas fa-shopping-cart"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">จำนวนคำสั่งซื้อ</span>
                    <span class="info-box-number"><?= number_format($totalOrders) ?></span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-boxes"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">จำนวนสินค้าที่ขาย</span>
                    <span class="info-box-number"><?= number_format($totalQuantity) ?></span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="fas fa-dollar-sign"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">ยอดขายเฉลี่ย/คำสั่งซื้อ</span>
                    <span class="info-box-number">฿<?= number_format($avgOrderValue, 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">ยอดขายรายวัน</h5>
                </div>
                <div class="card-body">
                    <div id="salesChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">ยอดขายตามช่องทาง</h5>
                </div>
                <div class="card-body">
                    <div id="channelChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Table -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="card-title">รายละเอียดยอดขาย</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>วันที่</th>
                        <th>ช่องทาง</th>
                        <th class="text-center">จำนวนคำสั่งซื้อ</th>
                        <th class="text-center">จำนวนสินค้า</th>
                        <th class="text-right">ยอดขายรวม</th>
                        <th class="text-right">ยอดขายเฉลี่ย</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reportData as $data): ?>
                        <tr>
                            <td><?= Yii::$app->formatter->asDate($data['order_date'], 'php:d/m/Y') ?></td>
                            <td><?= Html::encode($data['channel']['name']) ?></td>
                            <td class="text-center"><?= number_format($data['order_count']) ?></td>
                            <td class="text-center"><?= number_format($data['total_quantity']) ?></td>
                            <td class="text-right">฿<?= number_format($data['total_sales'], 2) ?></td>
                            <td class="text-right">฿<?= number_format($data['avg_order_value'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr class="font-weight-bold">
                        <td colspan="2">รวมทั้งหมด</td>
                        <td class="text-center"><?= number_format($totalOrders) ?></td>
                        <td class="text-center"><?= number_format($totalQuantity) ?></td>
                        <td class="text-right">฿<?= number_format($totalSales, 2) ?></td>
                        <td class="text-right">฿<?= number_format($avgOrderValue, 2) ?></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Include Highcharts -->
<?php
// Include Highcharts
$this->registerJsFile('https://code.highcharts.com/highcharts.js', [
    'depends' => [\yii\web\JqueryAsset::class],
    'position' => \yii\web\View::POS_HEAD
]);
$this->registerJsFile('https://code.highcharts.com/modules/exporting.js', [
    'depends' => [\yii\web\JqueryAsset::class],
    'position' => \yii\web\View::POS_HEAD
]);

// Prepare data for line chart
$dates = [];
$salesData = [];

// ตรวจสอบว่ามีข้อมูลหรือไม่
if (isset($chartData['salesByDate']) && !empty($chartData['salesByDate'])) {
    foreach ($chartData['salesByDate'] as $date => $amount) {
        $dates[] = date('d/m', strtotime($date));
        $salesData[] = (float)$amount;
    }
}

// Prepare data for pie chart
$channelData = [];
if (isset($chartData['salesByChannel']) && !empty($chartData['salesByChannel'])) {
    foreach ($chartData['salesByChannel'] as $channel => $amount) {
        $channelData[] = [
            'name' => $channel,
            'y' => (float)$amount
        ];
    }
}

// แปลงข้อมูลเป็น JSON string ก่อนส่งไป JavaScript
$datesJson = json_encode($dates);
$salesDataJson = json_encode($salesData);
$channelDataJson = json_encode($channelData);

// JavaScript code
$js = <<<JS
$(document).ready(function() {
    // ตรวจสอบว่า Highcharts โหลดแล้วหรือยัง
    if (typeof Highcharts === 'undefined') {
        console.error('Highcharts is not loaded');
        return;
    }
    
    // รับข้อมูลจาก PHP
    var dates = {$datesJson};
    var salesData = {$salesDataJson};
    var channelData = {$channelDataJson};
    
    console.log('Dates:', dates);
    console.log('Sales Data:', salesData);
    console.log('Channel Data:', channelData);
    
    // Line Chart
    if ($('#salesChart').length && dates.length > 0) {
        try {
            Highcharts.chart('salesChart', {
                chart: {
                    type: 'line',
                    height: 350
                },
                title: {
                    text: ''
                },
                xAxis: {
                    categories: dates,
                    title: {
                        text: 'วันที่'
                    }
                },
                yAxis: {
                    title: {
                        text: 'ยอดขาย (บาท)'
                    },
                    labels: {
                        formatter: function() {
                            return '฿' + Highcharts.numberFormat(this.value, 0);
                        }
                    }
                },
                tooltip: {
                    formatter: function() {
                        return '<b>' + this.series.name + '</b><br/>' +
                               this.x + ': ฿' + Highcharts.numberFormat(this.y, 2);
                    }
                },
                plotOptions: {
                    line: {
                        dataLabels: {
                            enabled: false
                        },
                        enableMouseTracking: true
                    }
                },
                series: [{
                    name: 'ยอดขาย',
                    data: salesData,
                    color: '#007bff'
                }],
                credits: {
                    enabled: false
                }
            });
        } catch (e) {
            console.error('Error creating line chart:', e);
        }
    } else {
        console.warn('salesChart element not found or no data');
        $('#salesChart').html('<div class="text-center p-4">ไม่มีข้อมูลสำหรับแสดงกราฟ</div>');
    }

    // Pie Chart
    if ($('#channelChart').length && channelData.length > 0) {
        try {
            Highcharts.chart('channelChart', {
                chart: {
                    type: 'pie',
                    height: 350
                },
                title: {
                    text: ''
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b><br/>ยอดขาย: <b>฿{point.y:,.2f}</b>'
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f}%',
                            style: {
                                fontSize: '12px'
                            }
                        },
                        showInLegend: true
                    }
                },
                series: [{
                    name: 'สัดส่วน',
                    colorByPoint: true,
                    data: channelData
                }],
                credits: {
                    enabled: false
                }
            });
        } catch (e) {
            console.error('Error creating pie chart:', e);
        }
    } else {
        console.warn('channelChart element not found or no data');
        $('#channelChart').html('<div class="text-center p-4">ไม่มีข้อมูลสำหรับแสดงกราฟ</div>');
    }
});
JS;

$this->registerJs($js, \yii\web\View::POS_READY);
?>

<!-- Print CSS -->
<style>
    @media print {
        .btn, .form-group, .card-header h5 {
            display: none !important;
        }
        .card {
            border: none !important;
        }
    }
</style>