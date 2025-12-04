<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use kartik\select2\Select2;
use kartik\daterange\DateRangePicker;
use backend\models\OnlineChannel;
use yii\helpers\ArrayHelper;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\OrderSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'จัดการคำสั่งซื้อ';
$this->params['breadcrumbs'][] = $this->title;

$lastSync = \common\models\SyncLog::find()
    ->where(['type' => \common\models\SyncLog::TYPE_ORDER, 'status' => \common\models\SyncLog::STATUS_SUCCESS])
    ->orderBy(['end_time' => SORT_DESC])
    ->one();
?>
<div class="order-index space-y-6">

    <!-- Header & Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-shopping-cart text-indigo-600"></i>
                <?= Html::encode($this->title) ?>
                <?php if ($lastSync): ?>
                    <span class="text-sm text-red-500 font-normal ml-2">
                        (อัปเดตล่าสุด: <?= Yii::$app->formatter->asDatetime($lastSync->end_time, 'php:d/m/Y H:i:s') ?>)
                    </span>
                <?php endif; ?>
            </h1>
        </div>
        <div class="flex items-center gap-2">
            <?= Html::a('<i class="fas fa-chart-line mr-1"></i> ดูรายงาน', ['report'], [
                'class' => 'inline-flex items-center px-4 py-2 bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition-colors font-medium text-sm'
            ]) ?>
        </div>
    </div>

    <!-- Grid -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <?php Pjax::begin(); ?>
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'filterModel' => $searchModel,
            'responsive' => true,
            'hover' => true,
            'pjax' => true,
            'bordered' => false,
            'striped' => false,
            'condensed' => false,
            'layout' => "{items}\n<div class='px-4 py-3 border-t border-gray-100 flex items-center justify-between'>{summary}\n{pager}</div>",
            'tableOptions' => ['class' => 'w-full text-sm text-left text-gray-500'],
            'headerRowOptions' => ['class' => 'text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-100'],
            'filterRowOptions' => ['class' => 'bg-white border-b border-gray-100'],
            'rowOptions' => function($model){
                return ['class' => 'bg-white border-b border-gray-100 hover:bg-gray-50 transition-colors'];
            },
            'columns' => [
                ['class' => 'kartik\grid\SerialColumn'],
                [
                    'attribute' => 'order_id',
                    'headerOptions' => ['class' => 'px-3 py-3 font-medium'],
                    'contentOptions' => ['class' => 'px-3 py-3 font-medium text-gray-900'],
                ],
                [
                    'attribute' => 'channel_id',
                    'format'=>'raw',
                    'value' => function($model){
                        if($model->channel_id == 1){
                            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                <img src="'.Yii::$app->request->baseUrl."/uploads/logo/sp.png".'" class="w-4 h-4 mr-1 rounded-full"> Shopee
                            </span>';
                        }else if($model->channel_id == 2){
                            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-black text-white">
                                <img src="'.Yii::$app->request->baseUrl."/uploads/logo/tt.png".'" class="w-4 h-4 mr-1 rounded-full"> Tiktok
                            </span>';
                        }
                        return $model->channel_id;
                    },
                    'filter' => Select2::widget([
                        'model' => $searchModel,
                        'attribute' => 'channel_id',
                        'data' => ArrayHelper::map(OnlineChannel::find()->all(), 'id', 'name'),
                        'theme' => Select2::THEME_BOOTSTRAP,
                        'options' => ['placeholder' => 'ทุกช่องทาง'],
                        'pluginOptions' => ['allowClear' => true],
                    ]),
                    'headerOptions' => ['class' => 'px-3 py-3'],
                    'contentOptions' => ['class' => 'px-3 py-3'],
                ],
                [
                    'attribute' => 'sku',
                    'headerOptions' => ['class' => 'px-3 py-3'],
                    'contentOptions' => ['class' => 'px-3 py-3 font-mono text-xs'],
                ],
                [
                    'attribute' => 'product_name',
                    'headerOptions' => ['class' => 'px-3 py-3'],
                    'contentOptions' => ['class' => 'px-3 py-3 max-w-xs truncate'],
                ],
                [
                    'attribute' => 'quantity',
                    'format' => 'integer',
                    'headerOptions' => ['class' => 'px-3 py-3 text-center'],
                    'contentOptions' => ['class' => 'px-3 py-3 text-center font-semibold text-gray-700'],
                ],
                [
                    'attribute' => 'total_amount',
                    'format' => ['decimal', 2],
                    'headerOptions' => ['class' => 'px-3 py-3 text-right'],
                    'contentOptions' => ['class' => 'px-3 py-3 text-right font-medium text-emerald-600'],
                ],
                [
                    'attribute' => 'order_date',
                    'value' => function ($model) {
                        return Yii::$app->formatter->asDatetime($model->order_date, 'php:d/m/Y H:i');
                    },
                    'filter' => DateRangePicker::widget([
                        'model' => $searchModel,
                        'attribute' => 'dateRange',
                        'convertFormat' => true,
                        'pluginOptions' => [
                            'locale' => ['format' => 'd/m/Y', 'separator' => ' - '],
                            'opens' => 'left',
                        ],
                    ]),
                    'headerOptions' => ['class' => 'px-3 py-3'],
                    'contentOptions' => ['class' => 'px-3 py-3 text-xs text-gray-500'],
                ],
                [
                    'attribute' => 'order_status',
                    'format' => 'raw',
                    'value' => function($model) {
                        $statusClass = 'bg-gray-100 text-gray-800';
                        if ($model->order_status == 'COMPLETED' || $model->order_status == 'Completed') $statusClass = 'bg-emerald-100 text-emerald-800';
                        if ($model->order_status == 'CANCELLED' || $model->order_status == 'Cancelled') $statusClass = 'bg-red-100 text-red-800';
                        if ($model->order_status == 'SHIPPED' || $model->order_status == 'Shipped') $statusClass = 'bg-blue-100 text-blue-800';
                        
                        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $statusClass . '">
                            ' . $model->order_status . '
                        </span>';
                    },
                    'headerOptions' => ['class' => 'px-3 py-3'],
                    'contentOptions' => ['class' => 'px-3 py-3'],
                ],
                [
                    'class' => 'kartik\grid\ActionColumn',
                    'dropdown' => false,
                    'vAlign' => 'middle',
                    'urlCreator' => function ($action, $model, $key, $index) {
                        return [$action, 'id' => $key];
                    },
                    'viewOptions' => ['class' => 'text-blue-600 hover:text-blue-900 mx-1', 'title' => 'ดูรายละเอียด', 'data-toggle' => 'tooltip'],
                    'updateOptions' => ['class' => 'text-indigo-600 hover:text-indigo-900 mx-1', 'title' => 'แก้ไข', 'data-toggle' => 'tooltip'],
                    'deleteOptions' => ['class' => 'text-red-600 hover:text-red-900 mx-1', 'title' => 'ลบ', 'data-toggle' => 'tooltip'],
                    'headerOptions' => ['class' => 'px-3 py-3 text-center'],
                    'contentOptions' => ['class' => 'px-3 py-3 text-center whitespace-nowrap'],
                ],
            ],
        ]); ?>
        <?php Pjax::end(); ?>
    </div>
</div>

<?php
$syncUrl = \yii\helpers\Url::to(['sync-orders']);
$csrf = Yii::$app->request->getCsrfToken();
$js = <<<JS
$('.sync-channel').on('click', function(e) {
    e.preventDefault();
    var channelId = $(this).data('channel-id');
    var channelName = $(this).text();
    
    if (confirm('ต้องการดึงข้อมูลจาก ' + channelName + ' หรือไม่?')) {
        $.post('$syncUrl', {
            channel_id: channelId,
            _csrf: '$csrf'
        }).done(function(data) {
            $.pjax.reload({container: '#p0'});
        });
    }
});
JS;
$this->registerJs($js);
?>