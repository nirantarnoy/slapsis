<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Department $model */

$this->title = 'แก้ไขข้อมูลแผนก: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'ข้อมูลแผนก', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'แก้ไข';
?>
<div class="department-update">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
