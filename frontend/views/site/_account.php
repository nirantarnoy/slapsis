<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'บัญชีของฉัน';
$this->params['breadcrumbs'][] = $this->title;

if($model!=null){
    echo $model->id;
}
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
        <div><b>ข้อมูลส่วนตัว</b></div>
        <br/>
        <?php $form = ActiveForm::begin(); ?>
        <div style="border: 1px solid #95a5a6;padding: 15px;">
            <div class="row">
                <div class="col-lg-4">
                    <label for="">ชื่อ</label>
                    <?= $form->field($model, 'first_name')->textInput(['maxlength' => true])->label(false) ?>
                </div>
                <div class="col-lg-4">
                    <label for="">นามสกุล</label>
                    <?= $form->field($model, 'last_name')->textInput(['maxlength' => true])->label(false) ?>
                </div>
            </div>
            <br/>
            <div class="row">
                <div class="col-lg-4">
                    <label for="">อีเมล์</label>
                    <?= $form->field($model, 'email')->textInput(['maxlength' => true])->label(false) ?>
                </div>
                <div class="col-lg-4">

                </div>
            </div>
            <br/>
            <div class="row">
                <div class="col-lg-4">
                    <label for="">เบอร์โทร</label>
                    <?= $form->field($model, 'phone')->textInput(['maxlength' => true])->label(false) ?>
                </div>
            </div>
            <br/>
            <hr/>
            <br/>
            <div class="row">
                <div class="col-lg-4">
                    <div class="form-group">
                        <?= Html::submitButton('บันทึกการแก้ไข', ['class' => 'btn btn-success']) ?>
                    </div>
                </div>
                <div class="col-lg-4">

                </div>
            </div>
            <br/>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</div>