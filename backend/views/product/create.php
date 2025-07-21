<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Product $model */

$this->title = 'สร้างสินค้า';
$this->params['breadcrumbs'][] = ['label' => 'สินค้า', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="product-create">

    <?= $this->render('_form', [
        'model' => $model,
        'work_photo'=> null,
        'model_line' =>null,
        'model_customer_line'=>null,
    ]) ?>

</div>
