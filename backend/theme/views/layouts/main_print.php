<?php

use yii\helpers\Html;

use backend\assets\AppAsset;

AppAsset::register($this);

\hail812\adminlte3\assets\FontAwesomeAsset::register($this);
\hail812\adminlte3\assets\AdminLteAsset::register($this);

$assetDir = Yii::$app->assetManager->getPublishedUrl('@vendor/almasaeed2010/adminlte/dist');
$cururl = Yii::$app->controller->id;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= "mmc" ?></title>
    <link rel="shortcut icon" href="<?php echo Yii::$app->getUrlManager()->baseUrl; ?>/sst.ico" type="image/x-icon"/>

    <link rel="stylesheet" href="plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">

    <link rel="stylesheet" href="plugins/toastr/toastr.min.css">
    <!-- Google Font: Prompt -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">

    <link rel="stylesheet" href="css/sweetalert.css">
    <?php $this->head() ?>
    <style>
        body {
            font-family: "Prompt", sans-serif;
            font-size: 16px;
        }


        /*.pagination li {*/
        /*    padding: 10px;*/
        /*}*/

        /*.pagination li.active {*/
        /*    background-color: #2e6da4;*/
        /*}*/

        /*.pagination li.active a {*/
        /*    color: white;*/
        /*}*/

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
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<input type="hidden" id="current-url" value="<?= $cururl ?>">
<?php $this->beginBody() ?>

<div class="wrapper">
    <!-- Content Wrapper. Contains page content -->
    <div class="content">

        <!-- Content Header (Page header) -->
        <!-- Main content -->
        <?php $this->beginBody() ?>
        <section class="content" style="background-color: #ffffff;">
            <div class="container-fluid">
                <?php echo $content ?>
            </div>
        </section>
        <?php $this->endBody(); ?>
        <!-- /.content -->
    </div>
</div>

<?php $this->endBody() ?>

<!-- jQuery -->
<!--<script src="plugins/jquery/jquery.min.js"></script>-->
<!-- jQuery UI 1.11.4 -->
<script src="plugins/jquery-ui/jquery-ui.min.js"></script>

<script src="plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

<script src="plugins/print-this/printThis.js"></script>
<!-- SweetAlert2 -->
<script src="plugins/sweetalert2/sweetalert2.min.js"></script>
<!-- Toastr -->
<script src="plugins/toastr/toastr.min.js"></script>

<script src="js/sweetalert.min.js"></script>

<!-- OPTIONAL SCRIPTS -->
<script src="plugins/chart.js/Chart.min.js"></script>
<script src="dist/js/demo.js"></script>
<script src="dist/js/pages/dashboard3.js"></script>
<script src="js/module_index_delete.js"></script>


</body>
</html>
<?php $this->endPage() ?>
