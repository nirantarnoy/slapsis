<?php

use backend\models\Product;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use kartik\grid\GridView;
use yii\widgets\Pjax;
use yii\bootstrap4\LinkPager;

/** @var yii\web\View $this */
/** @var backend\models\ProductSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'สินค้า';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="product-index space-y-6">

    <!-- Header & Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-box text-indigo-600"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <p class="text-sm text-gray-500 mt-1">จัดการข้อมูลสินค้าและราคา</p>
        </div>
        <div class="flex items-center gap-2">
            <?= Html::a('<i class="fas fa-plus mr-1"></i> สร้างใหม่', ['create'], [
                'class' => 'inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors font-medium text-sm shadow-sm'
            ]) ?>
        </div>
    </div>

    <!-- Search & Filter (Optional, if _search is used) -->
    <?php echo $this->render('_search', ['model' => $searchModel, 'viewstatus' => $viewstatus]); ?>

    <!-- Grid -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <?php Pjax::begin(); ?>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'emptyCell' => '-',
            'layout' => "{items}\n<div class='px-4 py-3 border-t border-gray-100 flex items-center justify-between'>{summary}\n{pager}</div>",
            'summary' => "แสดง {begin} - {end} ของทั้งหมด {totalCount} รายการ",
            'showOnEmpty' => false,
            'pjax' => true,
            'pjaxSettings' => ['neverTimeout' => true],
            'tableOptions' => ['class' => 'w-full text-sm text-left text-gray-500'],
            'headerRowOptions' => ['class' => 'text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-100'],
            'rowOptions' => function($model){
                return ['class' => 'bg-white border-b border-gray-100 hover:bg-gray-50 transition-colors'];
            },
            'emptyText' => '<div class="text-center py-8"><p class="text-gray-500">ไม่พบรายการใดๆ</p></div>',
            'columns' => [
                [
                    'class' => 'yii\grid\SerialColumn',
                    'headerOptions' => ['class' => 'px-3 py-3 text-center', 'width' => '60'],
                    'contentOptions' => ['class' => 'px-3 py-3 text-center font-medium text-gray-400'],
                ],
                [
                    'attribute' => 'sku',
                    'headerOptions' => ['class' => 'px-3 py-3'],
                    'contentOptions' => ['class' => 'px-3 py-3 font-mono text-xs font-medium text-indigo-600'],
                ],
                [
                    'attribute' => 'name',
                    'headerOptions' => ['class' => 'px-3 py-3'],
                    'contentOptions' => ['class' => 'px-3 py-3 font-medium text-gray-900'],
                ],
                [
                    'attribute' => 'cost_price',
                    'format' => ['decimal', 2],
                    'headerOptions' => ['class' => 'px-3 py-3 text-right'],
                    'contentOptions' => ['class' => 'px-3 py-3 text-right font-medium'],
                ],
                [
                    'attribute' => 'status',
                    'format' => 'raw',
                    'headerOptions' => ['class' => 'px-3 py-3 text-center'],
                    'contentOptions' => ['class' => 'px-3 py-3 text-center'],
                    'value' => function ($data) {
                        if ($data->status == 1) {
                            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">ใช้งาน</span>';
                        } else {
                            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">ไม่ใช้งาน</span>';
                        }
                    }
                ],
                [
                    'header' => 'ตัวเลือก',
                    'class' => 'yii\grid\ActionColumn',
                    'headerOptions' => ['class' => 'px-3 py-3 text-center', 'width' => '120'],
                    'contentOptions' => ['class' => 'px-3 py-3 text-center whitespace-nowrap'],
                    'template' => '{view} {update} {delete}',
                    'buttons' => [
                        'view' => function ($url, $data, $index) {
                            return Html::a('<i class="fas fa-eye"></i>', $url, [
                                'title' => Yii::t('yii', 'View'),
                                'class' => 'text-blue-600 hover:text-blue-900 mx-1 transition-colors'
                            ]);
                        },
                        'update' => function ($url, $data, $index) {
                            return Html::a('<i class="fas fa-pencil-alt"></i>', $url, [
                                'title' => Yii::t('yii', 'Update'),
                                'class' => 'text-indigo-600 hover:text-indigo-900 mx-1 transition-colors'
                            ]);
                        },
                        'delete' => function ($url, $data, $index) {
                            return Html::a('<i class="fas fa-trash"></i>', 'javascript:void(0)', [
                                'title' => Yii::t('yii', 'Delete'),
                                'class' => 'text-red-600 hover:text-red-900 mx-1 transition-colors',
                                'data-url' => $url,
                                'data-var' => $data->id,
                                'onclick' => 'recDelete($(this));'
                            ]);
                        }
                    ]
                ],
            ],
            'pager' => [
                'class' => LinkPager::className(),
                'options' => ['class' => 'flex pl-0 list-none rounded my-0'],
                'linkOptions' => ['class' => 'relative block py-2 px-3 leading-tight bg-white border border-gray-300 text-gray-800 border-r-0 hover:bg-gray-200'],
                'disabledListItemSubTagOptions' => ['class' => 'relative block py-2 px-3 leading-tight bg-white border border-gray-300 text-gray-400 border-r-0'],
                'activeLinkAttributes' => ['class' => 'relative block py-2 px-3 leading-tight bg-indigo-50 border border-indigo-500 text-indigo-600 border-r-0 z-10'],
            ],
        ]); ?>
        <?php Pjax::end(); ?>
    </div>
</div>

<?php
$this->registerJs(<<<JS
    $(document).on('pjax:start', function() {
        $('#loading').fadeIn();
    });
    $(document).on('pjax:end', function() {
       $('#loading').fadeOut();
    });
JS
);
?>