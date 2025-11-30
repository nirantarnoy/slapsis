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
//if(isset($_SESSION['user_group_id'])){
//    $has_group = $_SESSION['user_group_id'];
//}

//if (!empty(\Yii::$app->user->identity->group_id)) {
//    $has_group = \Yii::$app->user->identity->group_id;
//}

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= "MMC" ?></title>
    <link rel="shortcut icon" href="<?php echo Yii::$app->getUrlManager()->baseUrl; ?>/sst.ico" type="image/x-icon"/>

    <link rel="stylesheet" href="plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">

    <link rel="stylesheet" href="plugins/toastr/toastr.min.css">
    <!-- Google Font: Prompt -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">

<!--    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.2/css/bootstrap-select.min.css">-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">

    <link rel="stylesheet" href="css/sweetalert.css">
    <?php $this->head() ?>
    <style>
        body {
            font-family: "Prompt", sans-serif;
            font-size: 16px;
        }

        @media print {
            @page {
                size: A4 landscape;
            }
            body {
                font-size: 9px;
                writing-mode: horizontal-tb;
                /*transform: rotate(90deg);*/
            }
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

<!-- jQuery -->
<!--<script src="plugins/jquery/jquery.min.js"></script>-->
<!-- jQuery UI 1.11.4 -->
<script src="plugins/jquery-ui/jquery-ui.min.js"></script>

<script src="plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
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
<script src="js/jspdf.js"></script>
<!--<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.2/js/bootstrap-select.min.js"></script>-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>

<script>
    var cururl = $("#current-url").val();
    $(function () {


        //---- active menu
        $("#perpage").change(function () {
            $("#form-perpage").submit();
        });

        if (cururl == 'pos' || cururl == 'orders' || cururl == 'salereport' || cururl == 'salereportemp') {
            $(".sidebar-mini").removeClass('layout-fixed');
            $(".sidebar-mini").addClass('sidebar-collapse');
        }

        //     var xx = $(".nav-sidebar").find(".nav-item").find("."+cururl+"").find(".nav-link").parent().parent().attr("class");
        $("ul.nav-sidebar li").each(function (index) {
            var cli = $(this).attr("class");
            var list_class = cli.split(" ");
            //console.log(list_class);
            if ($.inArray("has-sub", list_class) !== -1) {
                $(this).find(".nav-treeview").find(".nav-item").find("." + cururl).addClass("active");
                $(this).find(".nav-treeview").find(".nav-item").find("." + cururl).parent().parent().parent().find(".nav-link").trigger("click");
                //console.log(x);
            } else {
                $(this).find("." + cururl).addClass("active");
            }

        });
        //--- end active menu

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-center',
            showConfirmButton: false,
            timer: 3000
        });

        $("#btn-show-alert").click(function () {
            var msg = $(".alert-msg").val();
            var msg_error = $(".alert-msg-error").val();
            //alert(msg);
            if (msg != '' && typeof (msg) !== "undefined") {
                //alert(msg);
                Toast.fire({
                    type: 'success',
                    title: msg
                })
            }
            if (msg_error != '' && typeof (msg_error) !== "undefined") {
                Toast.fire({
                    type: 'error',
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
