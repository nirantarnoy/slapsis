<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Stocksum $model */

$this->title = 'Update Stocksum: ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Stocksums', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="stocksum-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
