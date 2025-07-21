<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\User */

$this->title = 'สร้างผู้ใช้งาน';
$this->params['breadcrumbs'][] = ['label' => 'ผู้ใช้งาน', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-create">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
