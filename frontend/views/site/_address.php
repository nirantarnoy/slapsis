<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'บัญชีของฉัน';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
    <div class="col-lg-3">
        <div><b>บัญชีของฉัน</b></div>
        <br/>
        <table class="table">
            <tr>
                <td style="border: 1px solid lightgrey"><a href="index.php?r=site/profile"
                                                           style="text-decoration: none;color: grey;">ข้อมูลส่วนตัว</a>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid lightgrey"><a href="index.php?r=site/addressinfo"
                                                           style="text-decoration: none;color: grey;">ที่อยู่สำหรับจัดส่งสินค้า</a>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid lightgrey"><a href="index.php?r=site/myorder"
                                                           style="text-decoration: none;color: grey;">การสั่งซื้อของฉัน</a>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid lightgrey"><a href="index.php?r=site/logout"
                                                           style="text-decoration: none;color: red;">ออกจากระบบ</a></td>
            </tr>
        </table>
    </div>
    <div class="col-lg-9">
        <div><b>ที่อยู่สำหรับการจัดส่ง</b></div>
        <br/>
        <?php $form = ActiveForm::begin(); ?>
        <?= $form->field($model, 'party_id')->hiddenInput(['maxlength' => true,'value'=>$party_id])->label(false) ?>
        <div style="border: 1px solid #95a5a6;padding: 15px;">
            <div class="row">
                <div class="col-lg-8">
                    <label for="">ที่อยู่</label>
                    <?= $form->field($model, 'address')->textarea(['maxlength' => true])->label(false) ?>
                </div>

            </div>
            <br/>
            <div class="row">
                <div class="col-lg-4">
                    <label for="">ถนน</label>
                    <?= $form->field($model, 'street')->textInput(['maxlength' => true])->label(false) ?>
                </div>
                <div class="col-lg-4">

                </div>
            </div>
            <br/>
            <div class="row">
                <div class="col-lg-4">
                    <label for="">ตำบล</label>
                    <?= $form->field($model, 'district_id')->widget(\kartik\select2\Select2::className(), [
                        'data' => \yii\helpers\ArrayHelper::map(\backend\models\District::find()->all(), 'DISTRICT_ID', 'DISTRICT_NAME'),
                        'options' => [
                            'id' => 'district-id',
                            'placeholder' => 'เลือกตำบล',
                        ]
                    ])->label(false) ?>
                </div>
                <div class="col-lg-4">
                    <label for="">อำเภอ</label>
                    <?= $form->field($model, 'city_id')->widget(\kartik\select2\Select2::className(), [
                        'data' => \yii\helpers\ArrayHelper::map(\backend\models\Amphur::find()->all(), 'AMPHUR_ID', 'AMPHUR_NAME'),
                        'options' => [
                            'id' => 'city',
                            'placeholder' => 'เลือกอำเภอ',
                            'onchange' => 'getDistrict($(this))',
                        ]
                    ])->label(false) ?>
                </div>
            </div>
            <br/>
            <div class="row">
                <div class="col-lg-4">
                    <label for="">จังหวัด</label>
                    <?= $form->field($model, 'province_id')->widget(\kartik\select2\Select2::className(), [
                        'data' => \yii\helpers\ArrayHelper::map(\backend\models\Province::find()->all(), 'PROVINCE_ID', 'PROVINCE_NAME'),
                        'options' => [
                            'placeholder' => 'เลือกจังหวัด',
                            'onchange' => 'getCity($(this))',
                        ]
                    ])->label(false) ?>
                </div>
                <div class="col-lg-4">

                </div>
            </div>
            <br/>
            <div class="row">
                <div class="col-lg-4">
                    <label for="">รหัสไปรษณีย์</label>
                    <?= $form->field($model, 'zipcode')->textInput(['maxlength' => true,'id'=>'zipcode','readonly' => 'readonly'])->label(false) ?>
                </div>
                <div class="col-lg-4">

                </div>
            </div>
            <br/>
            <hr/>
            <br/>
            <div class="row">
                <div class="col-lg-4">
                    <?php if($party_id == 0):?>
                    <i style="color: red;">กรุณากรอกข้อมูลส่วนตัวให้ครบก่อนกรอกข้อมูลที่อยู่</i>
                    <?php else:?>
                    <?= Html::submitButton('บันทึกการแก้ไข', ['class' => 'btn btn-success']) ?>
                    <?php endif;?>
                </div>
                <div class="col-lg-4">

                </div>
            </div>
            <br/>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>
<?php
$url_to_getcity = \yii\helpers\Url::to(['site/showcity'], true);
$url_to_getdistrict = \yii\helpers\Url::to(['site/showdistrict'], true);
$url_to_getzipcode = \yii\helpers\Url::to(['site/showzipcode'], true);
$url_to_getAddress = \yii\helpers\Url::to(['site/showaddress'], true);

$js = <<<JS
var removelist = [];

$(function(){
    
});

function getCity(e){
    $.post("$url_to_getcity"+"&id="+e.val(),function(data){
        $("select#city").html(data);
        $("select#city").prop("disabled","");
    });
}

function getDistrict(e){
    $.post("$url_to_getdistrict"+"&id="+e.val(),
    function(data){
        $("select#district-id").html(data);
        $("select#district-id").prop("disabled","");
    });
    $.post("$url_to_getzipcode"+"&id="+e.val(),function(data){
        $("#zipcode").val(data);
    });
}

function getAddres(e){
    $.post("$url_to_getAddress"+"&id="+e.val(),function(data){
        $("#city").html(data);
        $("select#city").prop("disabled","");
    });
}

JS;
$this->registerJs($js, static::POS_END);
?>

