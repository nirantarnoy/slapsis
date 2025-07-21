<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Journalreceive $model */

$this->title = 'แก้ไขรับเข้าสินค้า: ' . $model->journal_no;
$this->params['breadcrumbs'][] = ['label' => 'รับเข้าสินค้า', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->journal_no, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'แก้ไข';
?>
<div class="journalreceive-update">
    <?= $this->render('_form', [
        'model' => $model,
        'model_line'=> $model_line,
    ]) ?>

</div>
