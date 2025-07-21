<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\Position */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'ตำแหน่ง', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="position-view">
    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
        //    'id',
            'code',
            'name',
            'description',
            [
                'attribute' => 'status',
                'format' => 'raw',
                'value' => function ($model) {
                    return $model->status == 1 ? '<div class="badge badge-success" style="padding: 10px;">ใช้งาน</div>' : '<div class="badge badge-secondary">ไม่ใช้งาน</div>';
                }
            ],
            [
                'attribute' => 'created_at',
                'format' => ['date', 'php:d-m-Y H:i:s'],
            ],
            [
                'attribute' => 'created_by',
                'value' => function ($model) {
                    return \backend\models\User::findName($model->created_by);
                }
            ],
            [
                'attribute' => 'updated_at',
                'format' => ['date', 'php:d-m-Y H:i:s'],
            ],
            [
                'attribute' => 'updated_by',
                'value' => function ($model) {
                    return \backend\models\User::findName($model->updated_by);
                }
            ],
        ],
    ]) ?>

</div>
