<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \frontend\models\PasswordResetRequestForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Request password reset';
$this->params['breadcrumbs'][] = '/'.$this->title;
?>
<div class="row">
    <div class="col-lg-2"></div>
    <div class="col-lg-8">
        <div class="site-request-password-reset">
            <h1><?= Html::encode($this->title) ?></h1>

            <p style="font-size: 18px;">กรุณากรอก Email ของคุณเพื่อให้ทางเราส่งลิงค์สำหรับเปลี่ยนรหัสผ่านไปให้</p>

            <div class="row">
                <div class="col-lg-5">
                    <?php $form = ActiveForm::begin(['id' => 'request-password-reset-form']); ?>

                    <?= $form->field($model, 'email')->textInput(['autofocus' => true]) ?>

                    <div class="form-group">
                        <?= Html::submitButton('ตกลง', ['class' => 'btn btn-primary']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
        <hr>
        <footer class="main-footer2">
            <strong>Copyright &copy; 2019 <a href="#">Yourscommerce</a>.</strong>
            All rights reserved.
            <div class="float-right d-none d-sm-inline-block">
                <b>Version</b> 1.0
            </div>
        </footer>
    </div>
    <div class="col-lg-2"></div>
</div>


