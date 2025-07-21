<?php
$this->title = 'สำรองข้อมูล';
$this->params['breadcrumbs'][] = '/ '.$this->title;
?>
<br>
<div class="panel panel-headline">
<!--    <div class="panel-heading">-->
<!--        <h3>สำรองข้อมูล</h3>-->
<!--    </div>-->

    <div class="panel-body">
        <div class="row">
            <div class="col-lg-3">
                <form action="<?=\yii\helpers\Url::to(['dbbackup/exrestore'],true)?>" method="post">
                    <input type="submit" class="btn btn-info" value="Backup Data">
                </form>

            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-lg-12">
                <table class="table">
                    <thead>
                    <tr>
                        <th>ชื่อไฟล์</th>
                        <th>วันที่</th>
                        <th>ขนาด</th>
                        <th>ดาวน์โหลด</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach (glob("../web/uploads/backup/*.sql") as $file): ?>
                        <tr>
                            <td><?= basename($file) ?></td>
                            <td><?= date('d/m/Y H:m:s', filectime($file)) ?></td>
                            <td><?= number_format(((filesize($file) / 1024)/1024), 2). 'MB' ?></td>
                            <td>
                                <a href="<?=\yii\helpers\Url::to(['dbbackup/downloadbak','id'=>basename($file)],true)?>" class="btn btn-success"> <i class="fa fa-download"></i> ดาวน์โหลดไฟล์</a>
                            </td>
                            <td>
                                <a href="<?=\yii\helpers\Url::to(['dbbackup/deletebak','id'=>basename($file)],true)?>" class="btn btn-danger"> ลบ</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
