<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Employee */

$this->title = Yii::t('app', 'แก้ไขพนักงาน: {name}', [
    'name' => $model->code,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'พนักงาน'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->code, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'แก้ไข');
?>
<div class="employee-update">
    <?= $this->render('_form', [
        'model' => $model,
        'model_line' => $model_line,
    ]) ?>

</div>
