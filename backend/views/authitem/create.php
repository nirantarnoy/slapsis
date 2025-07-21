<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\Authitem */

$this->title = Yii::t('app', 'สร้างสิทธิ์ใช้งาน');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'สิทธิ์ใช้งาน'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="authitem-create">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
