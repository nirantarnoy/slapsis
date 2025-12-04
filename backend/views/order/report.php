<?php

use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use kartik\select2\Select2;
use kartik\daterange\DateRangePicker;
use backend\models\OnlineChannel;
use yii\helpers\ArrayHelper;

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

<div class="order-report space-y-8">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-chart-line text-indigo-600"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <p class="text-sm text-gray-500 mt-1">สรุปข้อมูลยอดขายและสถิติ</p>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <?php $form = ActiveForm::begin([
            'method' => 'get',
            'action' => ['report'],
            'options' => ['class' => 'space-y-4'],
        ]); ?>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">ช่องทางการขาย</label>
                <?= $form->field($searchModel, 'channel_id', ['options' => ['class' => 'm-0']])->widget(Select2::class, [
                    'data' => ArrayHelper::map(OnlineChannel::find()->all(), 'id', 'name'),
                    'theme' => Select2::THEME_BOOTSTRAP,
                    'options' => ['placeholder' => 'ทุกช่องทาง'],
                    'pluginOptions' => ['allowClear' => true],
                ])->label(false) ?>
            </div>
            
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">ช่วงเวลา</label>
                <?= $form->field($searchModel, 'dateRange', ['options' => ['class' => 'm-0']])->widget(DateRangePicker::class, [
                    'convertFormat' => true,
                    'pluginOptions' => [
                        'locale' => ['format' => 'd/m/Y', 'separator' => ' - '],
                        'opens' => 'left',
                    ],
                    'options' => ['class' => 'form-control block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 px-3']
                ])->label(false) ?>
            </div>

            <div class="flex items-end space-x-2 pb-1">
                <?= Html::submitButton('<i class="fas fa-search mr-2"></i> ค้นหา', [
                    'class' => 'inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
                ]) ?>
                
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" @click.away="open = false" type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-download mr-2"></i> Export
                        <i class="fas fa-chevron-down ml-2 text-xs"></i>
                    </button>
                    <div x-show="open" class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-10" style="display: none;">
                        <div class="py-1">
                            <?= Html::a('<i class="fas fa-file-excel mr-2 text-green-600"></i> Excel', ['export-excel-report', 'OrderSearch' => $searchModel->attributes], ['class' => 'block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100']) ?>
                            <?= Html::a('<i class="fas fa-file-pdf mr-2 text-red-600"></i> PDF', ['export-pdf-report', 'OrderSearch' => $searchModel->attributes], ['class' => 'block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100']) ?>
                        </div>
                    </div>
                </div>

                <?= Html::button('<i class="fas fa-print mr-2"></i> พิมพ์', [
                    'class' => 'inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500',
                    'onclick' => 'window.print();'
                ]) ?>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Total Orders -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 flex items-center space-x-4">
            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                <i class="fas fa-shopping-cart text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">จำนวนคำสั่งซื้อ</p>
                <p class="text-2xl font-bold text-gray-900"><?= number_format($totalOrders) ?></p>
            </div>
        </div>

        <!-- Total Quantity -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 flex items-center space-x-4">
            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                <i class="fas fa-boxes text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">จำนวนสินค้าที่ขาย</p>
                <p class="text-2xl font-bold text-gray-900"><?= number_format($totalQuantity) ?></p>
            </div>
        </div>

        <!-- Avg Order Value -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 flex items-center space-x-4">
            <div class="flex-shrink-0 w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center text-amber-600">
                <i class="fas fa-dollar-sign text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">ยอดขายเฉลี่ย/คำสั่งซื้อ</p>
                <p class="text-2xl font-bold text-gray-900">฿<?= number_format($avgOrderValue, 2) ?></p>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 border-l-4 border-indigo-500 pl-3">ยอดขายรายวัน</h3>
            <div id="salesChart" style="height: 350px;"></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 border-l-4 border-pink-500 pl-3">ยอดขายตามช่องทาง</h3>
            <div id="channelChart" style="height: 350px;"></div>
        </div>
    </div>

    <!-- Detail Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h3 class="text-lg font-medium text-gray-900">รายละเอียดยอดขาย</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ช่องทาง</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวนคำสั่งซื้อ</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวนสินค้า</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">ยอดขายรวม</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">ยอดขายเฉลี่ย</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($reportData as $data): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= Yii::$app->formatter->asDate($data['order_date'], 'php:d/m/Y') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php if($data['channel']['id'] == 1): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                    <img src="<?= Yii::$app->request->baseUrl ?>/uploads/logo/sp.png" class="w-4 h-4 mr-1 rounded-full"> Shopee
                                </span>
                            <?php elseif($data['channel']['id'] == 2): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-black text-white">
                                    <img src="<?= Yii::$app->request->baseUrl ?>/uploads/logo/tt.png" class="w-4 h-4 mr-1 rounded-full"> Tiktok
                                </span>
                            <?php else: ?>
                                <?= Html::encode($data['channel']['name']) ?>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center"><?= number_format($data['order_count']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center"><?= number_format($data['total_quantity']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-emerald-600 text-right">฿<?= number_format($data['total_sales'], 2) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">฿<?= number_format($data['avg_order_value'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">รวมทั้งหมด</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-center"><?= number_format($totalOrders) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-center"><?= number_format($totalQuantity) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-emerald-600 text-right">฿<?= number_format($totalSales, 2) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 text-right">฿<?= number_format($avgOrderValue, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
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
    
    // Line Chart
    if ($('#salesChart').length && dates.length > 0) {
        try {
            Highcharts.chart('salesChart', {
                chart: {
                    type: 'line',
                    height: 350,
                    style: {
                        fontFamily: 'Prompt, sans-serif'
                    }
                },
                title: { text: '' },
                xAxis: {
                    categories: dates,
                    lineColor: '#E5E7EB',
                    tickColor: '#E5E7EB',
                    labels: { style: { color: '#6B7280' } }
                },
                yAxis: {
                    title: { text: 'ยอดขาย (บาท)', style: { color: '#6B7280' } },
                    gridLineColor: '#F3F4F6',
                    labels: {
                        formatter: function() {
                            return '฿' + Highcharts.numberFormat(this.value, 0);
                        },
                        style: { color: '#6B7280' }
                    }
                },
                tooltip: {
                    backgroundColor: '#FFFFFF',
                    borderColor: '#E5E7EB',
                    borderRadius: 8,
                    shadow: true,
                    formatter: function() {
                        return '<b>' + this.series.name + '</b><br/>' +
                               this.x + ': <span style="color:#10B981; font-weight:bold;">฿' + Highcharts.numberFormat(this.y, 2) + '</span>';
                    }
                },
                plotOptions: {
                    line: {
                        dataLabels: { enabled: false },
                        enableMouseTracking: true,
                        marker: { enabled: false }
                    }
                },
                series: [{
                    name: 'ยอดขาย',
                    data: salesData,
                    color: '#4F46E5', // Indigo-600
                    lineWidth: 3
                }],
                credits: { enabled: false }
            });
        } catch (e) {
            console.error('Error creating line chart:', e);
        }
    } else {
        $('#salesChart').html('<div class="flex items-center justify-center h-full text-gray-400">ไม่มีข้อมูลสำหรับแสดงกราฟ</div>');
    }

    // Pie Chart
    if ($('#channelChart').length && channelData.length > 0) {
        try {
            Highcharts.chart('channelChart', {
                chart: {
                    type: 'pie',
                    height: 350,
                    style: {
                        fontFamily: 'Prompt, sans-serif'
                    }
                },
                title: { text: '' },
                tooltip: {
                    backgroundColor: '#FFFFFF',
                    borderColor: '#E5E7EB',
                    borderRadius: 8,
                    pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b><br/>ยอดขาย: <b>฿{point.y:,.2f}</b>'
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        innerSize: '60%', // Donut style
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f}%',
                            style: {
                                fontSize: '12px',
                                color: '#374151'
                            }
                        },
                        showInLegend: true,
                        borderWidth: 0
                    }
                },
                series: [{
                    name: 'สัดส่วน',
                    colorByPoint: true,
                    data: channelData,
                    colors: ['#F97316', '#000000', '#10B981', '#3B82F6'] // Shopee Orange, TikTok Black, etc.
                }],
                credits: { enabled: false }
            });
        } catch (e) {
            console.error('Error creating pie chart:', e);
        }
    } else {
        $('#channelChart').html('<div class="flex items-center justify-center h-full text-gray-400">ไม่มีข้อมูลสำหรับแสดงกราฟ</div>');
    }
});
JS;

$this->registerJs($js, \yii\web\View::POS_READY);
?>

<!-- Print CSS -->
<style>
    @media print {
        .btn, .form-group, button {
            display: none !important;
        }
        .card, .bg-white {
            border: none !important;
            box-shadow: none !important;
        }
        body {
            background-color: white !important;
        }
    }
</style>