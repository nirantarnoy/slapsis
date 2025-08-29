<?php
use yii\helpers\Html;

$this->title = 'เพิ่มค่าใช้จ่าย';
$this->params['breadcrumbs'][] = ['label' => 'จัดการค่าใช้จ่าย', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="expense-create">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
