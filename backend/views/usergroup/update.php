<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Usergroup */

$this->title = 'แก้ไขกลุ่มผู้ใช้งาน: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'กลุ่มผู้ใช้งาน', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'แก้ไข';
?>
<div class="usergroup-update">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
