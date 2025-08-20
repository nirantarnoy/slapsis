<aside class="main-sidebar sidebar-light-green elevation-4">
    <!-- Brand Logo -->
    <a href="index.php?r=site/index" class="brand-link" style="text-align: center;">
<!--        <img src="-->
        <?php //echo Yii::$app->request->baseUrl; ?><!--/uploads/logo/ab_logo.jpg" alt="mmc" class="brand-image">-->
        <img src="<?php echo Yii::$app->request->baseUrl; ?>/uploads/logo/slapsis_logo.jpg" alt="mhee" width="45%">
<!--                <span style="margin-left: 20px; " class="brand-text font-weight-light">SLAPSIS</span>-->
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
<!--        --><?php //if (!isset($_SESSION['driver_login'])): ?>
        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                data-accordion="false">
                <!-- Add icons to the links using the .nav-icon class
                     with font-awesome or any other icon font library -->
                <?php //if(\Yii::$app->user->can('site/index')):?>
                <li class="nav-item">
                    <a href="<?=\Yii\helpers\Url::to(['site/index',true])?>" class="nav-link site">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>
                            ภาพรวมระบบ
                            <!--                                <i class="right fas fa-angle-left"></i>-->
                        </p>
                    </a>
                </li>
                <?php //endif;?>
                <li class="nav-item has-treeview has-sub">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-cog"></i>
                        <p>
                            ตั้งค่า
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <?php //if (\Yii::$app->user->can('department/index')): ?>
                        <li class="nav-item">
                            <a href="<?=\Yii\helpers\Url::to(['site/index',true])?>" class="nav-link site">
                                <i class="far fa-circlez nav-icon"></i>
                                <p>เชื่อมต่อร้านค้า</p>
                            </a>
                        </li>
                        <?php //endif; ?>
                    </ul>
                </li>
                <li class="nav-item has-treeview has-sub">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-cubes"></i>
                        <p>
                            สินค้า
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <?php //if (\Yii::$app->user->can('department/index')): ?>
                        <li class="nav-item">
                            <a href="<?=\Yii\helpers\Url::to(['product/index',true])?>" class="nav-link product">
                                <i class="far fa-circlez nav-icon"></i>
                                <p>ข้อมูลสินค้า</p>
                            </a>
                        </li>
                        <?php //endif; ?>
                    </ul>
                </li>

                <li class="nav-item has-treeview has-sub">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-shopping-cart"></i>
                        <p>
                            คำสั่งซื้อ
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <?php //if (\Yii::$app->user->can('department/index')): ?>
                        <li class="nav-item">
                            <a href="<?=\Yii\helpers\Url::to(['order/index',true])?>" class="nav-link order">
                                <i class="far fa-circlez nav-icon"></i>
                                <p>คำสั่งซื้อล่าสุด</p>
                            </a>
                        </li>
                        <?php //endif; ?>
                    </ul>
                </li>

                <?php if(\Yii::$app->user->can('department/index') || \Yii::$app->user->can('position/index') || \Yii::$app->user->can('employee/index')):?>
                <li class="nav-item has-treeview has-sub">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-users-cog"></i>
                        <p>
                            พนักงาน
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <?php if (\Yii::$app->user->can('department/index')): ?>
                        <li class="nav-item">
                            <a href="index.php?r=department/index" class="nav-link department">
                                <i class="far fa-circlez nav-icon"></i>
                                <p>แผนก</p>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\Yii::$app->user->can('position/index')): ?>
                        <li class="nav-item">
                            <a href="index.php?r=position/index" class="nav-link position">
                                <i class="far fa-circlez nav-icon"></i>
                                <p>ตำแหน่ง</p>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\Yii::$app->user->can('employee/index')): ?>
                            <li class="nav-item">
                                <a href="index.php?r=employee/index" class="nav-link employee">
                                    <i class="far fa-circlez nav-icon"></i>
                                    <p>พนักงาน</p>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif;?>

                <?php if(\Yii::$app->user->can('productgroup/index')||\Yii::$app->user->can('product/index')||\Yii::$app->user->can('warehouse/index')||\Yii::$app->user->can('product/index')||\Yii::$app->user->can('stocksum/index')||\Yii::$app->user->can('stocktrans/index')):?>

                <li class="nav-item has-treeview has-sub">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-cubes"></i>
                        <p>
                            จัดการสต๊อกสินค้า
                            <i class="fas fa-angle-left right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <?php if (\Yii::$app->user->can('productgroup/index')): ?>
                        <li class="nav-item">
                            <a href="index.php?r=productgroup/index" class="nav-link productgroup">
                                <i class="far fa-circlez nav-icon"></i>
                                <p>กลุ่มสินค้า</p>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (\Yii::$app->user->can('product/index')): ?>
                            <li class="nav-item">
                                <a href="index.php?r=unit" class="nav-link unit">
                                    <i class="far fa-circlez nav-icon"></i>
                                    <p>หน่วยนับ</p>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (\Yii::$app->user->can('productbrand/index')): ?>
                            <li class="nav-item">
                                <a href="index.php?r=productbrand" class="nav-link productbrand">
                                    <i class="far fa-circlez nav-icon"></i>
                                    <p>ยี่ห้อสินค้า</p>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (\Yii::$app->user->can('product/index')): ?>
                            <li class="nav-item">
                                <a href="index.php?r=product" class="nav-link product">
                                    <i class="far fa-circlez nav-icon"></i>
                                    <p>สินค้า</p>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (\Yii::$app->user->can('warehouse/index')): ?>
                        <li class="nav-item">
                            <a href="index.php?r=warehouse" class="nav-link warehouse">
                                <i class="far fa-circlez nav-icon"></i>
                                <p>คลังสินค้า</p>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\Yii::$app->user->can('watchmaker/index')): ?>
                            <li class="nav-item">
                                <a href="index.php?r=watchmaker" class="nav-link watchmaker">
                                    <i class="far fa-circlez nav-icon"></i>
                                    <p>ช่างนาฬิกา</p>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php if (\Yii::$app->user->can('stocksum/index')): ?>
                        <li class="nav-item">
                            <a href="index.php?r=stocksum" class="nav-link stocksum">
                                <i class="far fa-circlez nav-icon"></i>
                                <p>
                                    สินค้าคงเหลือ
                                    <!--                                <span class="right badge badge-danger">New</span>-->
                                </p>
                            </a>
                        </li>
                        <?php endif;?>
                    </ul>
                </li>
                <?php endif;?>
                <?php if(\Yii::$app->user->can('journaltrans/index')):?>
                    <li class="nav-item has-treeview has-sub">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-tags"></i>
                            <p>
                                ทำรายการ
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php if (\Yii::$app->user->can('journaltrans/index')): ?>
                                <li class="nav-item">
                                    <a href="index.php?r=journaltrans/index" class="nav-link journaltrans">
                                        <i class="far fa-circlez nav-icon"></i>
                                        <p>บันทึกรายการต่างๆ</p>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if (\Yii::$app->user->can('stocktrans/index')): ?>
                                <li class="nav-item">
                                    <a href="index.php?r=stocktrans" class="nav-link stocktrans">
                                        <i class="far fa-circlez nav-icon"></i>
                                        <p>
                                            ประวัติทำรายการ
                                            <!--                                <span class="right badge badge-danger">New</span>-->
                                        </p>
                                    </a>
                                </li>
                            <?php endif;?>
                        </ul>
                    </li>
                <?php endif;?>
                <?php if(\Yii::$app->user->can('salereport/crosstab')):?>
                    <li class="nav-item has-treeview has-sub">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-chart-line"></i>
                            <p>
                                รายงาน
                                <i class="fas fa-angle-left right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php if (\Yii::$app->user->can('salereport/crosstab')): ?>
                                <li class="nav-item">
                                    <a href="index.php?r=salereport/crosstab" class="nav-link salereport">
                                        <i class="far fa-circlez nav-icon"></i>
                                        <p>รายงานยอดขาย</p>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif;?>
                <?php // if (isset($_SESSION['user_group_id'])): ?>
                <?php //if ($_SESSION['user_group_id'] == 1): ?>
                <?php  if (\backend\models\User::findName(\Yii::$app->user->id) == 'slapsis'): ?>
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
                            <?php //if (\Yii::$app->user->can('usergroup/index')): ?>
                                <li class="nav-item">
                                    <a href="index.php?r=usergroup" class="nav-link usergroup">
                                        <i class="far fa-circlez nav-icon"></i>
                                        <p>กลุ่มผู้ใช้งาน</p>
                                    </a>
                                </li>
                            <?php //endif; ?>
                            <?php //if (\Yii::$app->user->can('user/index')): ?>
                                <li class="nav-item">
                                    <a href="index.php?r=user" class="nav-link user">
                                        <i class="far fa-circlez nav-icon"></i>
                                        <p>ผู้ใช้งาน</p>
                                    </a>
                                </li>
                            <?php //endif;?>

                            <?php //if (\Yii::$app->user->can('authitem/index')): ?>
                                <li class="nav-item">
                                    <a href="index.php?r=authitem" class="nav-link authitem">
                                        <i class="far fa-circlez nav-icon"></i>
                                        <p>สิทธิ์การใช้งาน</p>
                                    </a>
                                </li>
                            <?php //endif;?>

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
                <?php  endif;?>
                <?php //endif; ?>
                <?php //endif; ?>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->

<!--        --><?php //endif; ?>

    </div>
    <!-- /.sidebar -->
</aside>

