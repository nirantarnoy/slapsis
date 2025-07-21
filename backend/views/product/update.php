<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Product $model */

$this->title = 'แก้ไขสินค้า: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'สินค้า', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'แก้ไข';
?>
<div class="product-update">

    <?= $this->render('_form', [
        'model' => $model,
        'work_photo'=>$work_photo,
        'model_line' => $model_line,
        'model_customer_line' => $model_customer_line,
    ]) ?>

</div>
