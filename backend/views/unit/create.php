<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Unit $model */

$this->title = 'สร้างหน่วยนับ';
$this->params['breadcrumbs'][] = ['label' => 'หน่วยนับ', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="unit-create">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
