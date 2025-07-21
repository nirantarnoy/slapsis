<?php

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;

$company_id = 1;
$branch_id = 1;
if (!empty(\Yii::$app->user->identity->company_id)) {
    $company_id = \Yii::$app->user->identity->company_id;
}
if (!empty(\Yii::$app->user->identity->branch_id)) {
    $branch_id = \Yii::$app->user->identity->branch_id;
}

$drivingcard_data = \backend\helpers\DrivingcardType::asArrayObject();

?>

<div class="employee-form">

    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

    <input type="hidden" class="remove-list" name="remove_list" value="">
    <div class="row">
        <div class="col-lg-1">

        </div>
        <div class="col-lg-10">
            <div class="row">
                <div class="col-lg-4">
                    <?= $form->field($model, 'fname')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-lg-4">
                    <?= $form->field($model, 'lname')->textInput(['maxlength' => true]) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4">
                    <?= $form->field($model, 'position_id')->widget(\kartik\select2\Select2::className(), [
                        'data' => ArrayHelper::map(\backend\models\Position::find()->all(), 'id', 'name'),
                        'options' => [
                            'placeholder' => '-- เลือกตําแหน่ง --',
                        ],
                        'pluginOptions' => [
                            'allowClear' => true,
                        ],
                    ]) ?>
                </div>
            </div>

            <br>
            <div class="row">
                <div class="col-lg-4">
                    <?php echo $form->field($model, 'status')->widget(\toxor88\switchery\Switchery::className(), ['options' => ['label' => '', 'class' => 'form-control']])->label() ?>

                </div>
            </div>

            <br>

            <div class="form-group">
                <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
            </div>
        </div>

        <div class="col-lg-1">

        </div>
        <?php ActiveForm::end(); ?>

    </div>
    <form id="form-delete-photo" action="<?= \yii\helpers\Url::to(['employee/deletephoto'], true) ?>" method="post">
        <input type="hidden" class="delete-photo-id" name="delete_id" value="">
    </form>
    <?php

    $js = <<<JS
var removelist = [];

$(function(){
    // $('.start-date').datepicker({dateformat: 'dd-mm-yy'});
    // $('.expire-date').datepicker({dateFormat: 'dd-mm-yy'});
});
$(".btn-delete-photo").click(function (){
        var prodid = $(this).attr('data-var');
       //alert(prodid);
      swal({
                title: "ต้องการทำรายการนี้ใช่หรือไม่",
                text: "",
                type: "warning",
                showCancelButton: true,
                closeOnConfirm: true,
                showLoaderOnConfirm: true
               }, function () {
                  $(".delete-photo-id").val(prodid);
                  $("#form-delete-photo").submit();
         });
     });

function addline(e){
    var tr = $("#table-list tbody tr:last");
                    var clone = tr.clone();
                    //clone.find(":text").val("");
                    // clone.find("td:eq(1)").text("");
                    clone.find(".card-type").val("");
                    clone.find(".card-no").val("");
                    clone.find(".start-date").val("");
                    clone.find(".expire-date").val("");
                  
                    clone.attr("data-var", "");
                    clone.find('.rec-id').val("");
                    
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
                   // $(this).find(".line-prod-photo").attr('src', '');
                    $(this).find(".line-price").val(0);
                    // cal_num();
                });
            } else {
                e.parent().parent().remove();
            }
            // cal_linenum();
            // cal_all();
        }
    }

JS;

    $this->registerJs($js, static::POS_END);

    ?>
