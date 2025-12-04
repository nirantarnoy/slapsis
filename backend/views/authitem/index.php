<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\Url;
use lavrentiev\widgets\toastr\Notification;
use backend\assets\ICheckAsset;
use yii\bootstrap4\LinkPager;

ICheckAsset::register($this);
/* @var $this yii\web\View */
/* @var $searchModel backend\models\AuthitemSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'สิทธิ์ใช้งาน');
$this->params['breadcrumbs'][] = $this->title;

$this->registerJsFile(
    '@web/js/stockbalancejs.js?V=001',
    ['depends' => [\yii\web\JqueryAsset::className()]],
    static::POS_END
);

?>
<div class="authitem-index space-y-6">

    <!-- Header & Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-key text-indigo-600"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <p class="text-sm text-gray-500 mt-1">จัดการสิทธิ์การใช้งานระบบ (Roles & Permissions)</p>
        </div>
        <div class="flex items-center gap-2">
            <?= Html::a('<i class="fas fa-plus mr-1"></i> สร้างใหม่', ['create'], [
                'class' => 'inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors font-medium text-sm shadow-sm'
            ]) ?>
        </div>
    </div>

    <!-- Search (Optional) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <?php echo $this->render('_search', ['model' => $searchModel]); ?>
    </div>

    <!-- Grid -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <?php $session = Yii::$app->session; ?>
        <?php Pjax::begin(); ?>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'emptyCell' => '-',
            'layout' => "{items}\n<div class='px-4 py-3 border-t border-gray-100 flex items-center justify-between'>{summary}\n{pager}</div>",
            'summary' => "แสดง {begin} - {end} ของทั้งหมด {totalCount} รายการ",
            'showOnEmpty' => false,
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
                    'class' => 'yii\grid\CheckboxColumn',
                    'headerOptions' => ['class' => 'px-3 py-3 text-center', 'width' => '40'],
                    'contentOptions' => ['class' => 'px-3 py-3 text-center'],
                ],
                [
                    'attribute' => 'name',
                    'headerOptions' => ['class' => 'px-3 py-3'],
                    'contentOptions' => ['class' => 'px-3 py-3 font-medium text-gray-900'],
                ],
                [
                    'attribute' => 'description',
                    'headerOptions' => ['class' => 'px-3 py-3'],
                    'contentOptions' => ['class' => 'px-3 py-3 text-gray-500'],
                ],
                [
                    'attribute' => 'rule_name',
                    'headerOptions' => ['class' => 'px-3 py-3'],
                    'contentOptions' => ['class' => 'px-3 py-3 text-gray-500'],
                ],
                [
                    'attribute' => 'type',
                    'headerOptions' => ['class' => 'px-3 py-3'],
                    'contentOptions' => ['class' => 'px-3 py-3'],
                    'value' => function ($data) {
                        if ($data->type == 1) {
                            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Role</span>';
                        } else {
                            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Permission</span>';
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
                                'onclick' => 'recDelete($(this));'
                            ]);
                        }
                    ]
                ],
            ],
            'pager' => ['class' => LinkPager::className()],
        ]); ?>
        <?php Pjax::end(); ?>
    </div>
</div>

<?php
$this->registerJsFile('@web/js/sweetalert.min.js', ['depends' => [\yii\web\JqueryAsset::className()]], static::POS_END);
$this->registerCssFile('@web/css/sweetalert.css');
$this->registerJs('
    function recDelete(e){
        var url = e.attr("data-url");
        swal({
              title: "ต้องการลบรายการนี้ใช่หรือไม่",
              text: "",
              type: "error",
              showCancelButton: true,
              closeOnConfirm: false,
              showLoaderOnConfirm: true
            }, function () {
              e.attr("href",url); 
              e.trigger("click");        
        });
    }
    ', static::POS_END);
?>