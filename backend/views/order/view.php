<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\Order */

$this->title = $model->order_id;
$this->params['breadcrumbs'][] = ['label' => 'คำสั่งซื้อ', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="order-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('<i class="fas fa-edit"></i> แก้ไข', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="fas fa-trash"></i> ลบ', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'คุณแน่ใจหรือไม่ที่จะลบรายการนี้?',
                'method' => 'post',
            ],
        ]) ?>
        <?= Html::a('<i class="fas fa-list"></i> กลับไปยังรายการ', ['index'], ['class' => 'btn btn-default']) ?>
    </p>

    <div class="card">
        <div class="card-body">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'id',
                    'order_id',
                    [
                        'attribute' => 'channel_id',
                        'value' => $model->channel->name,
                    ],
                    'sku',
                    'product_name',
                    'product_detail:ntext',
                    'quantity:integer',
                    'price:currency',
                    'total_amount:currency',
                    'order_date:datetime',
                    'created_at:datetime',
                    'updated_at:datetime',
                ],
            ]) ?>
        </div>
    </div>

</div>