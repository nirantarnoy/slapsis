<?php

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var backend\models\Product $model */
/** @var yii\widgets\ActiveForm $form */
$data_warehouse = null;

$yesno = [['id' => 1, 'YES'], ['id' => 0, 'NO']];

$model_warehouse_product = null;


?>

    <div class="product-form">

        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
        <input type="hidden" class="remove-list" name="remove_list" value="">
        <input type="hidden" class="remove-customer-list" name="remove_customer_list" value="">
        <div class="row">
            <div class="col-lg-3">
                <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
            </div>
            <div class="col-lg-3">
                <?= $form->field($model, 'product_group_id')->widget(\kartik\select2\Select2::className(), [
                    'data' => \yii\helpers\ArrayHelper::map(\backend\models\Productgroup::find()->all(), 'id', 'name'),
                    'options' => [

                    ],
                    'pluginOptions' => [
                        'allowClear' => true,
                    ]
                ]) ?>
            </div>
            <div class="col-lg-3">
                <?php echo $form->field($model, 'status')->widget(\toxor88\switchery\Switchery::className(), ['options' => ['label' => '', 'class' => 'form-control']])->label() ?>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <?= $form->field($model, 'description')->textarea(['maxlength' => true]) ?>
            </div>

        </div>
        <div class="row">
            <div class="col-lg-3">
                <input type="hidden" name="old_photo" value="<?= $model->photo ?>">
            </div>
        </div>
        <br/>

        <br/>
        <div class="form-group">
            <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
    <div class="row">
        <form action="<?= \yii\helpers\Url::to(['product/importproduct'], true) ?>" method="post"
              enctype="multipart/form-data">
            <input type="file" name="file_product" class="form-control">
            <button class="btn btn-success">Import</button>
        </form>
    </div>
<?php
$js = <<<JS
var removelist = [];
var removecustomerpricelist = [];
$(function(){
  // $(".line-exp-date").datepicker(); 
});
function addline(e){
    var tr = $("#table-list tbody tr:last");
    var clone = tr.clone();
                    //clone.find(":text").val("");
                    // clone.find("td:eq(1)").text("");
    clone.find(".line-warehouse-id").val("-1").change();
    clone.find(".line-qty").val("");
    clone.find(".line-exp-date").val("");
    clone.find(".line-rec-id").val("0");

    tr.after(clone);
     
}
function removeline(e) {
        if (confirm("ต้องการลบรายการนี้ใช่หรือไม่?")) {
            if (e.parent().parent().attr("data-var") != '') {
                removelist.push(e.parent().parent().attr("data-var"));
                $(".remove-list").val(removelist);
            }
            // alert(removelist);
            // alert(e.parent().parent().attr("data-var"));

            if ($("#table-list tbody tr").length == 1) {
                $("#table-list tbody tr").each(function () {
                    $(this).find(":text").val("");
                    $(this).find(".line-warehouse-id").val("-1").change();
                    $(this).find(".line-qty").val("");
                    $(this).find(".line-exp-date").val("");
                    $(this).find(".line-rec-id").val("0");
                });
            } else {
                e.parent().parent().remove();
            }
            // cal_linenum();
            // cal_all();
        }
}
function removecustomerpriceline(e) {
        if (confirm("ต้องการลบรายการนี้ใช่หรือไม่?")) {
            if (e.parent().parent().attr("data-var") != '') {
                removecustomerpricelist.push(e.parent().parent().attr("data-var"));
                $(".remove-customer-list").val(removecustomerpricelist);
            }
            // alert(removelist);
            // alert(e.parent().parent().attr("data-var"));

            if ($("#table-list2 tbody tr").length == 1) {
                $("#table-list2 tbody tr").each(function () {
                    $(this).find(":text").val("");
                    $(this).find(".line-product-customer-id").val("-1").change();
                    $(this).find(".line-customer-price").val("0");
                });
            } else {
                e.parent().parent().remove();
            }
            // cal_linenum();
            // cal_all();
        }
}
function addcustomerpriceline(e){
    var tr = $("#table-list2 tbody tr:last");
    var clone = tr.clone();
                    //clone.find(":text").val("");
                    // clone.find("td:eq(1)").text("");
    clone.find(".line-product-customer-id").val("-1").change();
    clone.find(".line-customer-price").val("0");

    tr.after(clone);
     
}
JS;
$this->registerJs($js, static::POS_END);
?>