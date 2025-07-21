<?php
$item_count = 0;
$total_amount = 0;
if (isset($_SESSION['cart'])) {
    $item_count = count($_SESSION['cart']);
}

$model_account = \backend\models\Bankaccount::find()->where(['status' => 1])->one();
?>

    <br xmlns="http://www.w3.org/1999/html"/>
    <div class="card">
        <div class="row">
            <div class="col-md-8 cart">
                <div class="title">
                    <div class="row">
                        <div class="col"><h4><b>สินค้าในตะกร้าของคุณ</b></h4></div>
                        <div class="col align-self-center text-right text-muted"><?= $item_count ?> รายการ</div>
                    </div>
                </div>
                <?php if (isset($_SESSION['cart'])): ?>
                    <?php foreach ($_SESSION['cart'] as $key => $value): ?>
                        <?php
                        $total_amount += ((float)$value['qty'] * (float)$value['price']); ?>
                        <div class="row border-top border-bottom">
                            <div class="row main align-items-center">
                                <div class="col-2"><img class="img-fluid"
                                                        src="<?= \Yii::$app->urlManagerBackend->getBaseUrl() . '/uploads/product_photo/' . $value['photo'] ?>">
                                </div>
                                <div class="col">
                                    <div class="row text-muted"><?= $value['sku'] ?></div>
                                    <div class="row"><?= $value['product_name'] ?></div>
                                </div>
                                <div class="col" style="max-width: 180px">
                                    <div class="input-group">
                                        <input type="hidden" class="line-product-id"
                                               value="<?= $value['product_id'] ?>">
                                        <div class="btn btn-success" style="font-size: 20px;"
                                             onclick="decreaseitem($(this))">-
                                        </div>
                                        <input type="text" class="form-control cart-selected-qty"
                                               style="text-align: center;" name="cart_selected_qty"
                                               value="<?= $value['qty'] ?>" pattern="[0-9]"
                                               onkeypress="return /[0-9]/i.test(event.key)" onchange="updatecartqty($(this))">
                                        <div class="btn btn-success" style="font-size: 20px;"
                                             onclick="increaseitem($(this))">+
                                        </div>
                                    </div>
                                </div>
                                <div class="col-1"><span>&#3647</span><?= $value['price'] ?></div>
                                <div class="col-1">
                                    <div class="btn btn-danger" data-var="<?= $value['product_id'] ?>"
                                         onclick="removeitem($(this))">ลบ
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="row">
                        <div class="col-12">ยังไม่มีสินค้าในตะกร้าของคุณ</div>
                    </div>
                <?php endif; ?>
                <!--            <div class="row">-->
                <!--                <div class="row main align-items-center">-->
                <!--                    <div class="col-2"><img class="img-fluid" src="-->
                <?php //=\Yii::$app->getUrlManager()->baseUrl . '/uploads/product_photo/'.'xx.jpg' ?><!--"></div>-->
                <!--                    <div class="col">-->
                <!--                        <div class="row text-muted">ครีม</div>-->
                <!--                        <div class="row">นอทิ ชิค ออล เดย์ อายไลเนอร์ เพ็น</div>-->
                <!--                    </div>-->
                <!--                    <div class="col">-->
                <!--                        <a href="#">-</a><a href="#" class="border">1</a><a href="#">+</a>-->
                <!--                    </div>-->
                <!--                    <div class="col">&#3647 44.00 <span class="close">&#10005;</span></div>-->
                <!--                </div>-->
                <!--            </div>-->
                <!--            <div class="row border-top border-bottom">-->
                <!--                <div class="row main align-items-center">-->
                <!--                    <div class="col-2"><img class="img-fluid" src="-->
                <?php //=\Yii::$app->getUrlManager()->baseUrl . '/uploads/product_photo/'.'xx.jpg' ?><!--"></div>-->
                <!--                    <div class="col">-->
                <!--                        <div class="row text-muted">ครีม</div>-->
                <!--                        <div class="row">ยูสตาร์ สไมลี่ย์เวิลด์ ซุปเปอร์ อายไลเนอร์</div>-->
                <!--                    </div>-->
                <!--                    <div class="col">-->
                <!--                        <a href="#">-</a><a href="#" class="border">1</a><a href="#">+</a>-->
                <!--                    </div>-->
                <!--                    <div class="col">&#3647 44.00 <span class="close">&#10005;</span></div>-->
                <!--                </div>-->
                <!--            </div>-->
                <?php if (isset($_SESSION['cart'])): ?>
                    <?php if (count($_SESSION['cart']) > 0): ?>
                        <div class="back-to-shop"><a href="#">&leftarrow;</a><span
                                    class="text-muted"> ซื้อสินค้าเพิ่ม</span></div>
                    <?php endif; ?>
                <?php endif; ?>

                <hr style="border: 1px dashed grey;"/>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="row">
                        <div class="col-lg-12">
                            <h5>ที่อยู่ในการจัดส่ง</h5>
                        </div>
                    </div>
                    <div class="row">
                        <!--                    <div class="col-lg-1">-->
                        <!--                        <input type="checkbox" class="checkbox">-->
                        <!--                    </div>-->
                        <div class="col-lg-11">
                            <?php if ($address == ''): ?>
                                <a href="index.php?r=site%2Faddressinfo" class="btn btn-sm btn-outline-info">เพิ่มที่อยู่</a>
                            <?php else: ?>
                                <p><?= $address ?></p>
                            <?php endif; ?>

                        </div>
                    </div>
                    <hr style="border: 1px dashed grey;"/>
                    <div class="row">
                        <div class="col-lg-12">
                            <h5>ชำระเงินผ่านบัญชีธนาคาร</h5>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-11">
                            <table class="table table-bordered table-striped">
                                <thead>
                                <tr style="background-color: #08883f;color: white;">
                                    <th style="text-align: center;background-color: #267243;color: white;">ธนาคาร</th>
                                    <th style="text-align: center;background-color: #267243;color: white;">ชื่อบัญชี
                                    </th>
                                    <th style="text-align: center;background-color: #267243;color: white;">เลขบัญชี</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td style="text-align: center;"><?= \backend\models\Bank::findName($model_account->bank_id) ?></td>
                                    <td style="text-align: center;"><?= $model_account->account_name ?></td>
                                    <td style="text-align: center;"><?= $model_account->account_no ?></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-4 summary">
                <div><h5><b>รวมจำนวนเงิน</b></h5></div>
                <hr>
                <div class="row">
                    <div class="col" style="padding-left:0;"><?= $item_count ?> รายการ</div>
                    <div class="col text-right">&#3647 <?= number_format($total_amount, 2) ?></div>
                </div>

                <br />
                <div class="row" style="border-top: 1px solid rgba(0,0,0,.1); padding: 2vh 0;">
                    <div class="col">ราคารวมทั้งหมด</div>
                    <div class="col text-right">&#3647 <?= number_format($total_amount, 2) ?></div>
                </div>
                <?php if (isset($_SESSION['cart'])): ?>
                    <?php //if (count($_SESSION['cart'] ) > 0 && $address !='') : ?>
                        <a href="index.php?r=site/createorder" style="text-decoration: none;"><div style="font-size: 20px;height: 50px;width: 100%;background-color: black;color: white;text-align: center;padding: 10px">สั่งซื้อสินค้า</div></a>
                    <?php //endif; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
<?php
$url_to_update_cart = \yii\helpers\Url::to(['site/updatecart'], true);
$url_to_remove_cart = \yii\helpers\Url::to(['site/removecart'], true);
$js = <<<JS
$(function(){
    $('.btn-add-to-cart').click(function(){
        var id = $(".product-id").val();
        var name = $(".product-name").val();
        var price = $(".price").val();     
        var qty = $(".qty").val();
        var sku = $(".sku").val();
        var cart_id = 1;
        if(id){
            $.ajax({
            url:'$url_to_update_cart',
            type:'post',
            dataType:'html',
            data:{
                'product_id':id,
                'product_name':name,
                'price':price,
                'qty':qty,
                'sku':sku
            },
            success:function(data){
                 alert(data);
            }
        })
        }
        
    });
});
function increaseitem(e){
    var qty = e.parent().find(".cart-selected-qty").val();
    var product_id = e.parent().find(".line-product-id").val();
    qty = parseInt(qty) + 1;
    e.parent().find(".cart-selected-qty").val(qty);
    
    updateCart(product_id,qty);
}
function decreaseitem(e){
    var qty = e.parent().find(".cart-selected-qty").val();
    var product_id = e.parent().find(".line-product-id").val();
    if(qty <= 1){
        return false;
    }
    qty = parseInt(qty) - 1;
    e.parent().find(".cart-selected-qty").val(qty);
    updateCart(product_id,qty);
}

function updatecartqty(e){
    var qty = e.val();
    var product_id = e.parent().find(".line-product-id").val();
    updateCart(product_id,qty);
}

function updateCart(id,qty){
    $.ajax({
            url:'$url_to_update_cart',
            type:'post',
            dataType:'html',
            data:{
                'product_id':id,              
                'qty':qty
            },
            success:function(data){
               location.reload();
            }
      });
}
//function updateCart2(id,qty){
//    $.ajax({
//            url:'',
//            type:'post',
//            dataType:'html',
//            data:{
//                'product_id':id,              
//                'qty':qty
//            },
//            success:function(data){
//               location.reload();
//            }
//      });
//}
function removeitem(e){
    var product_id = e.attr('data-var');
    if(confirm("ต้องการลบสินค้านี้ใช่หรือไม่")){
        $.ajax({
           url:'$url_to_remove_cart',
            type:'post',
            dataType:'html',
            data:{
                'product_id':product_id         
            },
            success:function(data){
               location.reload();
            }
    });
    }
    
}
JS;
$this->registerJs($js, static::POS_END);
?>