<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Warehouse $model */

$this->title = 'แก้ไขคลังสินค้า: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'คลังสินค้า', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'แก้ไข';
?>
<div class="warehouse-update">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
