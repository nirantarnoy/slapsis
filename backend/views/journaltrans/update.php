<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\JournalTrans */
/* @var $modelLines app\models\JournalTransLine[] */

$this->title = 'แก้ไขรายการ: ' . $model->journal_no;
$this->params['breadcrumbs'][] = ['label' => 'รายการ Stock Transaction', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->journal_no, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'แก้ไข';
?>
<div class="journal-trans-update">

    <?= $this->render('_form', [
        'model' => $model,
        'modelLines' => $modelLines,
        'create_type'=> null,
    ]) ?>

</div>