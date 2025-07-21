<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Order $model */

$this->title = 'Create Order';
$this->params['breadcrumbs'][] = ['label' => 'Orders', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="order-create">
    <?= $this->render('_form', [
        'model' => $model,
        'model_line' => null,
    ]) ?>

</div>
