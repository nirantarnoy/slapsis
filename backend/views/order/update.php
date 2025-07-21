<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Order $model */

$this->title = 'แก้ไขคำสั่งซื้อ: ' . $model->order_no;
$this->params['breadcrumbs'][] = ['label' => 'คำสั่งซื้อ', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->order_no, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'แก้ไข';
?>
<div class="order-update">

    <?= $this->render('_form', [
        'model' => $model,
        'model_line' => $model_line
    ]) ?>

</div>
