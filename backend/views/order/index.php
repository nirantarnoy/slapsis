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
    <div class="order-index">
        <?php
        if (Yii::$app->session->hasFlash('success')) {
            echo '<div class="alert alert-success">' . Yii::$app->session->getFlash('success') . '</div>';
        }
        if (Yii::$app->session->hasFlash('error')) {
            echo '<div class="alert alert-danger">' . Yii::$app->session->getFlash('error') . '</div>';
        }
        ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <?= Html::encode($this->title) ?>
                    <?php if ($lastSync): ?>
                        <small class="text-muted" style="font-size: 0.6em; margin-left: 10px;color: red;">
                            (อัปเดตล่าสุด: <?= Yii::$app->formatter->asDatetime($lastSync->end_time, 'php:d/m/Y H:i:s') ?>)
                        </small>
                    <?php endif; ?>
                </h3>
                <div class="card-tools">
                    <?= Html::a('<i class="fas fa-plus"></i> เพิ่มคำสั่งซื้อ', ['create'], ['class' => 'btn btn-success btn-sm']) ?>
                    <?= Html::a('<i class="fas fa-chart-line"></i> ดูรายงาน', ['report'], ['class' => 'btn btn-info btn-sm']) ?>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown">
                            <i class="fas fa-sync"></i> ดึงข้อมูล
                        </button>
                        <div class="dropdown-menu">
                            <?php foreach (OnlineChannel::find()->where(['status' => OnlineChannel::STATUS_ACTIVE])->all() as $channel): ?>
                                <?= Html::a($channel->name, '#', [
                                    'class' => 'dropdown-item sync-channel',
                                    'data-channel-id' => $channel->id,
                                ]) ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php Pjax::begin(); ?>
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'responsive' => true,
                    'hover' => true,
                    'panel' => [
                        'type' => GridView::TYPE_DEFAULT,
                        'heading' => false,
                    ],
                    'toolbar' => [
                        '{export}',
                        '{toggleData}',
                    ],
                    'export' => [
                        'fontAwesome' => true,
                        'showConfirmAlert' => false,
                        'target' => GridView::TARGET_BLANK,
                    ],
                    'exportConfig' => [
                        GridView::EXCEL => [
                            'label' => 'Excel',
                            'icon' => 'file-excel-o',
                            'iconOptions' => ['class' => 'text-success'],
                            'showHeader' => true,
                            'showPageSummary' => true,
                            'showFooter' => true,
                            'showCaption' => true,
                            'filename' => 'orders_' . date('YmdHis'),
                            'alertMsg' => 'The EXCEL export file will be generated for download.',
                            'config' => [
                                'onRender' => new \yii\web\JsExpression("
                                function() {
                                    window.location.href = '" . \yii\helpers\Url::to(['export-excel']) . "?' + $.param($('#w0').yiiGridView('getFilterUrl'));
                                    return false;
                                }
                            "),
                            ],
                        ],
                        GridView::PDF => [
                            'label' => 'PDF',
                            'icon' => 'file-pdf-o',
                            'iconOptions' => ['class' => 'text-danger'],
                            'showHeader' => true,
                            'showPageSummary' => true,
                            'showFooter' => true,
                            'showCaption' => true,
                            'filename' => 'orders_' . date('YmdHis'),
                            'alertMsg' => 'The PDF export file will be generated for download.',
                            'config' => [
                                'onRender' => new \yii\web\JsExpression("
                                function() {
                                    window.location.href = '" . \yii\helpers\Url::to(['export-pdf']) . "?' + $.param($('#w0').yiiGridView('getFilterUrl'));
                                    return false;
                                }
                            "),
                            ],
                        ],
                    ],
                    'columns' => [
                        ['class' => 'kartik\grid\SerialColumn'],
                        [
                            'attribute' => 'order_id',
                            'headerOptions' => ['style' => 'width: 150px'],
                        ],
                        [
                            'attribute' => 'channel_id',
                            'format'=>'raw',
                            'value' => function($model){
                               if($model->channel_id ==1){
                                   return '<img src="'.Yii::$app->request->baseUrl."/uploads/logo/sp.png".'" width="32px">'.' Shopee';
                               }else if($model->channel_id==2){
                                   return '<img src="'.Yii::$app->request->baseUrl."/uploads/logo/tt.png".'" width="32px">'.' Tiktok';
                               }
                            },
                            'filter' => Select2::widget([
                                'model' => $searchModel,
                                'attribute' => 'channel_id',
                                'data' => ArrayHelper::map(OnlineChannel::find()->all(), 'id', 'name'),
                                'theme' => Select2::THEME_BOOTSTRAP,
                                'options' => [
                                    'placeholder' => 'ทุกช่องทาง',
                                ],
                                'pluginOptions' => [
                                    'allowClear' => true,
                                ],
                            ]),
                            'headerOptions' => ['style' => 'width: 150px'],
                        ],
                        'sku',
                        [
                            'attribute' => 'product_name',
                            'headerOptions' => ['style' => 'width: 200px'],
                        ],
                        [
                            'attribute' => 'quantity',
                            'format' => 'integer',
                            'headerOptions' => ['class' => 'text-center'],
                            'contentOptions' => ['class' => 'text-center'],
                        ],
                        [
                            'attribute' => 'price',
                            'format' => ['decimal', 2],
                            'headerOptions' => ['class' => 'text-right'],
                            'contentOptions' => ['class' => 'text-right'],
                        ],
                        [
                            'attribute' => 'total_amount',
                            'format' => ['decimal', 2],
                            'headerOptions' => ['class' => 'text-right'],
                            'contentOptions' => ['class' => 'text-right'],
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
                                    'locale' => [
                                        'format' => 'd/m/Y',
                                        'separator' => ' - ',
                                    ],
                                    'opens' => 'left',
                                ],
                            ]),
                            'headerOptions' => ['style' => 'width: 200px'],
                        ],
                        'order_status',
                        [
                            'attribute' => 'created_at',
                            'label' => 'วันที่ดึงข้อมูล',
                            'value' => function ($model) {
                                return Yii::$app->formatter->asDatetime($model->created_at, 'php:d/m/Y H:i:s');
                            },
                            'filter' => false,
                            'headerOptions' => ['style' => 'width: 150px'],
                        ],
                        [
                            'class' => 'kartik\grid\ActionColumn',
                            'dropdown' => false,
                            'vAlign' => 'middle',
                            'urlCreator' => function ($action, $model, $key, $index) {
                                return [$action, 'id' => $key];
                            },
                            'viewOptions' => ['title' => 'ดูรายละเอียด', 'data-toggle' => 'tooltip'],
                            'updateOptions' => ['title' => 'แก้ไข', 'data-toggle' => 'tooltip'],
                            'deleteOptions' => ['title' => 'ลบ', 'data-toggle' => 'tooltip'],
                        ],
                    ],
                    'showPageSummary' => false,
//                'pageSummary' => [
//                    'order_id' => 'รวม',
//                    'quantity' => function ($summary, $data, $widget) {
//                        return array_sum(array_column($data, 'quantity'));
//                    },
//                    'total_amount' => function ($summary, $data, $widget) {
//                        return array_sum(array_column($data, 'total_amount'));
//                    },
//                ],
                ]); ?>
                <?php Pjax::end(); ?>
            </div>
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