<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Stocksum $model */

$this->title = 'Create Stocksum';
$this->params['breadcrumbs'][] = ['label' => 'Stocksums', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="stocksum-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
