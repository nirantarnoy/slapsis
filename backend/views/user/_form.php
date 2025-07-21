<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\User */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="user-form">

    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-lg-1"></div>
        <div class="col-lg-5">
            <?= $form->field($model, 'username')->textInput() ?>

            <?php if ($model->isNewRecord): ?>
                <?= $form->field($model, 'pwd')->passwordInput()->label('Password') ?>
            <?php endif; ?>
            <?= $form->field($model, 'user_group_id')->widget(\kartik\select2\Select2::className(), [
                'data' => \yii\helpers\ArrayHelper::map(\backend\models\Usergroup::find()->all(), 'id', function ($data) {
                    return $data->name;
                }),
                'options' => [
                    'placeholder' => '--เลือกกลุ่มผู้ใช้งาน--'
                ]
            ])->label('กลุ่มผู้ใช้งาน') ?>
            <?= $form->field($model, 'emp_ref_id')->widget(\kartik\select2\Select2::className(), [
                'data' => \yii\helpers\ArrayHelper::map(\backend\models\Employee::find()->all(), 'id', function ($data) {
                    return $data->fname;
                }),
                'options' => [
                    'placeholder' => '--เลือกพนักงาน--'
                ]
            ])->label('พนักงาน') ?>


            <?= $form->field($model, 'status')->widget(\toxor88\switchery\Switchery::className())->label(false) ?>

            <?php //echo $form->field($model, 'roles')->checkboxList($model->getAllRoles())->label(false) ?>

            <div class="form-group">
                <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
            </div>
        </div>
        <div class="col-lg-1"></div>
        <div class="col-lg-5">
<!--            <div class="row">-->
<!--                <div class="col-lg-12">-->
<!--                    <h3></h3>-->
<!--                </div>-->
<!--            </div>-->
<!--            <br />-->
            <div class="row">
                <div class="col-lg-5">
                    <?php //print_r($model->roles)?>
                    <?php echo $form->field($model, 'roles')->checkboxList($model->getAllRoles(),[

                        'separator' => '<br>',
                        'itemOptions' => [
                            'class' => 'roles'
                        ]
                    ])->label('<label style="color: red">สิทธิ์การเข้าถึงระบบ</label>') ?>
                </div>
            </div>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
    <?php
    $url_to_show_branch = \yii\helpers\Url::to(['warehouse/findbranch'],true);
    $js=<<<JS
$(function(){
    //alert();
});

function showbranch(e){
    var com_id = e.val();
    if(com_id){
             $.ajax({
              'type':'post',
              'dataType': 'html',
              'async': false,
              'url': "$url_to_show_branch",
              'data': {'com_id': com_id},
              'success': function(data) {
                 // alert(data);
                 $(".warehouse-branc-id").html(data);
                 }
              });
    }
     
}
JS;
    $this->registerJs($js,static::POS_END);
    ?>
