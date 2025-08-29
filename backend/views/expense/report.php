<?php
use yii\helpers\Html;
use yii\helpers\Url;
use kartik\date\DatePicker;
use yii\widgets\ActiveForm;

$this->title = 'รายงานค่าใช้จ่าย';
$this->params['breadcrumbs'][] = ['label' => 'จัดการค่าใช้จ่าย', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="expense-report">

    <!-- Date Range Filter -->
    <div class="panel panel-default">
        <div class="panel-body">
            <?php $form = ActiveForm::begin(['method' => 'get']); ?>
            <div class="row">
                <div class="col-md-4">
                    <?= DatePicker::widget([
                        'name' => 'start_date',
                        'value' => $startDate,
                        'options' => ['placeholder' => 'วันที่เริ่มต้น...'],
                        'pluginOptions' => [
                            'format' => 'yyyy-mm-dd',
                            'todayHighlight' => true,
                            'autoclose' => true
                        ]
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= DatePicker::widget([
                        'name' => 'end_date',
                        'value' => $endDate,
                        'options' => ['placeholder' => 'วันที่สิ้นสุด...'],
                        'pluginOptions' => [
                            'format' => 'yyyy-mm-dd',
                            'todayHighlight' => true,
                            'autoclose' => true
                        ]
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= Html::submitButton('ค้นหา', ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('Export PDF',
                        ['export-pdf', 'start_date' => $startDate, 'end_date' => $endDate],
                        ['class' => 'btn btn-danger', 'target' => '_blank']) ?>
                    <?= Html::a('Export Excel',
                        ['export-excel', 'start_date' => $startDate, 'end_date' => $endDate],
                        ['class' => 'btn btn-success']) ?>
                </div>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
  <br />
    <!-- Summary Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-aqua"><i class="fa fa-money"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">ยอดรวมทั้งหมด</span>
                    <span class="info-box-number"><?= number_format($totalAmount, 2) ?> บาท</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-list"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">จำนวนรายการ</span>
                    <span class="info-box-number"><?= count($expenses) ?> รายการ</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-calendar"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">ช่วงเวลา</span>
                    <span class="info-box-number"><?= Yii::$app->formatter->asDate($startDate) ?></span>
                    <small>ถึง <?= Yii::$app->formatter->asDate($endDate) ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-red"><i class="fa fa-calculator"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">เฉลี่ยต่อวัน</span>
                    <span class="info-box-number"><?= number_format($totalAmount / max(1, (strtotime($endDate) - strtotime($startDate)) / 86400 + 1), 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <!-- Category Pie Chart -->
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">สัดส่วนค่าใช้จ่ายแยกตามหมวดหมู่</h3>
                </div>
                <div class="box-body">
                    <canvas id="categoryChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Daily Expenses Line Chart -->
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">ค่าใช้จ่ายรายวัน</h3>
                </div>
                <div class="box-body">
                    <canvas id="dailyChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Summary Table -->
    <div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title">สรุปค่าใช้จ่ายตามหมวดหมู่</h3>
        </div>
        <div class="box-body">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>หมวดหมู่</th>
                    <th class="text-center">จำนวนครั้ง</th>
                    <th class="text-right">จำนวนเงิน</th>
                    <th class="text-right">เปอร์เซ็นต์</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($categoryData as $category): ?>
                    <tr>
                        <td><?= Html::encode($category['category']) ?></td>
                        <td class="text-center"><?= number_format($category['count']) ?></td>
                        <td class="text-right"><?= number_format($category['total_amount'], 2) ?> บาท</td>
                        <td class="text-right"><?= number_format(($category['total_amount'] / max(1, $totalAmount)) * 100, 1) ?>%</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr class="bg-gray">
                    <th>รวมทั้งหมด</th>
                    <th class="text-center"><?= count($expenses) ?></th>
                    <th class="text-right"><?= number_format($totalAmount, 2) ?> บาท</th>
                    <th class="text-right">100.0%</th>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Detailed Expenses Table -->
    <div class="box box-warning">
        <div class="box-header with-border">
            <h3 class="box-title">รายการค่าใช้จ่ายทั้งหมด</h3>
        </div>
        <div class="box-body">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>วันที่</th>
                    <th>หมวดหมู่</th>
                    <th>รายละเอียด</th>
                    <th class="text-right">จำนวนเงิน</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td><?= Yii::$app->formatter->asDate($expense->expense_date) ?></td>
                        <td><span class="label label-info"><?= Html::encode($expense->category) ?></span></td>
                        <td><?= Html::encode($expense->description) ?></td>
                        <td class="text-right"><?= number_format($expense->amount, 2) ?> บาท</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Chart.js Scripts -->
<?php
$categoryLabels = json_encode(array_column($categoryData, 'category'));
$categoryAmounts = json_encode(array_column($categoryData, 'total_amount'));

// Prepare daily data
$dailyData = [];
$dailyLabels = [];
foreach ($expenses as $expense) {
    $date = $expense->expense_date;
    if (!isset($dailyData[$date])) {
        $dailyData[$date] = 0;
    }
    $dailyData[$date] += $expense->amount;
}
ksort($dailyData);
$dailyLabels = json_encode(array_keys($dailyData));
$dailyAmounts = json_encode(array_values($dailyData));

$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js');
$this->registerJs("
// Category Pie Chart
var ctx1 = document.getElementById('categoryChart').getContext('2d');
var categoryChart = new Chart(ctx1, {
    type: 'pie',
    data: {
        labels: $categoryLabels,
        datasets: [{
            data: $categoryAmounts,
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
            ],
            hoverBackgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + 
                            context.parsed.toLocaleString() + ' บาท';
                    }
                }
            }
        }
    }
});

// Daily Line Chart
var ctx2 = document.getElementById('dailyChart').getContext('2d');
var dailyChart = new Chart(ctx2, {
    type: 'line',
    data: {
        labels: $dailyLabels,
        datasets: [{
            label: 'ค่าใช้จ่าย (บาท)',
            data: $dailyAmounts,
            borderColor: '#36A2EB',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString() + ' บาท';
                    }
                }
            }
        },
        elements: {
            point: {
                radius: 4,
                hoverRadius: 6
            }
        }
    }
});
");
?>
