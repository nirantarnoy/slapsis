<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var backend\models\Order $model */

$this->title = $model->order_no;
$this->params['breadcrumbs'][] = ['label' => 'คำสั่งซื้อ', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="order-view">

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
            'order_no',
            ['attribute' => 'order_date', 'value' => function ($model) {
                return date('d/m/Y H:i:s', strtotime($model->order_date));
            }],
//            'customer_id',
//            'customer_name',
            //'customer_type',
            'total_amount',
            [
                'attribute' => 'transfer_bank_account_id',
                'value' => function ($model) {
                    return \backend\models\Bank::findName($model->transfer_bank_account_id);
                }
            ],
//            'status',
            'order_tracking_no',
            'delivery_status',
            [
                'attribute' => 'status',
                'value' => function ($data) {
                    return \backend\helpers\OrderStatus::getTypeById($data->id);
                },
            ],
//            'created_at',
//            'created_by',
//            'updated_at',
//            'updated_by',
        ],
    ]) ?>

</div>
