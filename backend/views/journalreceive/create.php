<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Journalreceive $model */

$this->title = 'สร้างรายการรับเข้าสินค้า';
$this->params['breadcrumbs'][] = ['label' => 'รับเข้าสินค้า', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="journalreceive-create">

    <?= $this->render('_form', [
        'model' => $model,
        'model_line'=> null,
    ]) ?>

</div>
