<?php
use yii\helpers\Html;

$this->title = 'แก้ไขค่าใช้จ่าย: ' . $model->category;
$this->params['breadcrumbs'][] = ['label' => 'จัดการค่าใช้จ่าย', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->category, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'แก้ไข';
?>
<div class="expense-update">


    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
