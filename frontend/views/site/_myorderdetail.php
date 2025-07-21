<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'รายละเอียดคำสั่งซื้อ';
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="row">
    <div class="col-lg-3">
        <div><b>บัญชีของฉัน</b></div>
        <br/>
        <table class="table">
            <tr>
                <td style="border: 1px solid lightgrey"><a href="index.php?r=site/profile"
                                                           style="text-decoration: none;color: grey;">ข้อมูลส่วนตัว</a>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid lightgrey"><a href="index.php?r=site/addressinfo"
                                                           style="text-decoration: none;color: grey;">ที่อยู่สำหรับจัดส่งสินค้า</a>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid lightgrey"><a href="index.php?r=site/myorder"
                                                           style="text-decoration: none;color: grey;">การสั่งซื้อของฉัน</a>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid lightgrey"><a href="index.php?r=site/logout"
                                                           style="text-decoration: none;color: red;">ออกจากระบบ</a></td>
            </tr>
        </table>
    </div>
    <div class="col-lg-9">
        <div class="row">
            <div class="col-lg-12">
                <h5>รายการละเอียดการสั่งซื้อ <span><b><?=$model->order_no;?></b></span></h5>
            </div>
        </div>
        <br/>
        <div class="row">
            <div class="col-lg-12">
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th style="width:5%;text-align: center;background-color: #36ab63;color: white;">#</th>
                        <th style="text-align: center;max-width: 50px;background-color: #36ab63;color: white;"></th>
                        <th style="text-align: center;background-color: #36ab63;color: white;">สินค้า</th>
                        <th style="text-align: right;background-color: #36ab63;color: white;">จำนวน</th>
                        <th style="text-align: right;background-color: #36ab63;color: white;">ราคา</th>
                        <th style="text-align: right;background-color: #36ab63;color: white;">รวม</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $qty_total = 0;
                    $line_total = 0;
                    ?>
                    <?php if ($model_line == null): ?>
                        <tr>
                            <td colspan="6" style="padding: 15px;text-align: center;color: lightgrey;">ไม่พบรายการสินค้า
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $loop_count = 0; ?>
                        <?php foreach ($model_line as $model_detail_item): ?>
                            <?php
                            $photo = \backend\models\Product::findPhoto($model_detail_item->product_id);
                            $qty_total = $qty_total + $model_detail_item->qty;
                            $line_total = $line_total + $model_detail_item->line_total;
                            ?>
                            <tr>
                                <td style="text-align: center;"><?= ++$loop_count ?></td>
                                <td style="text-align: center;"><img
                                        src="<?= \Yii::$app->urlManagerBackend->getBaseUrl() . '/uploads/product_photo/' . $photo ?>"
                                        style="margin-top: 5px;max-width: 50px" alt=""></td>
                                <td style="text-align: left;"><?= \backend\models\Product::findName($model_detail_item->product_id) ?></td>
                                <td style="text-align: right;"><?= number_format($model_detail_item->qty, 2) ?></td>
                                <td style="text-align: right;"><?= number_format($model_detail_item->price, 2) ?></td>
                                <td style="text-align: right;"><?= number_format($model_detail_item->line_total, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right;"><b>รวม</b></td>
                        <td style="text-align: right;"><b><?=number_format($qty_total,2)?></b></td>
                        <td style="text-align: right;"></td>
                        <td style="text-align: right;"><b><?=number_format($line_total,2)?></b></td>
                    </tr>
                    </tfoot>
                </table>
            </div>

        </div>
        <br />
        <div class="row">
            <div class="col-lg-8">
                <table class="table table-striped table-bordered">
                    <tr>
                        <td style="width: 25%">สถานะชำระเงิน</td>
                        <td>
                            <?php if($model->pay_status == 1):?>
                                <div class="badge badge-success">ชำระแล้ว</div>
                            <?php else:?>
                                <div class="badge badge-secondary" style="background-color: grey;color: white">ยังไม่ชำระ</div>
                            <?php endif;?>
                        </td>
                    </tr>
                    <tr>
                        <td>ที่อยู่ในการจัดส่ง</td>
                        <td>
                            <p><?=\backend\models\Customer::findFullAddress(1)?></p>
                        </td>
                    </tr>
                    <tr>
                        <td>สถานะคำสั่งซื้อ</td>
                        <?php
                        $color_status = '';
                        if ($model->status == 3) {
                            $color_status = 'color: orange';
                        } elseif ($model->status == 4) {
                            $color_status = 'color: green';
                        } elseif ($model->status == 5) {
                            $color_status = 'color: red';
                        }
                        ?>
                        <td style="<?=$color_status?>"><?=\backend\helpers\OrderStatus::getTypeById($model->status)?></td>
                    </tr>
                    <tr>
                        <td>เลขที่ติดตามพัสดุ</td>
                        <td style="color: green;">
                            <?=$model->order_tracking_no?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <hr style="border: 1px dashed grey"/>
        <?php if($model != null):?>
        <div class="row">
            <div class="col-lg-12"><h5>แจ้งการชำระเงิน</h5></div>
        </div>
        <br />
        <div class="row">
            <div class="col-lg-12">
                <input type="file" name="file_slip" class="form-control">
            </div>
        </div>
        <br />
        <div class="row">
            <div class="col-lg-3">
                <button class="btn btn-primary">ตกลง</button>
            </div>
        </div>
       <?php endif;?>
    </div>
</div>







