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

<div class="income-compare-index space-y-6">
    
    <!-- Search Filter -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <?php $form = ActiveForm::begin([
            'action' => ['index'],
            'method' => 'get',
            'options' => ['class' => 'flex flex-col md:flex-row items-end gap-4']
        ]); ?>

        <div class="flex-1 w-full md:w-auto">
            <label class="block text-sm font-medium text-gray-700 mb-2">ช่วงวันที่</label>
            <?= DatePicker::widget([
                'name' => 'start_date',
                'value' => $startDate,
                'type' => DatePicker::TYPE_RANGE,
                'name2' => 'end_date',
                'value2' => $endDate,
                'pluginOptions' => [
                    'format' => 'yyyy-mm-dd',
                    'autoclose' => true,
                ],
                'options' => ['class' => 'form-control w-full'], // Ensure full width
                'options2' => ['class' => 'form-control w-full']
            ]); ?>
        </div>
        
        <div class="pb-0.5">
            <?= Html::submitButton('<i class="fas fa-search mr-2"></i> ค้นหา', ['class' => 'bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-6 rounded-lg transition shadow-md hover:shadow-lg flex items-center h-[38px]']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Shopee Summary -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow duration-300">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-orange-50 to-white flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <span class="w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center mr-3">
                        <i class="fas fa-shopping-bag"></i>
                    </span>
                    Shopee Summary
                </h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-xl">
                        <span class="text-gray-600 font-medium">รวมรายได้</span>
                        <span class="text-emerald-600 font-bold text-lg"><?= number_format($shopeeSummary['total_income'], 2) ?></span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-xl">
                        <span class="text-gray-600 font-medium">รวมค่าใช้จ่าย</span>
                        <span class="text-rose-600 font-bold text-lg"><?= number_format($shopeeSummary['total_expense'], 2) ?></span>
                    </div>
                    <div class="flex justify-between items-center p-4 bg-indigo-50 rounded-xl border border-indigo-100">
                        <span class="text-indigo-900 font-bold">สุทธิ</span>
                        <span class="text-indigo-700 font-bold text-xl"><?= number_format($shopeeSummary['total_income'] - $shopeeSummary['total_expense'], 2) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- TikTok Summary -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow duration-300">
            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800 flex items-center">
                    <span class="w-8 h-8 rounded-full bg-gray-200 text-gray-800 flex items-center justify-center mr-3">
                        <i class="fab fa-tiktok"></i>
                    </span>
                    TikTok Summary
                </h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-xl">
                        <span class="text-gray-600 font-medium">รวมรายได้</span>
                        <span class="text-emerald-600 font-bold text-lg"><?= number_format($tiktokSummary['total_income'], 2) ?></span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-xl">
                        <span class="text-gray-600 font-medium">รวมค่าใช้จ่าย</span>
                        <span class="text-rose-600 font-bold text-lg"><?= number_format($tiktokSummary['total_expense'], 2) ?></span>
                    </div>
                    <div class="flex justify-between items-center p-4 bg-indigo-50 rounded-xl border border-indigo-100">
                        <span class="text-indigo-900 font-bold">สุทธิ</span>
                        <span class="text-indigo-700 font-bold text-xl"><?= number_format($tiktokSummary['total_income'] + $tiktokSummary['total_expense'], 2) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-6 border-l-4 border-indigo-500 pl-3">แนวโน้มรายได้และค่าใช้จ่าย</h3>
        <?= Highcharts::widget([
            'options' => [
                'title' => ['text' => ''],
                'chart' => ['style' => ['fontFamily' => 'Prompt, sans-serif']],
                'xAxis' => [
                    'categories' => $chartData['categories'],
                    'lineColor' => '#E5E7EB',
                    'tickColor' => '#E5E7EB',
                    'labels' => ['style' => ['color' => '#6B7280']]
                ],
                'yAxis' => [
                    'title' => ['text' => 'Amount (THB)', 'style' => ['color' => '#6B7280']],
                    'gridLineColor' => '#F3F4F6'
                ],
                'series' => $chartData['series'],
                'credits' => ['enabled' => false],
                'colors' => ['#10B981', '#F43F5E', '#3B82F6', '#F59E0B'], // Custom colors
                'plotOptions' => [
                    'line' => [
                        'marker' => ['enabled' => false]
                    ]
                ]
            ]
        ]); ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 border-l-4 border-emerald-500 pl-3">สัดส่วนรายได้ (Income Share)</h3>
            <?= Highcharts::widget([
                'options' => [
                    'chart' => ['type' => 'pie', 'style' => ['fontFamily' => 'Prompt, sans-serif']],
                    'title' => ['text' => ''],
                    'plotOptions' => [
                        'pie' => [
                            'innerSize' => '60%', // Donut chart
                            'allowPointSelect' => true,
                            'cursor' => 'pointer',
                            'dataLabels' => ['enabled' => true],
                            'borderWidth' => 0
                        ]
                    ],
                    'series' => [[
                        'name' => 'Income',
                        'data' => [
                            ['Shopee', $shopeeSummary['total_income']-$shopeeSummary['total_expense']],
                            ['TikTok', $tiktokSummary['total_income']],
                        ]
                    ]],
                    'credits' => ['enabled' => false],
                    'colors' => ['#F97316', '#1F2937'] // Shopee Orange, TikTok Dark
                ]
            ]); ?>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 border-l-4 border-rose-500 pl-3">สัดส่วนค่าใช้จ่าย (Expense Share)</h3>
            <?= Highcharts::widget([
                'options' => [
                    'chart' => ['type' => 'pie', 'style' => ['fontFamily' => 'Prompt, sans-serif']],
                    'title' => ['text' => ''],
                    'plotOptions' => [
                        'pie' => [
                            'innerSize' => '60%', // Donut chart
                            'allowPointSelect' => true,
                            'cursor' => 'pointer',
                            'dataLabels' => ['enabled' => true],
                            'borderWidth' => 0
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
                    'colors' => ['#FB923C', '#374151'] // Lighter versions
                ]
            ]); ?>
        </div>
    </div>

</div>
