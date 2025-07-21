<?php

use yii\widgets\Breadcrumbs;
use backend\assets\AppAsset;

AppAsset::register($this);

$cururl = Yii::$app->controller->id;
?>
<?php $this->beginPage()?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>MHEEWATCH</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- Tempusdominus Bbootstrap 4 -->
    <link rel="stylesheet" href="plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <!-- iCheck -->
    <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- JQVMap -->
    <link rel="stylesheet" href="plugins/jqvmap/jqvmap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <!-- Daterange picker -->
    <link rel="stylesheet" href="plugins/daterangepicker/daterangepicker.css">
    <!-- summernote -->
    <link rel="stylesheet" href="plugins/summernote/summernote-bs4.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?= "css/sweetalert.css" ?> ">

    <style>
        @font-face {
            font-family: 'Kanit-Regular';
            src: url('../../backend/web/fonts/Kanit-Regular.ttf') format('truetype');
            /* src: url('../fonts/thsarabunnew-webfont.eot?#iefix') format('embedded-opentype'),
                  url('../fonts/thsarabunnew-webfont.woff') format('woff'),
                  url('../fonts/EkkamaiStandard-Light.ttf') format('truetype');*/
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
    </style>


    <!-- jQuery -->
    <script src="plugins/jquery/jquery.min.js"></script>
    <!-- jQuery UI 1.11.4 -->
    <script src="plugins/jquery-ui/jquery-ui.min.js"></script>
    <!-- Select2 -->
    <script src="plugins/select2/js/select2.full.min.js"></script>
    <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
    <script>
        // $.widget.bridge('uibutton', $.ui.button)
    </script>
    <!-- Bootstrap 4 -->
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- ChartJS -->
    <script src="plugins/chart.js/Chart.min.js"></script>
    <!-- Sparkline -->
    <script src="plugins/sparklines/sparkline.js"></script>
    <!-- JQVMap -->
    <script src="plugins/jqvmap/jquery.vmap.min.js"></script>
    <script src="plugins/jqvmap/maps/jquery.vmap.usa.js"></script>
    <!-- jQuery Knob Chart -->
    <script src="plugins/jquery-knob/jquery.knob.min.js"></script>
    <!-- daterangepicker -->
    <script src="plugins/moment/moment.min.js"></script>
    <script src="plugins/daterangepicker/daterangepicker.js"></script>
    <!-- Tempusdominus Bootstrap 4 -->
    <script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
    <!-- Summernote -->
    <script src="plugins/summernote/summernote-bs4.min.js"></script>
    <!-- overlayScrollbars -->
    <script src="plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
    <!-- AdminLTE App -->
    <script src="dist/js/adminlte.js"></script>
    <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
    <script src="dist/js/pages/dashboard.js"></script>
    <!-- AdminLTE for demo purposes -->
    <script src="dist/js/demo.js"></script>
    <!-- Bootstrap Switch -->
    <script src="plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
    <script src="js/module_index_delete.js"></script>
    <script src="js/sweetalert.min.js"></script>

</head>
<body class="hold-transition sidebar-mini layout-fixed">
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
<!-- ./wrapper -->


<script>
    var cururl = $("#current-url").val();
    $(function () {
        //---- active menu
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

    });
</script>


<!-- OPTIONAL SCRIPTS -->
<script src="plugins/chart.js/Chart.min.js"></script>
<script src="dist/js/demo.js"></script>
<script src="dist/js/pages/dashboard3.js"></script>

</body>
</html>
<?php $this->endPage() ?>
