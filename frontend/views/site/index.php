<?php

/** @var yii\web\View $this */

use yii\bootstrap4\LinkPager;
use yii\helpers\Url;
use yii\web\JqueryAsset;

$this->title = 'MMC';

$cart_item_count = 0;
////if (isset($_POST['add_to_cart'])) {
if (isset($_SESSION['cart'])) {
//        $session_array_id = array_column($_SESSION['cart'], 'id');
//        if (!in_array($_GET['id'], $session_array_id)) {
//            $session_array = array(
//                "id" => $_GET['id'],
//                "name" => "soap",// $_POST['name'],
//                "price" => 100, //$_POST['price'],
//                "qty" => 2, //$_POST['qty']
//            );
//
//            $_SESSION['cart'][] = $session_array;
//        }
//    } else {
//        $session_array = array(
//            "id" => $_GET['id'],
//            "name" => "soap",// $_POST['name'],
//            "price" => 100, //$_POST['price'],
//            "qty" => 2, //$_POST['qty']
//        );
//
//        $_SESSION['cart'][] = $session_array;
    $cart_item_count = count($_SESSION['cart']);
    //  var_dump($_SESSION['cart']);
}
////}
//
//var_dump($_SESSION['cart']);
//unset($_SESSION['cart']);

?>
<br/>
<div class="container-cart-index">

</div>

<!--<div class="cartTab_">-->
<!--    <div style="height: 155px; "></div>-->
<!--    <h5 style="color: grey">สินค้าในตะกร้า</h5>-->
<!---->
<!--       -->
<!--    <div class="btn">-->
<!--        <button class="btn btn-outline-danger close">ปิด</button>-->
<!--        <button class="btn btn-outline-primary checkOut">ชำระเงิน</button>-->
<!--    </div>-->
<!--</div>-->

<?php
$uri = Url::base();
//$this->registerCssFile("{$uri}/js/bootstrap.css", ['depends' => JqueryAsset::class]);
$this->registerJsFile("{$uri}/js/cart.js", ['depends' => JqueryAsset::class]);
$url_to_add_cart2 = \yii\helpers\Url::to(['site/addcart2'], true);
$js = <<<JS
$(function(){
    $(".alert-over-qty").hide();
});

function addtocart(e){
        var id = e.attr("data-var");
        if(id){
            $.ajax({
            url:'$url_to_add_cart2',
            type:'post',
            dataType:'html',
            data:{
                'product_id':id
            },
            success:function(data){
                 alert(data);
            }
        });
        }
}

JS;
$this->registerJs($js, static::POS_END);
?>
