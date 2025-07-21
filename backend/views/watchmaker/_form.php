<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var backend\models\Productgroup $model */
/** @var yii\widgets\ActiveForm $form */

$sql = "SELECT jt.journal_no, jt.trans_date, jtl.product_id,p.name,p.description, jtl.qty 
FROM journal_trans as jt 
    inner join journal_trans_line as jtl on jt.id = jtl.journal_trans_id 
    inner join product as p on jtl.product_id = p.id 
where jt.status = 1 and jt.party_id = " . $model->id;

$model_has_product = Yii::$app->db->createCommand($sql)->queryAll();

?>

<div class="productgroup-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-lg-1">

        </div>
        <div class="col-lg-10">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'description')->textarea(['maxlength' => true]) ?>

            <?php echo $form->field($model, 'status')->widget(\toxor88\switchery\Switchery::className(), ['options' => ['label' => '', 'class' => 'form-control']])->label() ?>

            <div class="form-group">
                <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
            </div>
        </div>

        <div class="col-lg-1">

        </div>
    </div>
    <br />
    <div class="row">
        <div class="col-lg-1"></div>
        <div class="col-lg-10">
            <h5>รายการสินค้า</h5>
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th style="width: 5%;text-align: center">#</th>
                    <th>เลขที่</th>
                    <th>รหัสสินค้า</th>
                    <th>รายละเอียด</th>
                    <th style="width:10%;text-align: right;">จำนวน</th>
                </thead>
                <tbody>
                <?php $line_no = 0;?>
                <?php for($i=0; $i<=count($model_has_product)-1; $i++):?>
                    <?php $line_no = $line_no + 1;?>
                <tr>
                    <td style="text-align: center;"><?=$line_no?></td>
                    <td><?=$model_has_product[$i]['journal_no']?></td>
                    <td><?=$model_has_product[$i]['name']?></td>
                    <td><?=$model_has_product[$i]['description']?></td>
                    <td style="text-align: right"><?=number_format($model_has_product[$i]['qty'],2)?></td>
                </tr>
                <?php endfor;?>
                </tbody>
            </table>
        </div>
        <div class="col-lg-1"></div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
