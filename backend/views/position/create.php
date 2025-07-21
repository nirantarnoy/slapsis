<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Position */

$this->title = 'สร้างตำแหน่ง';
$this->params['breadcrumbs'][] = ['label' => 'ตำแหน่ง', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="position-create">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
