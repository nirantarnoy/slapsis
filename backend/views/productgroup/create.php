<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Productgroup $model */

$this->title = 'สร้างกลุ่มสินค้า';
$this->params['breadcrumbs'][] = ['label' => 'กลุ่มสินค้า', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="productgroup-create">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
