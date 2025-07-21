<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Warehouse $model */

$this->title = 'สร้างคลังสินค้า';
$this->params['breadcrumbs'][] = ['label' => 'คลังสินค้า', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="warehouse-create">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
