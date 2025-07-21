<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\Employee */

$this->title = Yii::t('app', 'สร้างรหัสพนักงาน');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'พนักงาน'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="employee-create">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
