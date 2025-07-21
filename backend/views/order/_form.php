<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var backend\models\Order $model */
/** @var yii\widgets\ActiveForm $form */
$issue_id = 0;
$delivery_id = 0;
$do_no = '';
//$model_issue_data = \common\models\JournalIssue::find()->where(['issue_for_id'=>$model->id])->one();
//if($model_issue_data){
//    $issue_id = $model_issue_data->id;
//    if($issue_id){
//        $model_do = \common\models\DeliveryOrder::find()->select(['id','order_no'])->where(['issue_ref_id'=>$issue_id])->one();
//        if($model_do){
//            $delivery_id = $model_do->id;
//            $do_no = $model_do->order_no;
//        }
//    }
//}

$model_do = \common\models\DeliveryOrder::find()->select(['id', 'order_no'])->where(['issue_ref_id' => $model->id])->one();
if ($model_do) {
    $delivery_id = $model_do->id;
    $do_no = $model_do->order_no;
}

?>

<div class="order-form">

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-lg-3">
            <?= $form->field($model, 'order_no')->textInput(['maxlength' => true, 'readonly' => 'readonly']) ?>
        </div>
        <div class="col-lg-3">
            <?= $form->field($model, 'order_date')->textInput(['maxlength' => true, 'readonly' => 'readonly']) ?>
        </div>
        <div class="col-lg-3">
            <label for="">ลูกค้า</label>
            <input type="text" class="form-control" value="<?=\backend\models\Customer::findCusFullName($model->customer_id)?>" readonly>
            <?= $form->field($model, 'customer_id')->hiddenInput(['maxlength' => true, 'readonly' => 'readonly'])->label(false) ?>
        </div>
        <div class="col-lg-3">
            <label for="">เลขที่เสนอราคา</label>
            <input type="text" class="form-control" value="<?=\backend\models\Quotation::findNo($model->quotation_id)?>" readonly>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-3">
            <?= $form->field($model, 'total_amount')->textInput(['readonly' => 'readonly']) ?>
        </div>
    </div>

    <br/>
    <div class="row">
        <div class="col-lg-12">
            <h5>รายการละเอียดการสั่งซื้อ</h5>
        </div>
    </div>
    <br/>
    <div class="row">
        <div class="col-lg-12">
            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th style="width:5%;text-align: center;background-color: #36ab63;color: white;">#</th>
                    <th style="text-align: center;max-width: 50px;background-color: #36ab63;color: white;"></th>
                    <th style="text-align: center;background-color: #36ab63;color: white;">สินค้า</th>
                    <th style="text-align: right;background-color: #36ab63;color: white;">จำนวน</th>
                    <th style="text-align: right;background-color: #36ab63;color: white;">ราคา</th>
                    <th style="text-align: right;background-color: #36ab63;color: white;">รวม</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $qty_total = 0;
                $line_total = 0;
                ?>
                <?php if ($model_line == null): ?>
                    <tr>
                        <td colspan="6" style="padding: 15px;text-align: center;color: lightgrey;">ไม่พบรายการสินค้า
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $loop_count = 0; ?>
                    <?php foreach ($model_line as $model_detail_item): ?>
                        <?php
                        $photo = \backend\models\Product::findPhoto($model_detail_item->product_id);
                        $qty_total = $qty_total + $model_detail_item->qty;
                        $line_total = $line_total + $model_detail_item->line_total;
                        ?>
                        <tr>
                            <td style="text-align: center;"><?= ++$loop_count ?></td>
                            <td style="text-align: center;"><img
                                        src="<?= \Yii::$app->getUrlManager()->baseUrl . '/uploads/product_photo/' . $photo ?>"
                                        style="margin-top: 5px;max-width: 50px" alt=""></td>
                            <td style="text-align: left;"><?= \backend\models\Product::findName($model_detail_item->product_id) ?></td>
                            <td style="text-align: right;"><?= number_format($model_detail_item->qty, 2) ?></td>
                            <td style="text-align: right;"><?= number_format($model_detail_item->price, 2) ?></td>
                            <td style="text-align: right;"><?= number_format($model_detail_item->line_total, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right;"><b>รวม</b></td>
                    <td style="text-align: right;"><b><?= number_format($qty_total, 2) ?></b></td>
                    <td style="text-align: right;"></td>
                    <td style="text-align: right;"><b><?= number_format($line_total, 2) ?></b></td>
                </tr>
                </tfoot>
            </table>
        </div>

    </div>

    <div class="row">
        <div class="col-lg-2">
        <?php if (\Yii::$app->user->can('order/create')||\Yii::$app->user->can('order/create')): ?>
            <div class="form-group">
                <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>


    <?php ActiveForm::end(); ?>

</div>
