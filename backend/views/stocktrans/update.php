<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Stocktrans $model */

$this->title = 'Update Stocktrans: ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Stocktrans', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="stocktrans-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
