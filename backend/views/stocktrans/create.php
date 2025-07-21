<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Stocktrans $model */

$this->title = 'Create Stocktrans';
$this->params['breadcrumbs'][] = ['label' => 'Stocktrans', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="stocktrans-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
