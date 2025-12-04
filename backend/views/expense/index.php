<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use kartik\date\DatePicker;

$this->title = 'จัดการค่าใช้จ่าย';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="expense-index space-y-6">
    
    <!-- Header & Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-file-invoice-dollar text-indigo-600"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <p class="text-sm text-gray-500 mt-1">บันทึกและตรวจสอบค่าใช้จ่ายต่างๆ</p>
        </div>
        <div class="flex items-center gap-2">
            <?= Html::a('<i class="fas fa-plus mr-1"></i> เพิ่มค่าใช้จ่าย', ['create'], [
                'class' => 'inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors font-medium text-sm shadow-sm'
            ]) ?>
            <?= Html::a('<i class="fas fa-chart-pie mr-1"></i> รายงานสรุป', ['report'], [
                'class' => 'inline-flex items-center px-4 py-2 bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition-colors font-medium text-sm'
            ]) ?>
             <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.away="open = false" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none shadow-sm">
                    <i class="fas fa-download mr-2 text-gray-400"></i> Export
                    <i class="fas fa-chevron-down ml-2 text-xs text-gray-400"></i>
                </button>
                <div x-show="open" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 border border-gray-100 z-50" style="display: none;">
                    <?= Html::a('<i class="fas fa-file-excel mr-2 text-green-600"></i> Excel', ['export-excel'], [
                        'class' => 'block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50',
                        'data-method' => 'post',
                    ]) ?>
                    <?= Html::a('<i class="fas fa-file-pdf mr-2 text-red-600"></i> PDF', ['export-pdf'], [
                        'class' => 'block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50',
                        'target' => '_blank',
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <?php Pjax::begin(); ?>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'layout' => "{items}\n<div class='px-4 py-3 border-t border-gray-100 flex items-center justify-between'>{summary}\n{pager}</div>",
            'tableOptions' => ['class' => 'w-full text-sm text-left text-gray-500'],
            'headerRowOptions' => ['class' => 'text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-100'],
            'filterRowOptions' => ['class' => 'bg-white border-b border-gray-100'],
            'rowOptions' => function($model){
                return ['class' => 'bg-white border-b border-gray-100 hover:bg-gray-50 transition-colors'];
            },
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],

                [
                    'attribute' => 'expense_date',
                    'format' => 'date',
                    'filter' => DatePicker::widget([
                        'model' => $searchModel,
                        'attribute' => 'expense_date',
                        'options' => ['placeholder' => 'เลือกวันที่...', 'class' => 'form-control text-sm'],
                        'pluginOptions' => [
                            'format' => 'yyyy-mm-dd',
                            'todayHighlight' => true,
                            'autoclose' => true
                        ]
                    ]),
                    'headerOptions' => ['class' => 'px-3 py-3', 'width' => '150'],
                    'contentOptions' => ['class' => 'px-3 py-3'],
                ],
                [
                    'attribute' => 'category',
                    'filter' => Html::activeDropDownList(
                        $searchModel,
                        'category',
                        ['' => 'ทั้งหมด'] + (new \backend\models\Expense())->getCategoryOptions(),
                        ['class' => 'form-select block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md']
                    ),
                    'value' => function($model) {
                         return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">' . $model->category . '</span>';
                    },
                    'format' => 'raw',
                    'headerOptions' => ['class' => 'px-3 py-3', 'width' => '150'],
                    'contentOptions' => ['class' => 'px-3 py-3'],
                ],
                [
                    'attribute' => 'description',
                    'value' => function($model) {
                        return Html::encode(mb_substr($model->description, 0, 50)) . (mb_strlen($model->description) > 50 ? '...' : '');
                    },
                    'format' => 'raw',
                    'headerOptions' => ['class' => 'px-3 py-3'],
                    'contentOptions' => ['class' => 'px-3 py-3'],
                ],
                [
                    'attribute' => 'amount',
                    'value' => function($model) {
                        return number_format($model->amount, 2);
                    },
                    'headerOptions' => ['class' => 'px-3 py-3 text-right', 'width' => '120'],
                    'contentOptions' => ['class' => 'px-3 py-3 text-right font-medium text-gray-900'],
                ],
                [
                    'attribute' => 'receipt_file',
                    'format' => 'raw',
                    'value' => function($model) {
                        if ($model->receipt_file) {
                            return Html::a('<i class="fas fa-paperclip mr-1"></i> ดูไฟล์', $model->getReceiptUrl(), [
                                'target' => '_blank',
                                'class' => 'inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
                            ]);
                        }
                        return '<span class="text-gray-400 text-xs italic">ไม่มีไฟล์</span>';
                    },
                    'headerOptions' => ['class' => 'px-3 py-3', 'width' => '100'],
                    'contentOptions' => ['class' => 'px-3 py-3'],
                ],

                [
                    'class' => 'yii\grid\ActionColumn',
                    'headerOptions' => ['class' => 'px-3 py-3 text-center', 'width' => '120'],
                    'contentOptions' => ['class' => 'px-3 py-3 text-center whitespace-nowrap'],
                    'template' => '{view} {update} {delete}',
                    'buttons' => [
                        'view' => function ($url, $model, $key) {
                            return Html::a('<i class="fas fa-eye"></i>', $url, [
                                'title' => Yii::t('app', 'View'),
                                'class' => 'text-blue-600 hover:text-blue-900 mx-1'
                            ]);
                        },
                        'update' => function ($url, $model, $key) {
                            return Html::a('<i class="fas fa-pencil-alt"></i>', $url, [
                                'title' => Yii::t('app', 'Update'),
                                'class' => 'text-indigo-600 hover:text-indigo-900 mx-1'
                            ]);
                        },
                        'delete' => function ($url, $model, $key) {
                            return Html::a('<i class="fas fa-trash"></i>', $url, [
                                'title' => Yii::t('app', 'Delete'),
                                'class' => 'text-red-600 hover:text-red-900 mx-1',
                                'data-confirm' => 'คุณแน่ใจหรือไม่ที่จะลบรายการนี้?',
                                'data-method' => 'post',
                            ]);
                        },
                    ],
                ],
            ],
        ]); ?>
        <?php Pjax::end(); ?>
    </div>
</div>
