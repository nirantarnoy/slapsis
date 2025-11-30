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
<div class="sync-log-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'id',
            [
                'attribute' => 'type',
                'filter' => [
                    SyncLog::TYPE_ORDER => 'Order',
                    SyncLog::TYPE_INCOME => 'Income',
                ],
                'value' => function ($model) {
                    return ucfirst($model->type);
                },
            ],
            [
                'attribute' => 'platform',
                'filter' => [
                    SyncLog::PLATFORM_TIKTOK => 'TikTok',
                    SyncLog::PLATFORM_SHOPEE => 'Shopee',
                ],
                'value' => function ($model) {
                    return ucfirst($model->platform);
                },
            ],
            'start_time',
            'end_time',
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
                            return 'Success';
                        case SyncLog::STATUS_FAILED:
                            return 'Failed';
                        default:
                            return 'Pending';
                    }
                },
                'contentOptions' => function ($model) {
                    if ($model->status == SyncLog::STATUS_SUCCESS) {
                        return ['class' => 'text-success'];
                    } elseif ($model->status == SyncLog::STATUS_FAILED) {
                        return ['class' => 'text-danger'];
                    }
                    return ['class' => 'text-warning'];
                },
            ],
            'total_records',
            //'message:ntext',
            'created_at',

            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view}',
            ],
        ],
    ]); ?>


</div>
