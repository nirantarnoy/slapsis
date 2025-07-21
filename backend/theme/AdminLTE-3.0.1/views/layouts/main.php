<?php

use yii\widgets\Breadcrumbs;
use backend\assets\AppAsset;

AppAsset::register($this);

$cururl = Yii::$app->controller->id;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <link rel="shortcut icon" href="<?php echo Yii::$app->request->baseUrl; ?>/uploads/logo/logo_head_icon_16.ico"
        type="image/x-icon"/>
  <title>NARONO</title>
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
  <!--    <link rel="stylesheet" href="plugins/jqvmap/jqvmap.min.css">-->
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
  <link rel="stylesheet" href="plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
  <!-- Toastr -->
  <link rel="stylesheet" href="plugins/toastr/toastr.min.css">
  <!-- Google Font: Source Sans Pro -->
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">

  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">

  <link rel="stylesheet" href="css/sweetalert.css">

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

    .pagination li {
      padding: 10px;
    }

    .pagination li.active {
      background-color: #2e6da4;
    }

    .pagination li.active a {
      color: white;
    }

    .select2,
    .select2-search__field,
    .select2-results__option {
      /*font-size:1.3em!important;*/
    }

    .select2-selection__rendered {
      line-height: 2em !important;
    }

    .select2-container .select2-selection--single {
      height: 2.4em !important;
    }

    .select2-selection__arrow {
      height: 2.4em !important;
    }

    .help-block {
      color: red;
    }
    .product-items{
      cursor: pointer;
    }
    .product-items:hover{
      -webkit-transform: scale(1.1);
      transform: scale(1.1);
    }
  </style>

</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  <input type="hidden" id="current-url" value="<?= $cururl ?>">
  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->

    <!-- SEARCH FORM -->
    <!--    <form class="form-inline ml-3">-->
    <!--      <div class="input-group input-group-sm">-->
    <!--        <input class="form-control form-control-navbar" type="search" placeholder="Search"-->
    <!--               aria-label="Search">-->
    <!--        <div class="input-group-append">-->
    <!--          <button class="btn btn-navbar" type="submit">-->
    <!--            <i class="fas fa-search"></i>-->
    <!--          </button>-->
    <!--        </div>-->
    <!--      </div>-->
    <!--    </form>-->

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          Administrator
          <!--                    <span class="badge badge-danger navbar-badge">3</span>-->
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <a href="index.php?r=site/changepassword" class="dropdown-item">
            <!-- Message Start -->
            <div class="media">
              <img src="uploads/images/change_password.png" alt="User Avatar"
                   class="img-size-50 mr-3 img-circle">
              <div class="media-body">
                <h3 class="dropdown-item-title">
                  เปลี่ยนรหัสผ่าน
                </h3>
                <p class="text-sm">Change password</p>
              </div>
            </div>
            <!-- Message End -->
          </a>
          <div class="dropdown-divider"></div>
          <a href="index.php?r=site/logout" class="dropdown-item">
            <!-- Message Start -->
            <div class="media">
              <img src="uploads/images/logout.png" alt="User Avatar"
                   class="img-size-50 mr-3 img-circle">
              <div class="media-body">
                <h3 class="dropdown-item-title">
                  ออกจากระบบ
                </h3>
                <p class="text-sm">Logout</p>
              </div>
            </div>
            <!-- Message End -->
          </a>
        </div>
      </li>
      <!-- Messages Dropdown Menu -->
      <!--            <li class="nav-item dropdown">-->
      <!--                <a class="nav-link" data-toggle="dropdown" href="#">-->
      <!--                    <i class="far fa-comments"></i>-->
      <!--                    <span class="badge badge-danger navbar-badge">3</span>-->
      <!--                </a>-->
      <!--                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">-->
      <!--                    <a href="#" class="dropdown-item">-->
      <!--                        <!-- Message Start -->
      <!--                        <div class="media">-->
      <!--                            <img src="dist/img/user1-128x128.jpg" alt="User Avatar" class="img-size-50 mr-3 img-circle">-->
      <!--                            <div class="media-body">-->
      <!--                                <h3 class="dropdown-item-title">-->
      <!--                                    Brad Diesel-->
      <!--                                    <span class="float-right text-sm text-danger"><i class="fas fa-star"></i></span>-->
      <!--                                </h3>-->
      <!--                                <p class="text-sm">Call me whenever you can...</p>-->
      <!--                                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>-->
      <!--                            </div>-->
      <!--                        </div>-->
      <!--                        <!-- Message End -->
      <!--                    </a>-->
      <!--                    <div class="dropdown-divider"></div>-->
      <!--                    <a href="#" class="dropdown-item">-->
      <!--                        <!-- Message Start -->
      <!--                        <div class="media">-->
      <!--                            <img src="dist/img/user8-128x128.jpg" alt="User Avatar" class="img-size-50 img-circle mr-3">-->
      <!--                            <div class="media-body">-->
      <!--                                <h3 class="dropdown-item-title">-->
      <!--                                    John Pierce-->
      <!--                                    <span class="float-right text-sm text-muted"><i class="fas fa-star"></i></span>-->
      <!--                                </h3>-->
      <!--                                <p class="text-sm">I got your message bro</p>-->
      <!--                                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>-->
      <!--                            </div>-->
      <!--                        </div>-->
      <!--                        <!-- Message End -->
      <!--                    </a>-->
      <!--                    <div class="dropdown-divider"></div>-->
      <!--                    <a href="#" class="dropdown-item">-->
      <!--                        <!-- Message Start -->
      <!--                        <div class="media">-->
      <!--                            <img src="dist/img/user3-128x128.jpg" alt="User Avatar" class="img-size-50 img-circle mr-3">-->
      <!--                            <div class="media-body">-->
      <!--                                <h3 class="dropdown-item-title">-->
      <!--                                    Nora Silvester-->
      <!--                                    <span class="float-right text-sm text-warning"><i class="fas fa-star"></i></span>-->
      <!--                                </h3>-->
      <!--                                <p class="text-sm">The subject goes here</p>-->
      <!--                                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>-->
      <!--                            </div>-->
      <!--                        </div>-->
      <!--                        <!-- Message End -->
      <!--                    </a>-->
      <!--                    <div class="dropdown-divider"></div>-->
      <!--                    <a href="#" class="dropdown-item dropdown-footer">See All Messages</a>-->
      <!--                </div>-->
      <!--            </li>-->
      <!-- Notifications Dropdown Menu -->
      <!--            <li class="nav-item dropdown">-->
      <!--                <a class="nav-link" data-toggle="dropdown" href="#">-->
      <!--                    <i class="far fa-bell"></i>-->
      <!--                    <span class="badge badge-warning navbar-badge">15</span>-->
      <!--                </a>-->
      <!--                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">-->
      <!--                    <span class="dropdown-item dropdown-header">15 Notifications</span>-->
      <!--                    <div class="dropdown-divider"></div>-->
      <!--                    <a href="#" class="dropdown-item">-->
      <!--                        <i class="fas fa-envelope mr-2"></i> 4 new messages-->
      <!--                        <span class="float-right text-muted text-sm">3 mins</span>-->
      <!--                    </a>-->
      <!--                    <div class="dropdown-divider"></div>-->
      <!--                    <a href="#" class="dropdown-item">-->
      <!--                        <i class="fas fa-users mr-2"></i> 8 friend requests-->
      <!--                        <span class="float-right text-muted text-sm">12 hours</span>-->
      <!--                    </a>-->
      <!--                    <div class="dropdown-divider"></div>-->
      <!--                    <a href="#" class="dropdown-item">-->
      <!--                        <i class="fas fa-file mr-2"></i> 3 new reports-->
      <!--                        <span class="float-right text-muted text-sm">2 days</span>-->
      <!--                    </a>-->
      <!--                    <div class="dropdown-divider"></div>-->
      <!--                    <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>-->
      <!--                </div>-->
      <!--            </li>-->
      <!--            <li class="nav-item">-->
      <!--                <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#">-->
      <!--                    <i class="fas fa-th-large"></i>-->
      <!--                </a>-->
      <!--            </li>-->
    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="index.php?r=site/index" class="brand-link">
      <img src="<?php echo Yii::$app->request->baseUrl; ?>/uploads/logo/Logo_head.jpg" alt="Mind account"
           class="brand-image img-circle elevation-3"
           style="opacity: .8">
      <span class="brand-text font-weight-light">NARONO</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <!--          <div class="user-panel mt-3 pb-3 mb-3 d-flex">-->
      <!--            <div class="image">-->
      <!--              <img src="dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">-->
      <!--            </div>-->
      <!--            <div class="info">-->
      <!--              <a href="#" class="d-block">Alexander Pierce</a>-->
      <!--            </div>-->
      <!--          </div>-->
      <br>
      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
            data-accordion="false">
          <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
          <li class="nav-item">
            <a href="index.php?r=site/index" class="nav-link site">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>
                ภาพรวมระบบ
                <!--                                <i class="right fas fa-angle-left"></i>-->
              </p>
            </a>
          </li>
          <li class="nav-item has-treeview has-sub">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-building"></i>
              <p>
                ข้อมูลบริษัท
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="index.php?r=company/index" class="nav-link company">
                  <i class="far fa-circlez nav-icon"></i>
                  <p>บริษัท</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php?r=branch" class="nav-link branch">
                  <i class="far fa-circlez nav-icon"></i>
                  <p>
                    สาขา
                    <!--                                <span class="right badge badge-danger">New</span>-->
                  </p>
                </a>
              </li>
            </ul>
          </li>

          <li class="nav-item has-treeview has-sub">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-cog"></i>
              <p>
                ตั้งค่าทั่วไป
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="index.php?r=mainconfig" class="nav-link mainconfig">
                  <i class="far fa-file-import nav-icon"></i>
                  <p>Import Master</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php?r=sequence" class="nav-link sequence">
                  <i class="far fa-file-import nav-icon"></i>
                  <p>เลขที่เอกสาร</p>
                </a>
              </li>
              <!--                            <li class="nav-item">-->
              <!--                                <a href="index.php?r=product" class="nav-link product">-->
              <!--                                    <i class="far fa-circlez nav-icon"></i>-->
              <!--                                    <p>สินค้า</p>-->
              <!--                                </a>-->
              <!--                            </li>-->
              <!--                            <li class="nav-item">-->
              <!--                                <a href="index.php?r=costgroup" class="nav-link costgroup">-->
              <!--                                    <i class="far fa-circlez nav-icon"></i>-->
              <!--                                    <p>กลุ่มต้นทุน</p>-->
              <!--                                </a>-->
              <!--                            </li>-->
              <!--                            <li class="nav-item">-->
              <!--                                <a href="index.php?r=costitem" class="nav-link costitem">-->
              <!--                                    <i class="far fa-circlez nav-icon"></i>-->
              <!--                                    <p>รหัสต้นทุน</p>-->
              <!--                                </a>-->
              <!--                            </li>-->
              <!--                            <li class="nav-item">-->
              <!--                                <a href="index.php?r=materialgroup" class="nav-link materialgroup">-->
              <!--                                    <i class="far fa-circlez nav-icon"></i>-->
              <!--                                    <p>กลุ่มวัสดุ</p>-->
              <!--                                </a>-->
              <!--                            </li>-->
              <!--                            <li class="nav-item">-->
              <!--                                <a href="index.php?r=material" class="nav-link material">-->
              <!--                                    <i class="far fa-circlez nav-icon"></i>-->
              <!--                                    <p>วัสดุ/อุปกรณ์</p>-->
              <!--                                </a>-->
              <!--                            </li>-->
              <!--                            <li class="nav-item">-->
              <!--                                <a href="index.php?r=jobperiod" class="nav-link jobperiod">-->
              <!--                                    <i class="far fa-circlez nav-icon"></i>-->
              <!--                                    <p>รหัสงวดงาน</p>-->
              <!--                                </a>-->
              <!--                            </li>-->
              <!--                            <li class="nav-item">-->
              <!--                                <a href="index.php?r=unit" class="nav-link unit">-->
              <!--                                    <i class="far fa-circlez nav-icon"></i>-->
              <!--                                    <p>หน่วยนับ</p>-->
              <!--                                </a>-->
              <!--                            </li>-->
            </ul>
          </li>
          <li class="nav-item has-treeview has-sub">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-warehouse"></i>
              <p>
                ที่จัดเก็บสินค้า
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="index.php?r=warehouse/index" class="nav-link warehouse">
                  <i class="far fa-circlez nav-icon"></i>
                  <p>คลังสินค้า</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php?r=location" class="nav-link location">
                  <i class="far fa-circlez nav-icon"></i>
                  <p>
                    Location
                    <!--                                <span class="right badge badge-danger">New</span>-->
                  </p>
                </a>
              </li>


            </ul>
          </li>
          <li class="nav-item has-treeview has-sub">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-cubes"></i>
              <p>
                ข้อมูลสินค้า
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="index.php?r=producttype/index" class="nav-link producttype">
                  <i class="far fa-circlez nav-icon"></i>
                  <p>ประเภทสินค้า</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php?r=productgroup" class="nav-link productgroup">
                  <i class="far fa-circlez nav-icon"></i>
                  <p>
                    กลุ่มสินค้า
                    <!--                                <span class="right badge badge-danger">New</span>-->
                  </p>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php?r=unit" class="nav-link unit">
                  <i class="far fa-circlez nav-icon"></i>
                  <p>
                    หน่วยนับ
                    <!--                                <span class="right badge badge-danger">New</span>-->
                  </p>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php?r=product" class="nav-link product">
                  <i class="far fa-circlez nav-icon"></i>
                  <p>
                    สินค้า
                    <!--                                <span class="right badge badge-danger">New</span>-->
                  </p>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php?r=pricegroup" class="nav-link pricegroup">
                  <i class="far fa-circlez nav-icon"></i>
                  <p>
                    ราคามาตรฐาน
                    <!--                                <span class="right badge badge-danger">New</span>-->
                  </p>
                </a>
              </li>

            </ul>
          </li>
          <li class="nav-item has-treeview has-sub">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-users"></i>
              <p>
                ลูกค้า
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="index.php?r=customergroup/index" class="nav-link customergroup">
                  <i class="far fa-circlez nav-icon"></i>
                  <p>กลุ่มลูกค้า</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php?r=customertype/index" class="nav-link customertype">
                  <i class="far fa-circlez nav-icon"></i>
                  <p>ประเภทลูกค้า</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php?r=salegroup/index" class="nav-link salegroup">
                  <i class="far fa-circlez nav-icon"></i>
                  <p>กลุ่มการขาย</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php?r=customer" class="nav-link customer">
                  <i class="far fa-circlez nav-icon"></i>
                  <p>
                    ลูกค้า
                    <!--                                <span class="right badge badge-danger">New</span>-->
                  </p>
                </a>
              </li>


            </ul>
          </li>
          <!--                    <li class="nav-item has-treeview has-sub">-->
          <!--                        <a href="#" class="nav-link">-->
          <!--                            <i class="nav-icon fas fa-edit"></i>-->
          <!--                            <p>-->
          <!--                                ข้อมูลโครงการ-->
          <!--                                <i class="fas fa-angle-left right"></i>-->
          <!--                            </p>-->
          <!--                        </a>-->
          <!--                        <ul class="nav nav-treeview">-->
          <!--                            <li class="nav-item">-->
          <!--                                <a href="index.php?r=quotation/index" class="nav-link quotation">-->
          <!--                                    <i class="far fa-circlez nav-icon"></i>-->
          <!--                                    <p>เสนอราคา</p>-->
          <!--                                </a>-->
          <!--                            </li>-->
          <!--                            <li class="nav-item">-->
          <!--                                <a href="index.php?r=projecttype/index" class="nav-link projecttype">-->
          <!--                                    <i class="far fa-circlez nav-icon"></i>-->
          <!--                                    <p>ประเภทโครงการ</p>-->
          <!--                                </a>-->
          <!--                            </li>-->
          <!--                            <li class="nav-item">-->
          <!--                                <a href="index.php?r=project/index" class="nav-link project">-->
          <!--                                    <i class="far fa-circlez nav-icon"></i>-->
          <!--                                    <p>โครงการ</p>-->
          <!--                                </a>-->
          <!--                            </li>-->
          <!---->
          <!---->
          <!--                        </ul>-->
          <!--                    </li>-->
          <li class="nav-item has-treeview has-sub">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-shopping-cart"></i>
              <p>
                จัดการพนักงาน
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">

              <li class="nav-item">
                <a href="index.php?r=pos/index" class="nav-link pos">
                  <i class="far fa-circlez nav-icon"></i>
                  <p>พนักงานขับรถ</p>
                </a>
              </li>
            </ul>
          </li>

          <li class="nav-item has-treeview has-sub">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-user"></i>
              <p>
                ผู้ใช้งาน
                <i class="fas fa-angle-left right"></i>
                <!--                                <span class="badge badge-info right">6</span>-->
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="index.php?r=usergroup" class="nav-link usergroup">
                  <i class="far fa-circlez nav-icon"></i>
                  <p>กลุ่มผู้ใช้งาน</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php?r=user" class="nav-link user">
                  <i class="far fa-circlez nav-icon"></i>
                  <p>ผู้ใช้งาน</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php?r=authitem" class="nav-link auth">
                  <i class="far fa-circlez nav-icon"></i>
                  <p>สิทธิ์การใช้งาน</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item has-treeview has-sub">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-database"></i>
              <p>
                สำรองข้อมูล
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="index.php?r=dbbackup/backuplist" class="nav-link dbbackup">
                  <i class="far fa-file-archive nav-icon"></i>
                  <p>สำรองข้อมูล</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php?r=dbrestore/restorepage" class="nav-link dbrestore">
                  <i class="fa fa-upload nav-icon"></i>
                  <p>กู้คืนข้อมูล</p>
                </a>
              </li>
            </ul>
          </li>
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header" style="background-color: #f3f3f3">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-dark"><?= $this->title ?></h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <!--                        <ol class="breadcrumb float-sm-right">-->
            <!--                            <li class="breadcrumb-item"><a href="#">Home</a></li>-->
            <!--                            <li class="breadcrumb-item active">Dashboard v1</li>-->
            <!--                        </ol>-->
            <?php
            echo Breadcrumbs::widget([
              'itemTemplate' => "<li class='breadcrumb-item'>{link}</li>",
              'options' => ['class' => 'breadcrumb float-sm-right'],
              'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],

            ]);
            ?>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <?php $this->beginBody() ?>
    <section class="content" style="background-color: #ffffff;">
      <div id="btn-show-alert"></div>
      <?php $session = Yii::$app->session;
      if ($session->getFlash('msg')): ?>
        <input type="hidden" class="alert-msg" value="<?= $session->getFlash('msg'); ?>">
      <?php endif; ?>
      <?php if ($session->getFlash('msg-error')): ?>
        <input type="hidden" class="alert-msg-error" value="<?= $session->getFlash('msg-error'); ?>">
      <?php endif; ?>
      <div class="container-fluid">
        <form action="" id="form-delete" method="post"></form>
        <br>
        <?php echo $content ?>
        <br>
      </div>
    </section>
    <?php $this->endBody(); ?>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
  <footer class="main-footer">
    <strong>Copyright &copy; 2020 <a href="#">vorapat ice</a>.</strong>
    All rights reserved.
    <div class="float-right d-none d-sm-inline-block">
      <b>Version</b> 1.0.0
    </div>
  </footer>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->


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
<!--<script src="plugins/jqvmap/jquery.vmap.min.js"></script>-->
<!--<script src="plugins/jqvmap/maps/jquery.vmap.usa.js"></script>-->
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<!-- SweetAlert2 -->
<script src="plugins/sweetalert2/sweetalert2.min.js"></script>
<!-- Toastr -->
<script src="plugins/toastr/toastr.min.js"></script>

<script src="js/sweetalert.min.js"></script>



<script>
  var cururl = $("#current-url").val();
  $(function () {
    //---- active menu
    $("#perpage").change(function () {
      $("#form-perpage").submit();
    });

    if(cururl == 'pos'){
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
      // alert(msg);
      if (msg != '' && typeof (msg) !== "undefined") {
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

<!-- OPTIONAL SCRIPTS -->
<script src="plugins/chart.js/Chart.min.js"></script>
<script src="dist/js/demo.js"></script>
<script src="dist/js/pages/dashboard3.js"></script>
<script src="js/module_index_delete.js"></script>

</body>
</html>
<?php $this->endPage() ?>
