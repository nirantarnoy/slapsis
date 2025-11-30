<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use common\models\SyncLog;

/* @var $this yii\web\View */
/* @var $model common\models\SyncLog */

$this->title = 'Sync Log #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Sync Logs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="sync-log-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            [
                'attribute' => 'type',
                'value' => ucfirst($model->type),
            ],
            [
                'attribute' => 'platform',
                'value' => ucfirst($model->platform),
            ],
            'start_time',
            'end_time',
            [
                'attribute' => 'status',
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
            ],
            'total_records',
            'message:ntext',
            'created_at',
        ],
    ]) ?>

</div>
