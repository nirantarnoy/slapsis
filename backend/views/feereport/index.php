<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use yii\helpers\Url;
use yii\bootstrap4\LinkPager;

$this->title = 'รายงานค่าธรรมเนียม';
$this->params['breadcrumbs'][] = $this->title;

$channelList = [
    '' => 'ทั้งหมด',
    '1' => 'Shopee',
    '2' => 'TikTok',
];

$reportTypes = [
    'detail' => 'รายละเอียด',
    'monthly' => 'รายเดือน',
    'yearly' => 'รายปี',
];
?>

    <div class="fee-report-index">

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?= Html::encode($this->title) ?></h3>
            </div>
            <div class="card-body">

                <!-- ฟอร์มค้นหา -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <?php $form = ActiveForm::begin([
                            'method' => 'get',
                            'options' => ['class' => 'form-inline'],
                        ]); ?>

                        <div class="form-group mr-3">
                            <label class="mr-2">ประเภทรายงาน:</label>
                            <?= Html::dropDownList('report_type', $reportType, $reportTypes, [
                                'class' => 'form-control',
                                'id' => 'report_type'
                            ]) ?>
                        </div>

                        <div class="form-group mr-3">
                            <label class="mr-2">จากวันที่:</label>
                            <?= Html::input('date', 'date_from', $dateFrom, ['class' => 'form-control']) ?>
                        </div>

                        <div class="form-group mr-3">
                            <label class="mr-2">ถึงวันที่:</label>
                            <?= Html::input('date', 'date_to', $dateTo, ['class' => 'form-control']) ?>
                        </div>

                        <div class="form-group mr-3">
                            <label class="mr-2">ช่องทาง:</label>
                            <?= Html::dropDownList('channel', $channel, $channelList, [
                                'class' => 'form-control'
                            ]) ?>
                        </div>

                        <div class="form-group mr-3">
                            <?= Html::submitButton('<i class="fa fa-search"></i> ค้นหา', ['class' => 'btn btn-primary']) ?>
                        </div>

                        <?php ActiveForm::end(); ?>
                    </div>
                </div>

                <!-- ปุ่มส่งออกและพิมพ์ -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="btn-group" role="group">
                            <?= Html::a('<i class="fa fa-file-excel"></i> Export Excel',
                                Url::to(['export', 'report_type' => $reportType, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'channel' => $channel, 'format' => 'excel']),
                                ['class' => 'btn btn-success']
                            ) ?>
                            <?= Html::a('<i class="fa fa-file-csv"></i> Export CSV',
                                Url::to(['export', 'report_type' => $reportType, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'channel' => $channel, 'format' => 'csv']),
                                ['class' => 'btn btn-info']
                            ) ?>
                            <?= Html::a('<i class="fa fa-print"></i> พิมพ์',
                                Url::to(['print', 'report_type' => $reportType, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'channel' => $channel]),
                                ['class' => 'btn btn-secondary', 'target' => '_blank']
                            ) ?>
                        </div>
                    </div>
                </div>

                <?php if ($reportType == 'detail'): ?>
                    <!-- แสดงรายละเอียด -->
                    <?= GridView::widget([
                        'dataProvider' => $data,
                        'tableOptions' => ['class' => 'table table-striped table-bordered table-sm'],
                        'columns' => [
                            ['class' => 'yii\grid\SerialColumn'],

                            [
                                'attribute' => 'order_sn',
                                'label' => 'Order No.',
                                'format' => 'text',
                            ],
                            [
                                'attribute' => 'channel_id',
                                'label' => 'Channel',
                                'value' => function($model) {
                                    return $model->channel_id == 1 ? 'Shopee' : 'TikTok';
                                },
                                'contentOptions' => ['style' => 'width: 80px'],
                            ],
                            [
                                'attribute' => 'product_name',
                                'label' => 'สินค้า',
                                'format' => 'text',
                            ],
                            [
                                'attribute' => 'quantity',
                                'label' => 'จำนวน',
                                'contentOptions' => ['class' => 'text-center', 'style' => 'width: 70px'],
                            ],
                            [
                                'attribute' => 'total_amount',
                                'label' => 'ยอดรวม',
                                'value' => function($model) {
                                    return number_format($model->total_amount, 2);
                                },
                                'contentOptions' => ['class' => 'text-right'],
                            ],
                            [
                                'attribute' => 'commission_fee',
                                'label' => 'ค่าคอมมิชชั่น',
                                'value' => function($model) {
                                    return number_format($model->commission_fee, 2);
                                },
                                'contentOptions' => ['class' => 'text-right'],
                            ],
                            [
                                'attribute' => 'transaction_fee',
                                'label' => 'ค่าธุรกรรม',
                                'value' => function($model) {
                                    return number_format($model->transaction_fee, 2);
                                },
                                'contentOptions' => ['class' => 'text-right'],
                            ],
                            [
                                'attribute' => 'service_fee',
                                'label' => 'ค่าบริการ',
                                'value' => function($model) {
                                    return number_format($model->service_fee, 2);
                                },
                                'contentOptions' => ['class' => 'text-right'],
                            ],
                            [
                                'attribute' => 'payment_fee',
                                'label' => 'ค่าชำระเงิน',
                                'value' => function($model) {
                                    return number_format($model->payment_fee, 2);
                                },
                                'contentOptions' => ['class' => 'text-right'],
                            ],
                            [
                                'attribute' => 'actual_income',
                                'label' => 'รายได้สุทธิ',
                                'value' => function($model) {
                                    return number_format($model->actual_income, 2);
                                },
                                'contentOptions' => ['class' => 'text-right', 'style' => 'font-weight: bold; background-color: #e8f5e9;'],
                            ],
                            [
                                'attribute' => 'order_date',
                                'label' => 'วันที่',
                                'format' => 'date',
                                'contentOptions' => ['style' => 'width: 100px'],
                            ],
                        ],
                        'pager' => ['class' => LinkPager::className()],
                    ]); ?>

                <?php else: ?>
                    <!-- แสดงรายงานสรุป -->
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead class="thead-dark">
                            <tr>
                                <th class="text-center">#</th>
                                <th class="text-center">ช่วงเวลา</th>
                                <th class="text-center">ช่องทาง</th>
                                <th class="text-center">จำนวนออเดอร์</th>
                                <th class="text-right">ยอดขายรวม</th>
                                <th class="text-right">ค่าคอมมิชชั่น</th>
                                <th class="text-right">ค่าธุรกรรม</th>
                                <th class="text-right">ค่าบริการ</th>
                                <th class="text-right">ค่าชำระเงิน</th>
                                <th class="text-right">ค่าขนส่ง</th>
                                <th class="text-right">ค่า Platform</th>
                                <th class="text-right">ภาษี</th>
                                <th class="text-right">รายได้สุทธิ</th>
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
                            $totalShipping = 0;
                            $totalPlatform = 0;
                            $totalTax = 0;
                            $totalIncome = 0;

                            foreach ($data as $row):
                                $totalOrders += $row['total_orders'];
                                $totalSales += $row['total_sales'];
                                $totalCommission += $row['total_commission'];
                                $totalTransaction += $row['total_transaction_fee'];
                                $totalService += $row['total_service_fee'];
                                $totalPayment += $row['total_payment_fee'];
                                $totalShipping += isset($row['total_shipping_cost']) ? $row['total_shipping_cost'] : 0;
                                $totalPlatform += isset($row['total_platform_discount']) ? $row['total_platform_discount'] : 0;
                                $totalTax += isset($row['total_tax']) ? $row['total_tax'] : 0;
                               // $totalIncome += $row['total_income'];
                                $line_total_income =  ($row['total_sales'] - ($row['total_commission'] + $row['total_transaction_fee'] + $row['total_service_fee'] + $row['total_payment_fee'] + $row['total_shipping_cost'] + $row['total_platform_discount'] + $row['total_tax']));
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
                                    <td class="text-right"><?= number_format(isset($row['total_shipping_cost']) ? $row['total_shipping_cost'] : 0, 2) ?></td>
                                    <td class="text-right"><?= number_format(isset($row['total_platform_discount']) ? $row['total_platform_discount'] : 0, 2) ?></td>
                                    <td class="text-right"><?= number_format(isset($row['total_tax']) ? $row['total_tax'] : 0, 2) ?></td>
                                    <td class="text-right" style="font-weight: bold; background-color: #e8f5e9;">
                                        <?= number_format($line_total_income, 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php
                            $totalIncome = ($totalSales - ($totalCommission + $totalTransaction + $totalService + $totalPayment + $totalShipping + $totalPlatform + $totalTax));
                            ?>
                            </tbody>
                            <tfoot class="table-info">
                            <tr style="font-weight: bold;">
                                <td colspan="3" class="text-center">รวมทั้งหมด</td>
                                <td class="text-center"><?= number_format($totalOrders) ?></td>
                                <td class="text-right"><?= number_format($totalSales, 2) ?></td>
                                <td class="text-right"><?= number_format($totalCommission, 2) ?></td>
                                <td class="text-right"><?= number_format($totalTransaction, 2) ?></td>
                                <td class="text-right"><?= number_format($totalService, 2) ?></td>
                                <td class="text-right"><?= number_format($totalPayment, 2) ?></td>
                                <td class="text-right"><?= number_format($totalShipping, 2) ?></td>
                                <td class="text-right"><?= number_format($totalPlatform, 2) ?></td>
                                <td class="text-right"><?= number_format($totalTax, 2) ?></td>
                                <td class="text-right" style="background-color: #c8e6c9;">
                                    <?= number_format($totalIncome, 2) ?>
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- กราฟแสดงผล -->
                    <?php if (!empty($chartData)): ?>
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <h4>กราฟแสดงค่าธรรมเนียม</h4>
                                <canvas id="feeChart" style="max-height: 400px;"></canvas>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>

            </div>
        </div>

    </div>

<?php if (!empty($chartData)): ?>
    <?php
    $this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', ['position' => \yii\web\View::POS_HEAD]);

    $chartDataJson = json_encode($chartData);
    $js = <<<JS
var chartData = $chartDataJson;

var ctx = document.getElementById('feeChart').getContext('2d');
var myChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: chartData.labels,
        datasets: [
            {
                label: 'ค่าคอมมิชชั่น',
                data: chartData.commission,
                backgroundColor: 'rgba(255, 99, 132, 0.6)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            },
            {
                label: 'ค่าธุรกรรม',
                data: chartData.transaction,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            },
            {
                label: 'ค่าบริการ',
                data: chartData.service,
                backgroundColor: 'rgba(255, 206, 86, 0.6)',
                borderColor: 'rgba(255, 206, 86, 1)',
                borderWidth: 1
            },
            {
                label: 'รายได้สุทธิ',
                data: chartData.income,
                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value, index, values) {
                        return value.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                }
            }
        },
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'สรุปค่าธรรมเนียมตามช่วงเวลา'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        label += context.parsed.y.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        return label;
                    }
                }
            }
        }
    }
});
JS;

    $this->registerJs($js, \yii\web\View::POS_READY);
    ?>
<?php endif; ?>