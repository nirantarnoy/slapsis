<?php

use yii\widgets\ActiveForm;
use yii\helpers\Url;

$session = new Yii::$app->session();
$this->title = "เปลี่ยนรหัสผ่านผู้ใช้งาน";

?>
<div class="panel panel-headline">
    <div class="panel-heading">
        <div class="x_title">
            <!-- <ul class="nav navbar-right panel_toolbox">
              <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
              </li>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                <ul class="dropdown-menu" role="menu">
                  <li><a href="#">Settings 1</a>
                  </li>
                  <li><a href="#">Settings 2</a>
                  </li>
                </ul>
              </li>
              <li><a class="close-link"><i class="fa fa-close"></i></a>
              </li>
            </ul> -->

            <div class="clearfix"></div>
        </div>
        <div class="panel-body">
            <br/>
            <?php $form = ActiveForm::begin() ?>
            <div class="row">
                <div class="col-lg-3"></div>
                <div class="col-lg-6">
                    <?php if ($session->getFlash('msg_err') != null) {
                        echo "<div class='alert alert-danger'>" . $session->getFlash('msg_err') .
                            " <button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">
    <span aria-hidden=\"true\">&times;</span>
  </button>" .
                            "</div>";
                    } ?>

                    <?php
                    echo $form->field($model, 'oldpw')->passwordInput()->label();

                    ?>
                    <?php echo $form->field($model, 'newpw')->passwordInput()->label() ?>
                    <?php echo $form->field($model, 'confirmpw')->passwordInput()->label() ?>
                    <div class="form-group">
                        <input type="submit" value="ตกลง" class="btn btn-success">
                    </div>

                </div>
                <div class="col-lg-3"></div>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
