<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use miloschuman\highcharts\Highcharts;

/* @var $this yii\web\View */
/* @var $startDate string */
/* @var $endDate string */
/* @var $shopeeSummary array */
/* @var $tiktokSummary array */
/* @var $chartData array */

$this->title = 'เปรียบเทียบรายได้ Shopee vs TikTok';
$this->params['breadcrumbs'][] = $this->title;
?>
<br />
<div class="income-compare-index">
    <div class="panel panel-default">
        <div class="panel-body">
            <?php $form = ActiveForm::begin([
                'action' => ['index'],
                'method' => 'get',
                'options' => ['class' => 'form-inline']
            ]); ?>

            <div class="form-group">
                <label class="control-label" style="margin-right: 10px;">ช่วงวันที่</label>
                <?= DatePicker::widget([
                    'name' => 'start_date',
                    'value' => $startDate,
                    'type' => DatePicker::TYPE_RANGE,
                    'name2' => 'end_date',
                    'value2' => $endDate,
                    'pluginOptions' => [
                        'format' => 'yyyy-mm-dd',
                        'autoclose' => true,
                    ]
                ]); ?>
            </div>
            
            <?= Html::submitButton('ค้นหา', ['class' => 'btn btn-primary', 'style' => 'margin-left: 10px;']) ?>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
<br />
    <div class="row">
        <!-- Shopee Summary -->
        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">Shopee Summary</h3>
                </div>
                <div class="box-body">
                    <table class="table table-bordered">
                        <tr>
                            <th>รวมรายได้</th>
                            <td class="text-right text-success"><?= number_format($shopeeSummary['total_income'], 2) ?></td>
                        </tr>
                        <tr>
                            <th>รวมค่าใช้จ่าย</th>
                            <td class="text-right text-danger"><?= number_format($shopeeSummary['total_expense'], 2) ?></td>
                        </tr>
                        <tr>
                            <th>สุทธิ</th>
                            <td class="text-right" style="font-weight: bold;"><?= number_format($shopeeSummary['total_income'] + $shopeeSummary['total_expense'], 2) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- TikTok Summary -->
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">TikTok Summary</h3>
                </div>
                <div class="box-body">
                    <table class="table table-bordered">
                        <tr>
                            <th>รวมรายได้</th>
                            <td class="text-right text-success"><?= number_format($tiktokSummary['total_income'], 2) ?></td>
                        </tr>
                        <tr>
                            <th>รวมค่าใช้จ่าย</th>
                            <td class="text-right text-danger"><?= number_format($tiktokSummary['total_expense'], 2) ?></td>
                        </tr>
                        <tr>
                            <th>สุทธิ</th>
                            <td class="text-right" style="font-weight: bold;"><?= number_format($tiktokSummary['total_income'] + $tiktokSummary['total_expense'], 2) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
<br />
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">แนวโน้มรายได้และค่าใช้จ่าย (Line Chart)</h3>
                </div>
                <div class="box-body">
                    <?= Highcharts::widget([
                        'options' => [
                            'title' => ['text' => 'Income & Expense Trend'],
                            'xAxis' => [
                                'categories' => $chartData['categories']
                            ],
                            'yAxis' => [
                                'title' => ['text' => 'Amount (THB)']
                            ],
                            'series' => $chartData['series'],
                            'credits' => ['enabled' => false],
                        ]
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
<br />
    <div class="row">
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">สัดส่วนรายได้ (Income Share)</h3>
                </div>
                <div class="box-body">
                    <?= Highcharts::widget([
                        'options' => [
                            'chart' => ['type' => 'pie'],
                            'title' => ['text' => 'Total Income Share'],
                            'plotOptions' => [
                                'pie' => [
                                    'innerSize' => '50%', // Donut chart
                                    'allowPointSelect' => true,
                                    'cursor' => 'pointer',
                                    'dataLabels' => ['enabled' => true]
                                ]
                            ],
                            'series' => [[
                                'name' => 'Income',
                                'data' => [
                                    ['Shopee', $shopeeSummary['total_income']],
                                    ['TikTok', $tiktokSummary['total_income']],
                                ]
                            ]],
                            'credits' => ['enabled' => false],
                        ]
                    ]); ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">สัดส่วนค่าใช้จ่าย (Expense Share)</h3>
                </div>
                <div class="box-body">
                    <?= Highcharts::widget([
                        'options' => [
                            'chart' => ['type' => 'pie'],
                            'title' => ['text' => 'Total Expense Share'],
                            'plotOptions' => [
                                'pie' => [
                                    'innerSize' => '50%', // Donut chart
                                    'allowPointSelect' => true,
                                    'cursor' => 'pointer',
                                    'dataLabels' => ['enabled' => true]
                                ]
                            ],
                            'series' => [[
                                'name' => 'Expense',
                                'data' => [
                                    ['Shopee', abs($shopeeSummary['total_expense'])],
                                    ['TikTok', abs($tiktokSummary['total_expense'])],
                                ]
                            ]],
                            'credits' => ['enabled' => false],
                        ]
                    ]); ?>
                </div>
            </div>
        </div>
    </div>

</div>
