<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Productgroup $model */

$this->title = 'แกไขยี่ห้อสินค้า: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'ยี่ห้อสินค้า', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'แก้ไข';
?>
<div class="productgroup-update">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
