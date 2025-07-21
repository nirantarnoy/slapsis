<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Department $model */

$this->title = 'สร้างข้อมูลแผนก';
$this->params['breadcrumbs'][] = ['label' => 'ข้อมูลแผนก', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="department-create">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
