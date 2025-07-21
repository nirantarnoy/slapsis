<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Authitem */

$this->title = Yii::t('app', 'แก้ไขสิทธิ์ใช้งาน: ' . $model->name, [
    'nameAttribute' => '' . $model->name,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'สิทธิ์ใช้งาน'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->name]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="authitem-update">


    <?= $this->render('_form', [
        'model' => $model,
        'modelchild'=> $modelchild,
    ]) ?>

</div>
