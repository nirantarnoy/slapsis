<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Order */

$this->title = 'เพิ่มคำสั่งซื้อ';
$this->params['breadcrumbs'][] = ['label' => 'คำสั่งซื้อ', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="order-create">


    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>