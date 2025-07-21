<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model backend\models\Authitem */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="authitem-form">
    <div class="panel panel-headlin">

        <div class="panel-body">
            <?php $form = ActiveForm::begin(); ?>

            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

            <?php echo $form->field($model, 'type')->widget(Select2::className(), [
                'data' => ArrayHelper::map(\backend\helpers\AuthType::asArrayObject(), 'id', 'name'),
            ]) ?>

            <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

            <?php
            if (!$model->isNewRecord) {
                $datalist = [];
                foreach ($modelchild as $value) {
                    array_push($datalist, $value->child);
                }
                $model->child_list = $datalist;
            }
            ?>

            <?= $form->field($model, 'child_list')->widget(\kartik\select2\Select2::className(), [
                'data' => \yii\helpers\ArrayHelper::map(\backend\models\Authitem::find()->all(), 'name', 'name'),
                'theme' => Select2::THEME_MATERIAL,
           //     'theme' => Select2::THEME_DEFAULT,
       //         'theme' => Select2::THEME_BOOTSTRAP,
                //     'theme' => Select2::THEME_CLASSIC,
                'options' => [
                        'placeholder' => 'Select ...',
                        'multiple' => true
                ],
                'pluginOptions' => [
                    'tags' => true,
                    'tokenSeparators' => [',', ' '],
                    'maximumInputLength' => 100
                ],
            ]) ?>



            <?php //echo $form->field($model, 'rule_name')->textInput(['maxlength' => true]) ?>

            <?php //echo $form->field($model, 'data')->textInput() ?>


            <div class="form-group">
                <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
