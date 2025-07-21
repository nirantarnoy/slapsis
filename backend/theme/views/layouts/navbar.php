<?php

use yii\helpers\Html;
use yii\web\Session;

?>
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
        </li>
        <li class="nav-item d-none d-sm-inline-block">
        </li>
        <li class="nav-item dropdown">
        </li>
    </ul>

    <!-- SEARCH FORM -->
    <form class="form-inline ml-3">
    </form>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <span><i class="fa fa-user-circle"></i>  <?= \backend\models\User::findName(\Yii::$app->user->id)?></span>
                <?php //echo $_SESSION['user_group_id']?>
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
    </ul>
</nav>
