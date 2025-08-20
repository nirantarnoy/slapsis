<?php

use yii\helpers\Html;
use backend\assets\AppAsset;
use yii\web\Session;

$session = \Yii::$app->session;

AppAsset::register($this);

\hail812\adminlte3\assets\FontAwesomeAsset::register($this);
\hail812\adminlte3\assets\AdminLteAsset::register($this);

$assetDir = Yii::$app->assetManager->getPublishedUrl('@vendor/almasaeed2010/adminlte/dist');
$cururl = Yii::$app->controller->id;

$has_group = '';
$has_second_user = '';
$is_pos_user = 0;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= "Slapsis" ?></title>
    <link rel="shortcut icon" href="<?= Yii::getAlias('@web') ?>/sst.ico" type="image/x-icon"/>

    <!-- Local CSS -->
    <link rel="stylesheet" href="<?= Yii::getAlias('@web') ?>/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
    <link rel="stylesheet" href="<?= Yii::getAlias('@web') ?>/plugins/toastr/toastr.min.css">
    <link rel="stylesheet" href="<?= Yii::getAlias('@web') ?>/css/sweetalert.css">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">

    <!-- CDN CSS -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">

    <?php $this->head() ?>
    <style>
        @font-face {
            font-family: 'Kanit-Regular';
            src: url('<?= Yii::getAlias('@web') ?>/fonts/Kanit-Regular.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        body {
            font-family: "Kanit-Regular";
            font-size: 16px;
        }

        .help-block {
            color: red;
        }

        .my-br {
            margin-top: 10px;
        }

        .product-items:hover {
            -webkit-transform: scale(1.1);
            transform: scale(1.1);
        }
        input[type=checkbox] {
            transform: scale(1.5);
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
<input type="hidden" id="current-url" value="<?= $cururl ?>">

<?php $this->beginBody() ?>
<div class="wrapper">
    <!-- Navbar -->
    <?= $this->render('navbar', ['assetDir' => $assetDir]) ?>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <?= $this->render('sidebar', ['assetDir' => $assetDir]) ?>
    <section class="content" style="background-color: #ffffff;">
        <!-- Content Wrapper. Contains page content -->
        <?= $this->render('content', ['content' => $content, 'assetDir' => $assetDir]) ?>
        <!-- /.content-wrapper -->
    </section>

    <!-- Control Sidebar -->
    <?= $this->render('control-sidebar') ?>
    <!-- /.control-sidebar -->

    <!-- Main Footer -->
    <?= $this->render('footer') ?>
</div>

<?php $this->endBody() ?>

<!-- Local JS -->
<script src="<?= Yii::getAlias('@web') ?>/plugins/jquery-ui/jquery-ui.min.js"></script>
<script src="<?= Yii::getAlias('@web') ?>/plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
<script src="<?= Yii::getAlias('@web') ?>/plugins/sweetalert2/sweetalert2.min.js"></script>
<script src="<?= Yii::getAlias('@web') ?>/plugins/toastr/toastr.min.js"></script>
<script src="<?= Yii::getAlias('@web') ?>/js/sweetalert.min.js"></script>
<script src="<?= Yii::getAlias('@web') ?>/plugins/chart.js/Chart.min.js"></script>
<script src="<?= Yii::getAlias('@web') ?>/dist/js/demo.js"></script>
<script src="<?= Yii::getAlias('@web') ?>/dist/js/pages/dashboard3.js"></script>
<script src="<?= Yii::getAlias('@web') ?>/js/module_index_delete.js"></script>
<script src="<?= Yii::getAlias('@web') ?>/js/jspdf.js"></script>

<!-- CDN JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>

<script>
    var cururl = $("#current-url").val();
    $(function () {
        $("#perpage").change(function () {
            $("#form-perpage").submit();
        });

        $("ul.nav-sidebar li").each(function () {
            let $this = $(this);
            let targetClass = cururl;

            if(cururl.includes('job')){
                targetClass = 'jobmain';
            }

            if ($this.hasClass("has-sub")) {
                let $target = $this.find(".nav-link." + targetClass);
                if ($target.length > 0) {
                    $target.addClass("active");
                    $this.addClass("menu-open");
                    $this.children("a.nav-link").addClass("active");
                }
            } else {
                let $target = $this.find("a.nav-link." + targetClass);
                if ($target.length > 0) {
                    $target.addClass("active");
                }
            }
        });

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-center',
            showConfirmButton: false,
            timer: 3000
        });

        $("#btn-show-alert").click(function () {
            var msg = $(".alert-msg").val();
            var msg_error = $(".alert-msg-error").val();
            if (msg != '' && typeof (msg) !== "undefined") {
                Toast.fire({
                    icon: 'success',
                    title: msg
                })
            }
            if (msg_error != '' && typeof (msg_error) !== "undefined") {
                Toast.fire({
                    icon: 'error',
                    title: msg_error
                })
            }
        })

        $("#btn-show-alert").trigger("click");
    });
</script>

</body>
</html>
<?php $this->endPage() ?>
