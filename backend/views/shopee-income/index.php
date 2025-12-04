<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\ShopeeIncomeSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'รายงานรายได้ Shopee';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="shopee-income-index space-y-8">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-orange-100 text-orange-600">
                    <i class="fas fa-shopping-bag text-sm"></i>
                </span>
                <?= Html::encode($this->title) ?>
            </h1>
            <p class="text-sm text-gray-500 mt-1 ml-10">สรุปรายได้และค่าธรรมเนียมจาก Shopee</p>
        </div>
    </div>

    <!-- Search Box -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <?php $form = ActiveForm::begin([
            'action' => ['report'],
            'method' => 'get',
            'options' => ['class' => 'space-y-4'],
        ]); ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">เลขคำสั่งซื้อ</label>
                <?= $form->field($searchModel, 'order_sn', ['options' => ['class' => 'm-0']])->textInput([
                    'placeholder' => 'ระบุเลขคำสั่งซื้อ (ถ้ามี)',
                    'class' => 'block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 px-3'
                ])->label(false) ?>
            </div>
            
            <div class="form-group">
                <label class="block text-sm font-medium text-gray-700 mb-1">ช่วงวันที่</label>
                <?= DatePicker::widget([
                    'model' => $searchModel,
                    'attribute' => 'start_date',
                    'attribute2' => 'end_date',
                    'options' => ['placeholder' => 'เริ่มต้น', 'class' => 'block w-full rounded-l-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 px-3'],
                    'options2' => ['placeholder' => 'สิ้นสุด', 'class' => 'block w-full rounded-r-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 px-3'],
                    'type' => DatePicker::TYPE_RANGE,
                    'form' => $form,
                    'pluginOptions' => [
                        'format' => 'yyyy-mm-dd',
                        'autoclose' => true,
                    ],
                    'layout' => '<div class="flex shadow-sm rounded-lg">{input1}<span class="inline-flex items-center px-3 rounded-none border border-l-0 border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">ถึง</span>{input2}</div>'
                ]); ?>
            </div>

            <div class="flex items-end space-x-3 pb-1">
                <?= Html::submitButton('<i class="fas fa-search mr-2"></i> ค้นหา', [
                    'class' => 'inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
                ]) ?>
                <?= Html::a('<i class="fas fa-undo mr-2"></i> ล้างค่า', ['index'], [
                    'class' => 'inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
                ]) ?>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </div>

    <?php if (isset($summary)): ?>
    <!-- Report Section -->
    <div class="space-y-4">
        <div class="flex justify-end space-x-3">
            <?= Html::a('<i class="fas fa-file-excel mr-2"></i> Export Excel', array_merge(['export-excel'], Yii::$app->request->queryParams), [
                'class' => 'inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500',
                'target' => '_blank'
            ]) ?>
            <?= Html::a('<i class="fas fa-file-pdf mr-2"></i> Export PDF', array_merge(['export-pdf'], Yii::$app->request->queryParams), [
                'class' => 'inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500',
                'target' => '_blank'
            ]) ?>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">สรุปรายการ (Summary)</h3>
            </div>
            
            <div class="p-6">
                <div class="max-w-3xl mx-auto">
                    <!-- Income Card -->
                    <div class="mb-8">
                        <h4 class="text-sm font-semibold text-emerald-600 uppercase tracking-wider mb-4 flex items-center">
                            <i class="fas fa-arrow-down mr-2"></i> รายได้ (Income)
                        </h4>
                        <div class="bg-emerald-50 rounded-lg border border-emerald-100 overflow-hidden">
                            <table class="min-w-full divide-y divide-emerald-100">
                                <tbody class="divide-y divide-emerald-100">
                                    <?php foreach ($summary['income'] as $label => $amount): ?>
                                    <tr>
                                        <td class="px-6 py-3 text-sm text-gray-700"><?= $label ?></td>
                                        <td class="px-6 py-3 text-sm font-medium text-gray-900 text-right"><?= number_format($amount, 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="bg-emerald-100/50">
                                        <td class="px-6 py-3 text-sm font-bold text-emerald-800">รวมรายได้ (Total Income)</td>
                                        <td class="px-6 py-3 text-sm font-bold text-emerald-800 text-right"><?= number_format($summary['total_income'], 2) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Expense Card -->
                    <div class="mb-8">
                        <h4 class="text-sm font-semibold text-red-600 uppercase tracking-wider mb-4 flex items-center">
                            <i class="fas fa-arrow-up mr-2"></i> ค่าใช้จ่าย (Expenses)
                        </h4>
                        <div class="bg-red-50 rounded-lg border border-red-100 overflow-hidden">
                            <table class="min-w-full divide-y divide-red-100">
                                <tbody class="divide-y divide-red-100">
                                    <?php foreach ($summary['expense'] as $label => $amount): ?>
                                    <tr>
                                        <td class="px-6 py-3 text-sm text-gray-700"><?= $label ?></td>
                                        <td class="px-6 py-3 text-sm font-medium text-gray-900 text-right"><?= number_format($amount, 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="bg-red-100/50">
                                        <td class="px-6 py-3 text-sm font-bold text-red-800">รวมค่าใช้จ่าย (Total Expenses)</td>
                                        <td class="px-6 py-3 text-sm font-bold text-red-800 text-right"><?= number_format($summary['total_expense'], 2) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Net Settlement -->
                    <div class="bg-gray-900 rounded-xl p-6 text-white shadow-lg transform transition hover:scale-[1.01]">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-400 text-sm font-medium uppercase tracking-wider">ยอดเงินโอนสุทธิ</p>
                                <p class="text-gray-400 text-xs mt-1">Net Settlement Amount</p>
                            </div>
                            <div class="text-right">
                                <p class="text-3xl font-bold text-white tracking-tight"><?= number_format($summary['net_settlement'], 2) ?></p>
                                <p class="text-emerald-400 text-xs mt-1 font-medium">
                                    <i class="fas fa-check-circle mr-1"></i> Calculated
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
