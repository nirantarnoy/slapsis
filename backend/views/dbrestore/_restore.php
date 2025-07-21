<?php

use yii\web\Session;
$this->title = 'Restore ข้อมูล';
$this->params['breadcrumbs'][] = '/ '.$this->title;

?>
<?php if(Yii::$app->session->hasFlash('msg')): ?>
   <div class="alert alert-success">
       <?=\Yii::$app->session->getFlash('msg')?>
   </div>
<?php endif;?>
<br>
<div class="panel panel-headline">
<!--    <div class="panel-heading">-->
<!--        <br>-->
<!--        <h3>Restore ข้อมูล</h3>-->
<!--    </div>-->
    <div class="panel-body">
        <div class="row">
            <div class="col-lg-12">

                <form method="post" action="<?= \yii\helpers\Url::to(['dbrestore/restoredb'], true) ?>"
                      enctype="multipart/form-data" id="form-restore">
                    <label for="">เลือกไฟล์ที่ต้องการกู้คืนข้อมูล</label>
                    <input type="file" name="restore_file" value="" accept=".sql" class="form-control">
                    <br>
                    <input type="submit" class="btn btn-success" value="ตกลง">
                </form>

            </div>
        </div>
        <br>
    </div>
</div>
