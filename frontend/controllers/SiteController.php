<?php

namespace frontend\controllers;

use backend\models\Customer;
use backend\models\ItemSearch;
use frontend\models\ProductSearch;
use frontend\models\ResendVerificationEmailForm;
use frontend\models\VerifyEmailForm;
use Yii;
use yii\base\InvalidArgumentException;
use yii\data\Pagination;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\ContactForm;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post','GET'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
            'captcha' => [
                'class' => \yii\captcha\CaptchaAction::class,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $customer_id=0;

        if (isset($_SESSION['user_customer_id'])) {
            $customer_id = $_SESSION['user_customer_id'];
        }


        return $this->render('index');
    }

    public function actionYourcart()
    {
        $address = 'xx';
        $customer_id = 0;
        if (isset($_SESSION['user_id'])) {
            $customer_id = \backend\models\User::findCustomerId($_SESSION['user_id']);
            $address = \backend\models\Customer::findFullAddress($customer_id);
        }
        return $this->render('_cart',[
            'address'=> $address,
            'customer_id' => $customer_id,
        ]);
    }

    public function actionProfile()
    {
        $id=0;
        if (!isset($_SESSION['user_id'])) {
            return $this->redirect(['site/login']);
        }
        if (isset($_SESSION['user_customer_id'])) {
            $id = $_SESSION['user_customer_id'];
        }
        $model = null;
        if ($id) {
            $model = \backend\models\Customer::find()->where(['id' => $id])->one();
        } else {
            $model = new \backend\models\Customer();
        }
        if ($model->load(Yii::$app->request->post())) {
            if ($model->save(false)) {
                return $this->redirect(['profile', 'id' => $model->id]);
            }
        }
        return $this->render('_account', [
            'model' => $model,
        ]);
    }

    public function actionAddressinfo()
    {
        $id=0;
        if (!isset($_SESSION['user_id'])) {
            return $this->redirect(['site/login']);
        }
        if (isset($_SESSION['user_customer_id'])) {
            $id = $_SESSION['user_customer_id'];
        }
        $model = null;
        $party_id = 0;
        if ($id) {
            $party_id = $id;
            $model = \backend\models\AddressInfo::find()->where(['party_id' => $id, 'party_type_id' => 2])->one();
            if (!$model) {
                $model = new \backend\models\AddressInfo();
            }
        } else {
            $model = new \backend\models\AddressInfo();
        }
        if ($model->load(\Yii::$app->request->post())) {
            $model->party_type_id = 2;
            $model->address_type_id = 1;
            $model->status = 1;
            if ($model->save(false)) {
                return $this->redirect(['addressinfo', 'id' => $model->party_id]);
            }
        }
        return $this->render('_address', [
            'model' => $model,
            'party_id' => $party_id,
        ]);
    }

    public function actionMyorder()
    {
        $id=0;
        if (!isset($_SESSION['user_id'])) {
            return $this->redirect(['site/login']);
        }
        if (isset($_SESSION['user_customer_id'])) {
            $id = $_SESSION['user_customer_id'];
        }
        $model = null;
        $party_id = 0;
        if ($id) {
            $party_id = $id;
            $model = \backend\models\Order::find()->where(['customer_id' => $id])->all();
        }
        return $this->render('_myorder', [
            'model' => $model,
            'party_id' => $party_id,
        ]);
    }

    public function actionMyorderdetail($id)
    {
        if (!isset($_SESSION['user_id'])) {
            return $this->redirect(['site/login']);
        }
        $model = null;
        $model_line = null;
        if ($id) {
            $model = \backend\models\Order::find()->where(['id' => $id])->one();
            $model_line = \common\models\OrderLine::find()->where(['order_id' => $id])->all();
        }
        return $this->render('_myorderdetail', [
            'model_line' => $model_line,
            'model' => $model,
            'party_id' => 1,
        ]);
    }

    public function actionProductdetail($id)
    {
        $customer_id=0;

        if (isset($_SESSION['user_customer_id'])) {
            $customer_id = $_SESSION['user_customer_id'];
        }
        if ($id) {
            $model = \common\models\ViewProductPage::find()->where(['id' => $id])->one();
        }
        return $this->render('_productdetail', [
            'model' => $model,
            'customer_id' => $customer_id,
        ]);
    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            $customer_id = \backend\models\User::findCustomerId(\Yii::$app->user->identity->id);
            $_SESSION['user_id'] = \Yii::$app->user->identity->id;
            $_SESSION['user_customer_id'] = $customer_id;

            return $this->goBack();
        }

        $model->password = '';

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
        unset($_SESSION['user_id']);
        unset($_SESSION['user_customer_id']);
        unset($_SESSION['cart']);
        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return mixed
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('success', 'Thank you for contacting us. We will respond to you as soon as possible.');
            } else {
                Yii::$app->session->setFlash('error', 'There was an error sending your message.');
            }

            return $this->refresh();
        }

        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return mixed
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post()) && $model->signup()) {
//            $model_customer = new Customer();
//            $model_customer->email = $model->email;
//            $model_customer->status = 0;
//            if($model_customer->save(false)){
//            //    $max_id = \backend\models\User::find()->where(['status'=>10])->max('id');
//              //  \backend\models\User::updateAll(['customer_ref_id' => $model_customer->id], ['id' => $max_id]);
//            }
            Yii::$app->session->setFlash('success', 'ขอบคุณสำหรับการลงทะเบียน. กรุณายืนยันการลงทะเบียนผ่านทาง Inbox อีเมลของคุณ.');
            return $this->goHome();
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');

                return $this->goHome();
            }

            Yii::$app->session->setFlash('error', 'Sorry, we are unable to reset password for the provided email address.');
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'New password saved.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    /**
     * Verify email address
     *
     * @param string $token
     * @return yii\web\Response
     * @throws BadRequestHttpException
     */
    public function actionVerifyEmail($token)
    {
        try {
            $model = new VerifyEmailForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        if (($user = $model->verifyEmail()) && Yii::$app->user->login($user)) {
            Yii::$app->session->setFlash('success', 'Your email has been confirmed!');
            return $this->goHome();
        }

        Yii::$app->session->setFlash('error', 'Sorry, we are unable to verify your account with provided token.');
        return $this->goHome();
    }

    /**
     * Resend verification email
     *
     * @return mixed
     */
    public function actionResendVerificationEmail()
    {
        $model = new ResendVerificationEmailForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');
                return $this->goHome();
            }
            Yii::$app->session->setFlash('error', 'Sorry, we are unable to resend verification email for the provided email address.');
        }

        return $this->render('resendVerificationEmail', [
            'model' => $model
        ]);
    }

    public
    function actionShowcity($id)
    {
        $model = \common\models\Amphur::find()->where(['PROVINCE_ID' => $id])->all();

        if (count($model) > 0) {
            echo "<option>--- เลือกอำเภอ ---</option>";
            foreach ($model as $value) {

                echo "<option value='" . $value->AMPHUR_ID . "'>$value->AMPHUR_NAME</option>";

            }
        } else {
            echo "<option>-</option>";
        }
    }

    public
    function actionShowdistrict($id)
    {
        $model = \common\models\District::find()->where(['AMPHUR_ID' => $id])->all();

        if (count($model) > 0) {
            foreach ($model as $value) {

                echo "<option value='" . $value->DISTRICT_ID . "'>$value->DISTRICT_NAME</option>";

            }
        } else {
            echo "<option>-</option>";
        }
    }

    public function actionShowzipcode($id)
    {
        $model = \common\models\Amphur::find()->where(['AMPHUR_ID' => $id])->one();
//        echo $id;
        if ($model) {
            echo $model->POSTCODE;
//            echo '1110';
        } else {
            echo "";
        }
//        echo '111';
    }

    public function actionAddcart()
    {
        $product_id = \Yii::$app->request->post('product_id');
        $product_name = \Yii::$app->request->post('product_name');
        $qty = \Yii::$app->request->post('qty');
        $price = \Yii::$app->request->post('price');
        $sku = \Yii::$app->request->post('sku');
        $photo = \Yii::$app->request->post('photo');

        if ($product_id) {
            //if (isset($_POST['add_to_cart'])) {
            if (isset($_SESSION['cart'])) {
                $session_array_id = array_column($_SESSION['cart'], 'product_id');
                print_r($session_array_id);
                if (!in_array($product_id, $session_array_id)) {
                    $session_array = array(
                        'product_id' => $product_id,
                        'sku' => $sku,
                        'product_name' => $product_name, // $_POST['name'],
                        'price' => $price, //$_POST['price'],
                        'qty' => (float)$qty, //$_POST['qty']
                        'photo' => $photo, //$_POST['qty']
                    );
                    //  echo 1;
                    $_SESSION['cart'][] = $session_array;
                } else {
                    $index = array_search($product_id, $session_array_id);
                    //                 if (in_array($product_id, $session_array_id)) {
                    $_SESSION['cart'][$index]['qty'] = $qty;
//                            $_SESSION['cart'][$product_id]['total'] = $qty;
                    //                  }
//                        $session_array = array(
//                            "product_id" => $product_id,
//                            "product_name" =>  $product_name, // $_POST['name'],
//                            "price" => $price, //$_POST['price'],
//                            "qty" => $qty, //$_POST['qty']
//                        );

//                        $_SESSION['cart'][] = $session_array;
                    //  echo 100;
                }

            } else {
                $session_array = array(
                    'product_id' => $product_id,
                    'sku' => $sku,
                    'product_name' => $product_name, // $_POST['name'],
                    'price' => $price, //$_POST['price'],
                    'qty' => (float)$qty, //$_POST['qty']
                    'photo' => $photo,
                );
                //  echo 2;
                $_SESSION['cart'][] = $session_array;
            }
            //}
        }
        return $this->redirect(['site/index']);
    }

    public function actionUpdatecart()
    {
        $product_id = \Yii::$app->request->post('product_id');
        $qty = \Yii::$app->request->post('qty');

        if ($product_id) {
            if (isset($_SESSION['cart'])) {
                $session_array_id = array_column($_SESSION['cart'], 'product_id');
                if (in_array($product_id, $session_array_id)) {
                    $index = array_search($product_id, $session_array_id);
                    $_SESSION['cart'][$index]['qty'] = $qty;
                }
            }
            echo "success";
        }
    }

    public function actionRemovecart()
    {
        $product_id = \Yii::$app->request->post('product_id');
        if ($product_id) {
            if (isset($_SESSION['cart'])) {
                $session_array_id = array_column($_SESSION['cart'], 'product_id');
                if (in_array($product_id, $session_array_id)) {
                    $index = array_search($product_id, $session_array_id);
                    unset($_SESSION['cart'][$index]);
                }
            }
            echo "success";
        }
    }

    public function actionCreateorder()
    {
        $customer_id = 0;
        if(!isset($_SESSION['user_id'])){
            return $this->redirect(['site/login']);
        }
        if (isset($_SESSION['user_customer_id'])) {
            $customer_id = $_SESSION['user_customer_id'];
        }
        if (isset($_SESSION['cart'])) {
            if (!empty($_SESSION['cart'])) {

                $order_amount = 0;
                $model_order = new \backend\models\Order();
                $model_order->order_no = $model_order::getLastNo();
                $model_order->order_date = date('Y-m-d H:i:s');
                $model_order->customer_id = $customer_id;
                $model_order->customer_type = 1;
                $model_order->transfer_bank_account_id = 1;
                $model_order->total_amount = 0;
                $model_order->status = 1;

                if ($model_order->save(false)) {
                    $total_amount = 0;
                    foreach ($_SESSION['cart'] as $key => $value) {
                        $order_amount+=((float)$value['qty'] * (float)$value['price']);
                        $total_amount = $total_amount + (float)$value['qty'] * (float)$value['price'];
                        $model_line = new \common\models\OrderLine();
                        $model_line->order_id = $model_order->id;
                        $model_line->product_id = $value['product_id'];
                        $model_line->qty = $value['qty'];
                        $model_line->price = $value['price'];
                        $model_line->line_total = (float)$value['qty'] * (float)$value['price'];
                        $model_line->status = 1;
                        $model_line->save(false);
                    }
                    \backend\models\Order::updateAll(['total_amount' => $total_amount], ['id' => $model_order->id]);
                    unset($_SESSION['cart']);

                    $this->notifymessage($model_order->order_no,$order_amount);
                }

            }
        }
        return $this->redirect(['site/index']);
    }
    public function actionAddcart2()
    {
        $product_id = \Yii::$app->request->post('product_id');

        $customer_id = 0;
        if (isset($_SESSION['user_customer_id'])) {
            $customer_id = $_SESSION['user_customer_id'];
        }

        $product_name = '';
        $qty = 1;
        $price = 0;
        $sku = '';
        $photo = '';

        if ($product_id) {

            $model_prod = \common\models\ViewProductPage::find()->where(['id' => $product_id])->one();
            if($model_prod){
                $product_name = $model_prod->name;
                $sku = $model_prod->sku;
                $photo = $model_prod->photo;

                if ($model_prod->customer_id != null && $model_prod->customer_id == $customer_id) {
                    $price = $model_prod->customer_sale_price;
                }else{
                    $price = $model_prod->sale_price;
                }
            }

            //if (isset($_POST['add_to_cart'])) {
            if (isset($_SESSION['cart'])) {
                $session_array_id = array_column($_SESSION['cart'], 'product_id');
              //  print_r($session_array_id);
                if (!in_array($product_id, $session_array_id)) {
                    $session_array = array(
                        'product_id' => $product_id,
                        'sku' => $sku,
                        'product_name' => $product_name, // $_POST['name'],
                        'price' => $price, //$_POST['price'],
                        'qty' => (float)$qty, //$_POST['qty']
                        'photo' => $photo, //$_POST['qty']
                    );
                    //  echo 1;
                    $_SESSION['cart'][] = $session_array;
                } else {
                    $index = array_search($product_id, $session_array_id);
                    //                 if (in_array($product_id, $session_array_id)) {
                    $_SESSION['cart'][$index]['qty'] = $_SESSION['cart'][$index]['qty'] + $qty;
                }

            } else {
                $session_array = array(
                    'product_id' => $product_id,
                    'sku' => $sku,
                    'product_name' => $product_name, // $_POST['name'],
                    'price' => $price, //$_POST['price'],
                    'qty' => (float)$qty, //$_POST['qty']
                    'photo' => $photo,
                );
                //  echo 2;
                $_SESSION['cart'][] = $session_array;
            }
            //}
        }
        return $this->redirect(['site/index']);
    }

    public function notifymessage($order_no,$total_amount)
    {
        if($order_no != null || $order_no != ''){
            //$message = "This is test send request from camel paperless";
            $line_api = 'https://notify-api.line.me/api/notify';
            $line_token = '';

            $b_token = '8H8dtjz5QWvWWBFrMAwYrglYhkwu3Pw7rnXeBK9vYFK';
            $line_token = trim($b_token);


            $journal_detail = '';
         //   $user_name = \backend\models\User::findName($user_id);


            $message = '' . "\n";
            $message .= 'แจ้งเตือนมีคำสั่งซื้อใหม่' . "\n";
            $message .= 'เลขที่คำสั่งซื้อ:' . $order_no . "\n";
            //   $message .= 'User:' . \backend\models\User::findName($user_id) . "\n";
            $message .= "วันที่:" . date('Y-m-d') . "(" . date('H:i:s') . ")" . "\n";

            $message .= 'ยอดเงินสั่งซื้อ: ' .number_format($total_amount,2). "\n";
//            $message .= "รายละเอียด: \n " . $journal_detail. "\n";

            //  $message .= 'สามารถดูรายละเอียดได้ที่ http:///103.253.73.108/icesystemdindang/backend/web/index.php?r=dailysum/indexnew' . "\n"; // bkt


            $queryData = array('message' => $message);
            $queryData = http_build_query($queryData, '', '&');
            $headerOptions = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n"
                        . "Authorization: Bearer " . $line_token . "\r\n"
                        . "Content-Length: " . strlen($queryData) . "\r\n",
                    'content' => $queryData
                )
            );
            $context = stream_context_create($headerOptions);
            $result = file_get_contents($line_api, FALSE, $context);
            $res = json_decode($result);
            return $res;
        }

    }
}
