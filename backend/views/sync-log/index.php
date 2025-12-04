<?php

use yii\helpers\Html;
use yii\grid\GridView;
use common\models\SyncLog;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\SyncLogSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Sync Logs';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="sync-log-index space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-history text-indigo-600"></i>
                <?= Html::encode($this->title) ?>
            </h1>
            <p class="text-sm text-gray-500 mt-1">ประวัติการซิงค์ข้อมูลระบบ</p>
        </div>
    </div>

    <!-- Grid -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
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
                    'attribute' => 'id',
                    'headerOptions' => ['class' => 'px-3 py-3', 'width' => '80'],
                    'contentOptions' => ['class' => 'px-3 py-3 font-mono text-xs'],
                ],
                [
                    'attribute' => 'type',
                    'filter' => [
                        SyncLog::TYPE_ORDER => 'Order',
                        SyncLog::TYPE_INCOME => 'Income',
                    ],
                    'value' => function ($model) {
                        $icon = $model->type == SyncLog::TYPE_ORDER ? 'fa-shopping-cart' : 'fa-money-bill-wave';
                        return '<span class="flex items-center gap-2"><i class="fas '.$icon.' text-gray-400"></i> ' . ucfirst($model->type) . '</span>';
                    },
                    'format' => 'raw',
                    'headerOptions' => ['class' => 'px-3 py-3'],
                    'contentOptions' => ['class' => 'px-3 py-3'],
                ],
                [
                    'attribute' => 'platform',
                    'filter' => [
                        SyncLog::PLATFORM_TIKTOK => 'TikTok',
                        SyncLog::PLATFORM_SHOPEE => 'Shopee',
                    ],
                    'value' => function ($model) {
                        if ($model->platform == SyncLog::PLATFORM_SHOPEE) {
                            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">Shopee</span>';
                        } elseif ($model->platform == SyncLog::PLATFORM_TIKTOK) {
                            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-black text-white">TikTok</span>';
                        }
                        return ucfirst($model->platform);
                    },
                    'format' => 'raw',
                    'headerOptions' => ['class' => 'px-3 py-3'],
                    'contentOptions' => ['class' => 'px-3 py-3'],
                ],
                [
                    'attribute' => 'start_time',
                    'value' => function($model) {
                        return Yii::$app->formatter->asDatetime($model->start_time, 'php:d/m/Y H:i:s');
                    },
                    'headerOptions' => ['class' => 'px-3 py-3'],
                    'contentOptions' => ['class' => 'px-3 py-3 text-xs text-gray-500'],
                ],
                [
                    'attribute' => 'end_time',
                    'value' => function($model) {
                        return Yii::$app->formatter->asDatetime($model->end_time, 'php:d/m/Y H:i:s');
                    },
                    'headerOptions' => ['class' => 'px-3 py-3'],
                    'contentOptions' => ['class' => 'px-3 py-3 text-xs text-gray-500'],
                ],
                [
                    'attribute' => 'status',
                    'filter' => [
                        SyncLog::STATUS_PENDING => 'Pending',
                        SyncLog::STATUS_SUCCESS => 'Success',
                        SyncLog::STATUS_FAILED => 'Failed',
                    ],
                    'value' => function ($model) {
                        switch ($model->status) {
                            case SyncLog::STATUS_SUCCESS:
                                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1"></i> Success</span>';
                            case SyncLog::STATUS_FAILED:
                                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"><i class="fas fa-times-circle mr-1"></i> Failed</span>';
                            default:
                                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"><i class="fas fa-spinner fa-spin mr-1"></i> Pending</span>';
                        }
                    },
                    'format' => 'raw',
                    'headerOptions' => ['class' => 'px-3 py-3'],
                    'contentOptions' => ['class' => 'px-3 py-3'],
                ],
                [
                    'attribute' => 'total_records',
                    'headerOptions' => ['class' => 'px-3 py-3 text-center'],
                    'contentOptions' => ['class' => 'px-3 py-3 text-center font-medium'],
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{view}',
                    'buttons' => [
                        'view' => function ($url, $model, $key) {
                            return Html::a('<i class="fas fa-eye"></i>', $url, [
                                'title' => Yii::t('app', 'View'),
                                'class' => 'text-blue-600 hover:text-blue-900 mx-1'
                            ]);
                        },
                    ],
                    'headerOptions' => ['class' => 'px-3 py-3 text-center'],
                    'contentOptions' => ['class' => 'px-3 py-3 text-center'],
                ],
            ],
        ]); ?>
    </div>
</div>
