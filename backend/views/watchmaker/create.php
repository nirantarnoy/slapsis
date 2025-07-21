<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Productgroup $model */

$this->title = 'สร้างช่างนาฬิกา';
$this->params['breadcrumbs'][] = ['label' => 'ช่างนาฬิกา', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="productgroup-create">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
