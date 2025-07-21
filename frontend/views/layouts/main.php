<?php

/** @var \yii\web\View $this */
/** @var string $content */

use common\widgets\Alert;
use frontend\assets\AppAsset;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php $this->registerCsrfMetaTags() ?>
    <title>MHEEWATCH</title>
    <?php $this->head() ?>

    <style>
        @font-face {
            font-family: 'Kanit-Regular';
            /*font-family: 'TH-Sarabun-New';*/
            /*src: url('fonts/THSarabunNew.ttf') format('truetype');*/
            src: url('fonts/Kanit-Regular.ttf') format('truetype');
            /*src: url('../../backend/web/fonts/Kanit-Regular.ttf') format('truetype');*/
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

        .breadcrumb {
            background-color: transparent;
        }

        /*@media print {*/
        /*    @page {*/
        /*        size: A4 landscape;*/
        /*    }*/
        /*    body {*/
        /*        font-size: 9px;*/
        /*        writing-mode: horizontal-tb;*/
        /*        !*transform: rotate(90deg);*!*/
        /*    }*/
        /*}*/


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

        .cart{
            background-color: #fff;
            padding: 4vh 5vh;
            border-bottom-left-radius: 1rem;
            border-top-left-radius: 1rem;
        }
        @media(max-width:767px){
            .cart{
                padding: 4vh;
                border-bottom-left-radius: unset;
                border-top-right-radius: 1rem;
            }
        }
        .summary{
            background-color: #ddd;
            border-top-right-radius: 1rem;
            border-bottom-right-radius: 1rem;
            padding: 4vh;
            color: rgb(65, 65, 65);
        }
        @media(max-width:767px){
            .summary{
                border-top-right-radius: unset;
                border-bottom-left-radius: 1rem;
            }
        }
        .summary .col-2{
            padding: 0;
        }
        .summary .col-10
        {
            padding: 0;
        }.row{
             margin: 0;
         }
        .title b{
            font-size: 1.5rem;
        }
        .back-to-shop{
            margin-top: 4.5rem;
        }
        .btn-checkout{
            background-color: #000;
            /*border-color: #000;*/
            color: white;
            width: 100%;
            font-size: 0.7rem;
            margin-top: 4vh;
            padding: 1vh;
            border-radius: 0;
        }
        .btn-checkout:focus{
            box-shadow: none;
            outline: none;
            box-shadow: none;
            color: white;
            -webkit-box-shadow: none;
            -webkit-user-select: none;
            transition: none;
        }
        .btn-checkout:hover{
            color: white;
        }


        /*section cart*/

        .container-cart-indexx{
            width: 900px;
            margin: auto;
            max-width: 90vw;
            text-align: center;
            padding-top: 10px;
            transition: transform .5s;
        }

        .cartTab{
            width: 400px;
            background-color: #ffffff;
            color: #504d4d;
            position: fixed;
            top: 0;
            right: -400px;
            bottom: 0;
            display: grid;
            grid-template-rows: 70px 1fr 70px;
            transition: .5s;
        }
        .cart-product{
            text-decoration="none"
        }
        .card-product:hover{
            transform: scale(1.1);
        }
        .card :hover{
            transform: scale(1);
        }
        body.showCart .cartTab{
            right: 0;
        }
    </style>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<header>
    <?php
    NavBar::begin([
        'brandLabel' => 'MHEEWATCH',
        'brandUrl' => Yii::$app->homeUrl,
        'options' => [
            'class' => 'navbar navbar-expand-md navbar-dark bg-success fixed-top',
        ],
    ]);
    $menuItems = [
        ['label' => 'หน้าแรก', 'url' => ['/site/index']],
        ['label' => 'เกี่ยวกับเรา', 'url' => ['/site/about']],
        ['label' => 'ติดต่อเรา', 'url' => ['/site/contact']],
    ];
    if (Yii::$app->user->isGuest) {
        $menuItems[] = ['label' => 'ลงทะเบียน', 'url' => ['/site/signup']];
    }

    echo Nav::widget([
        'options' => ['class' => 'navbar-nav me-auto mb-2 mb-md-0'],
        'items' => $menuItems,
    ]);
    if (Yii::$app->user->isGuest) {
        echo Html::tag('div',Html::a('เข้าระบบ',['/site/login'],['class' => ['btn login text-decoration-none text-white']]),['class' => ['d-flex']]);
      //  echo Html::tag('div',Html::a('ตะกร้าสินค้า',['/site/yourcart'],['class' => ['btn login text-decoration-none text-white']]),['class' => ['d-flex']]);
    } else {
//        echo Html::beginForm(['/site/logout'], 'post', ['class' => 'd-flex'])
//            . Html::submitButton(
//                'Logout (' . Yii::$app->user->identity->username . ')',
//                ['class' => 'btn btn-link logout text-decoration-none']
//            )
//            . Html::endForm();
        $user_menu_item[]=  [
            'label' => 'สวัสดีคุณ (' . Yii::$app->user->identity->username . ')',
            'options' => ['class' => 'd-flex'],
            'items' => [
                ['label' => 'ข้อมูลของคุณ', 'url' => ['/site/profile']],
                ['label' => 'ออกจากระบบ', 'url' => ['/site/logout']],
            ],
        ];
        echo Nav::widget([
            'options' => ['class' => 'navbar-nav'],
            'items' => $user_menu_item,
        ]);
    }
    NavBar::end();
    ?>
</header>

<main role="main" class="flex-shrink-0">
    <div class="container">
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],

        ]) ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</main>

<footer class="footer mt-auto py-3 text-muted">
    <div class="container">
        <p class="float-start">&copy; <?= Html::encode('MHEEWATCH') ?> <?= date('Y') ?></p>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();
