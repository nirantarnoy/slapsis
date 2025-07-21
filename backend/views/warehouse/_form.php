<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var backend\models\Warehouse $model */
/** @var yii\widgets\ActiveForm $form */

$model_warehouse_product = null;

if (!$model->isNewRecord) {
    $sql = "SELECT p.name as product_name,p.description,st.qty 
            FROM product as p 
                left join stock_sum as st on p.id = st.product_id 
                inner join warehouse as w on st.warehouse_id = w.id 
            where st.qty > 0 and w.id = " . $model->id;

    $model_warehouse_product = Yii::$app->db->createCommand($sql)->queryAll();

}
?>

<div class="warehouse-form">

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
            <label for="">รายละเอียดสินค้า</label>
            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <td>สินค้า</td>
                    <td>รายละเอียด</td>
                    <td>จำนวน</td>
                </tr>
                </thead>
                <tbody>
                <?php if ($model_warehouse_product != null): ?>
                    <?php for ($i = 0; $i <= count($model_warehouse_product) - 1; $i++): ?>
                        <tr>
                            <td><?= $model_warehouse_product[$i]['product_name'] ?></td>
                            <td><?= $model_warehouse_product[$i]['description'] ?></td>
                            <td><?= number_format($model_warehouse_product[$i]['qty'], 0) ?></td>
                        </tr>
                    <?php endfor; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="col-lg-1"></div>
    </div>
    <?php ActiveForm::end(); ?>

</div>
