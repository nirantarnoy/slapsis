<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Unit $model */

$this->title = 'แก้ไขหน่วยนับ: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'หน่วยนับ', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'แก้ไข';
?>
<div class="unit-update">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
