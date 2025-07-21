<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Position */

$this->title = 'แก้ไขตำแหน่ง: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'ตำแหน่ง', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'แก้ไข';
?>
<div class="position-update">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
