<?php
$company_id = 0;
$branch_id = 0;


use yii\bootstrap4\Breadcrumbs;
use yii\web\Session;

?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h2><?= $this->title; ?></h2>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <?php
                    echo Breadcrumbs::widget([
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        'options' => [
                            'class' => 'float-sm-right'
                        ]
                    ]);
                    ?>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
    <!-- Main content -->
    <div class="content" style="padding: 15px;background-color: white">
        <div id="btn-show-alert"></div>
        <?php $session = \Yii::$app->session;
        if ($session->getFlash('msg')): ?>
            <input type="hidden" class="alert-msg" value="<?= $session->getFlash('msg'); ?>">
        <?php endif; ?>
        <?php if ($session->getFlash('msg-error')): ?>
            <input type="hidden" class="alert-msg-error" value="<?= $session->getFlash('msg-error'); ?>">
        <?php endif; ?>
        <form action="" id="form-delete" method="post"></form>

        <?= $content ?><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
</div>


