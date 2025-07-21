<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\Journalissue $model */

$this->title = 'สร้างรายการเบิกสินค้า';
$this->params['breadcrumbs'][] = ['label' => 'เบิกสินค้า', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="journalissue-create">

    <?= $this->render('_form', [
        'model' => $model,
        'model_line' => null,
    ]) ?>

</div>
