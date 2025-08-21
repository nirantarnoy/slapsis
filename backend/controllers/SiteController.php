<?php

namespace backend\controllers;

use common\models\LoginForm;
use common\models\TiktokToken;
use GuzzleHttp\Client;
use yii\helpers\Url;
use Yii;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\db\Query;

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
                'rules' => [
                    [
                        'actions' => ['login', 'error','logindriver'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index', 'changepassword','grab','logoutdriver','connect-tiktok','tiktok-callback','shopee-callback','connect-shopee'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post', 'get'],
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
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */


    public function actionIndex()
    {

        // รับค่าวันที่จาก request หรือใช้ค่า default (30 วันย้อนหลัง)
        $fromDate = Yii::$app->request->get('from_date', date('Y-m-d', strtotime('-30 days')));
        $toDate = Yii::$app->request->get('to_date', date('Y-m-d'));

        // แปลงวันที่เป็น timestamp
        $fromTimestamp = strtotime($fromDate);
        $toTimestamp = strtotime($toDate . ' 23:59:59');


        return $this->render('index', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    /**
     * Login action.
     *
     * @return string|Response
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $this->layout = 'blank';

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            //echo "login ok"; return;
            // return $this->goBack();
            $model_user_info = \backend\models\User::find()->where(['id' => \Yii::$app->user->id])->one();
            if($model_user_info){
                if($model_user_info->user_group_id == 3){
                    \Yii::$app->user->logout();
                }
            }
            return $this->redirect(['site/index']);
        }

        //   $model->password = '';
        $model->password = '';
        $this->layout = 'main_login';
        $model->password = '';
        return $this->render('login_new', [
            'model' => $model,
        ]);


    }




    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        \Yii::$app->user->logout();

//        if(isset($_SESSION['driver_login'])){
//            return $this->redirect(['site/logindriver']);
//        }

        return $this->goHome();
    }
    public function actionLogoutdriver()
    {
        \Yii::$app->user->logout();

        return $this->redirect(['site/logindriver']);
    }


    public function actionChangepassword()
    {
        $model = new \backend\models\Resetform();
        if ($model->load(Yii::$app->request->post())) {

            $model_user = \backend\models\User::find()->where(['id' => Yii::$app->user->id])->one();
            if ($model->oldpw != '' && $model->newpw != '' && $model->confirmpw != '') {
                if ($model->confirmpw != $model->newpw) {
                    $session = Yii::$app->session;
                    $session->setFlash('msg_err', 'รหัสยืนยันไม่ตรงกับรหัสใหม่');
                } else {
                    if ($model_user->validatePassword($model->oldpw)) {
                        $model_user->setPassword($model->confirmpw);
                        if ($model_user->save()) {
                            $session = Yii::$app->session;
                            $session->setFlash('msg_success', 'ทำการเปลี่ยนรหัสผ่านเรียบร้อยแล้ว');
                            return $this->redirect(['site_/logout']);
                        }
                    } else {
                        $session = Yii::$app->session;
                        $session->setFlash('msg_err', 'รหัสผ่านเดิมไม่ถูกต้อง');
                    }
                }

            } else {
                $session = Yii::$app->session;
                $session->setFlash('msg_err', 'กรุณาป้อนข้อมูลให้ครบ');
            }

        }
        return $this->render('_setpassword', [
            'model' => $model
        ]);
    }

    public function actionGrab()
    {

        $aControllers = [];


        // $path = \Yii::$app->getBasePath() . 'icesystem/';
        $path = \Yii::$app->basePath;

        $ctrls = function ($path) use (&$ctrls, &$aControllers) {

            $oIterator = new \DirectoryIterator($path);

            foreach ($oIterator as $oFile) {

                if (!$oFile->isDot()

                    && (false !== strpos($oFile->getPathname(), 'controllers')

                        || false !== strpos($oFile->getPathname(), 'modules')

                    )

                ) {


                    if ($oFile->isDir()) {

                        $ctrls($oFile->getPathname());

                    } else {

                        if (strpos($oFile->getBasename(), 'Controller.php')) {


                            $content = file_get_contents($oFile->getPathname());

                            $controllerName = $oFile->getBasename('.php');


                            $route = explode(\Yii::$app->basePath, $oFile->getPathname());

                            $route = str_ireplace(array('modules', 'controllers', 'Controller.php'), '', $route[1]);

                            $route = preg_replace("/(\/){2,}/", "/", $route);


                            $aControllers[$controllerName] = [

                                'filepath' => $oFile->getPathname(),

                                'route' => mb_strtolower($route),

                                'actions' => [],

                            ];

                            preg_match_all('#function action(.*)\(#ui', $content, $aData);


                            $acts = function ($aData) use (&$aControllers, &$controllerName) {


                                if (!empty($aData) && isset($aData[1]) && !empty($aData[1])) {


                                    $aControllers[$controllerName]['actions'] = array_map(

                                        function ($actionName) {
                                            return mb_strtolower(trim($actionName, '{\\.*()'));
                                        },

                                        $aData[1]

                                    );


                                }

                            };


                            $acts($aData);

                        }

                    }


                }

            }

        };


        $ctrls($path);


        echo '<pre>';

        //   print_r($aControllers);

        foreach ($aControllers as $value) {

            //  $route_name = substr($value['route'],2);
            $route_name = substr($value['route'], 1);
            for ($x = 0; $x <= count($value['actions']) - 1; $x++) {
                $fullname = $route_name . '/' . $value['actions'][$x];
                if ($fullname != '') {
                    $chk = \common\models\AuthItem::find()->where(['name' => $fullname])->one();
                    if ($chk) continue;

                    $model = new \common\models\AuthItem();
                    $model->name = $fullname;
                    $model->type = 2;
                    $model->description = '';
                    $model->created_at = time();
                    $model->save(false);
                }
                echo $fullname . '<br/>';

            }
            //echo $route_name;
            // print_r($value['route']);
        }
        // print_r($aControllers['AdjustmentController']);

    }

    /**
     * ดึงข้อมูลยอดขายแยกตามสินค้า
     */
    private function getSalesByProduct($fromTimestamp, $toTimestamp)
    {
        $query = (new Query())
            ->select([
                'p.id',
                'p.code',
                'p.name',
                'SUM(jtl.qty) as total_qty',
                'SUM(jtl.qty * jtl.line_price) as total_sales',
                'AVG(jtl.line_price) as avg_price',
                'AVG(p.cost_price) as cost_price',
                'SUM(jtl.qty * jtl.line_price) - SUM(jtl.qty * p.cost_price) as profit'
            ])
            ->from(['jtl' => 'journal_trans_line'])
            ->innerJoin(['p' => 'product'], 'jtl.product_id = p.id')
            ->innerJoin(['jt' => 'journal_trans'], 'jtl.journal_trans_id = jt.id')
            ->where(['between', 'jt.created_at', $fromTimestamp, $toTimestamp])
            ->andWhere(['jt.status' => 1,'jt.trans_type_id' => 3]) // สมมติว่า status 1 = ขายสำเร็จ
            ->groupBy(['p.id', 'p.code', 'p.name', 'p.cost_price'])
            ->orderBy(['total_sales' => SORT_DESC]);

        return $query->all();
    }

    /**
     * ดึงข้อมูลสำหรับกราฟเปรียบเทียบราคาขายกับต้นทุน
     */
    private function getPriceComparisonData($fromTimestamp, $toTimestamp)
    {
        $query = (new Query())
            ->select([
                'p.name',
                'p.cost_price',
                'AVG(jtl.line_price) as avg_sale_price',
                'SUM(jtl.qty) as total_qty'
            ])
            ->from(['jtl' => 'journal_trans_line'])
            ->innerJoin(['p' => 'product'], 'jtl.product_id = p.id')
            ->innerJoin(['jt' => 'journal_trans'], 'jtl.journal_trans_id = jt.id')
            ->where(['between', 'jt.created_at', $fromTimestamp, $toTimestamp])
            ->andWhere(['jt.status' => 1,'jt.trans_type_id' => 3])
            ->groupBy(['p.id', 'p.name', 'p.cost_price'])
            ->having('SUM(jt.qty) > 0')
            ->orderBy(['total_qty' => SORT_DESC])
            ->limit(20); // จำกัดแค่ 20 สินค้าสำหรับกราฟ

        $data = $query->all();

        // จัดรูปแบบข้อมูลสำหรับ Highcharts
        $categories = [];
        $costPrices = [];
        $salePrices = [];
        $profits = [];

        foreach ($data as $item) {
            $categories[] = $item['name'];
            $costPrices[] = floatval($item['cost_price']);
            $salePrices[] = floatval($item['avg_sale_price']);
            $profits[] = floatval($item['avg_sale_price']) - floatval($item['cost_price']);
        }

        return [
            'categories' => $categories,
            'costPrices' => $costPrices,
            'salePrices' => $salePrices,
            'profits' => $profits
        ];
    }

    /**
     * ดึงข้อมูลสินค้าขายดี 10 อันดับ
     */
    private function getTopProducts($fromTimestamp, $toTimestamp)
    {
        $query = (new Query())
            ->select([
                'p.name',
                'p.code',
                'SUM(jtl.qty) as total_qty',
                'SUM(jtl.qty * jtl.line_price) as total_sales'
            ])
            ->from(['jtl' => 'journal_trans_line'])
            ->innerJoin(['p' => 'product'], 'jtl.product_id = p.id')
            ->innerJoin(['jt' => 'journal_trans'], 'jtl.journal_trans_id = jt.id')
            ->where(['between', 'jt.created_at', $fromTimestamp, $toTimestamp])
            ->andWhere(['jt.status' => 1,'jt.trans_type_id' => 3])
            ->groupBy(['p.id', 'p.name', 'p.code'])
            ->orderBy(['total_qty' => SORT_DESC])
            ->limit(10);

        $data = $query->all();

        // จัดรูปแบบข้อมูลสำหรับ Highcharts
        $categories = [];
        $quantities = [];
        $sales = [];

        foreach ($data as $item) {
            $categories[] = $item['name'];
            $quantities[] = intval($item['total_qty']);
            $sales[] = floatval($item['total_sales']);
        }

        return [
            'categories' => $categories,
            'quantities' => $quantities,
            'sales' => $sales,
            'rawData' => $data
        ];
    }

    /**
     * Export ข้อมูลเป็น Excel (optional)
     */
    public function actionExport()
    {
        $fromDate = Yii::$app->request->get('from_date', date('Y-m-d', strtotime('-30 days')));
        $toDate = Yii::$app->request->get('to_date', date('Y-m-d'));

        $fromTimestamp = strtotime($fromDate);
        $toTimestamp = strtotime($toDate . ' 23:59:59');

        $salesData = $this->getSalesByProduct($fromTimestamp, $toTimestamp);

        // สร้าง CSV
        $filename = 'sales_report_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Header
        fputcsv($output, ['รหัสสินค้า', 'ชื่อสินค้า', 'จำนวนขาย', 'ยอดขาย', 'ราคาเฉลี่ย', 'ต้นทุน', 'กำไร']);

        // Data
        foreach ($salesData as $row) {
            fputcsv($output, [
                $row['code'],
                $row['name'],
                $row['total_qty'],
                number_format($row['total_sales'], 2),
                number_format($row['avg_price'], 2),
                number_format($row['cost_price'], 2),
                number_format($row['profit'], 2)
            ]);
        }

        fclose($output);
        exit;
    }

//    public function actionConnectTiktok()
//    {
//        $appKey = 'YOUR_APP_KEY';
//        $redirectUri = Url::to(['site/tiktok-callback'], true);
//        $state = Yii::$app->security->generateRandomString(12);
//
//        $url = "https://auth.tiktok-shops.com/oauth/authorize?"
//            . http_build_query([
//                'app_key' => $appKey,
//                'redirect_uri' => $redirectUri,
//                'state' => $state
//            ]);
//
//        return $this->redirect($url);
//    }

    public function actionConnectTiktok()
    {
        $appKey = '6gtr1ukn2k2hj'; // Make sure this matches your actual app key
        $redirectUri = Url::to(['site/tiktok-callback'], true);
        $state = Yii::$app->security->generateRandomString(12);

        // Store state in session for validation later (optional but recommended for security)
        Yii::$app->session->set('tiktok_oauth_state', $state);

        $url = "https://auth.tiktok-shops.com/oauth/authorize?"
            . http_build_query([
                'app_key' => $appKey,
                'redirect_uri' => $redirectUri,
                'state' => $state,
                'response_type' => 'code'
            ]);

        return $this->redirect($url);
    }

    public function actionTiktokCallback()
    {
        // Get parameters from the request
        $code = Yii::$app->request->get('code');
        $shop_id = Yii::$app->request->get('shop_id');
        $state = Yii::$app->request->get('state');
        $error = Yii::$app->request->get('error');

        // Check for authorization errors
        if ($error) {
            Yii::$app->session->setFlash('error', 'TikTok authorization error: ' . $error);
            return $this->redirect(['site/index']);
        }

        // Validate required parameters
        if (!$code) {
            Yii::$app->session->setFlash('error', 'Missing authorization code from TikTok');
            return $this->redirect(['site/index']);
        }

        if (!$shop_id) {
            Yii::$app->session->setFlash('error', 'Missing shop_id from TikTok');
            return $this->redirect(['site/index']);
        }

        // Validate state parameter (optional but recommended for security)
        $sessionState = Yii::$app->session->get('tiktok_oauth_state');
        if ($sessionState && $sessionState !== $state) {
            Yii::$app->session->setFlash('error', 'Invalid state parameter');
            return $this->redirect(['site/index']);
        }

        // Clear the state from session
        Yii::$app->session->remove('tiktok_oauth_state');

        $appKey = '6gtr1ukn2k2hj';
        $appSecret = 'ea10324fb3c72d8e3a6b3c0a83672df6ea8f131d';
        $url = "https://auth.tiktok-shops.com/api/v2/token/get";

        try {
            $client = new \GuzzleHttp\Client();
            $res = $client->post($url, [
                'form_params' => [
                    'app_key' => $appKey,
                    'app_secret' => $appSecret,
                    'code' => $code,
                    'grant_type' => 'authorized_code',
                ],
                'timeout' => 30 // Add timeout
            ]);

            $statusCode = $res->getStatusCode();
            $body = $res->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("HTTP Error: $statusCode");
            }

            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("JSON decode error: " . json_last_error_msg());
            }

            if (isset($data['data']['access_token'])) {
                TiktokToken::saveToken(
                    $shop_id,
                    $data['data']['access_token'],
                    $data['data']['refresh_token'],
                    $data['data']['expires_in']
                );

                Yii::$app->session->setFlash('success', 'เชื่อมต่อ TikTok สำเร็จ');
            } else {
                $errorMsg = isset($data['message']) ? $data['message'] : 'Unknown error';
                Yii::$app->session->setFlash('error', 'ไม่สามารถเชื่อมต่อ TikTok ได้: ' . $errorMsg);
            }
        } catch (\Exception $e) {
            Yii::error('TikTok callback error: ' . $e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }

        return $this->redirect(['site/index']);
    }

//    public function actionTiktokCallback($code, $shop_id, $state = null)
//    {
//        $appKey = '6gtr1ukn2k2hj';
//        $appSecret = 'ea10324fb3c72d8e3a6b3c0a83672df6ea8f131d';
//        $url = "https://auth.tiktok-shops.com/api/v2/token/get";
//
//        $client = new \GuzzleHttp\Client();
//        $res = $client->post($url, [
//            'form_params' => [
//                'app_key' => $appKey,
//                'app_secret' => $appSecret,
//                'code' => $code,
//                'grant_type' => 'authorized_code',
//            ]
//        ]);
//
//        $data = json_decode($res->getBody(), true);
//
//        if (isset($data['data']['access_token'])) {
//            TiktokToken::saveToken(
//                $shop_id,
//                $data['data']['access_token'],
//                $data['data']['refresh_token'],
//                $data['data']['expires_in']
//            );
//
//            Yii::$app->session->setFlash('success', 'เชื่อมต่อ TikTok สำเร็จ');
//        } else {
//            Yii::$app->session->setFlash('error', 'ไม่สามารถเชื่อมต่อ TikTok ได้');
//        }
//
//        return $this->redirect(['site/index']);
//    }

    /**
     * เริ่มต้นการเชื่อมต่อ Shopee OAuth
     */
    public function actionConnectShopeex()
    {
        // ข้อมูลแอปของคุณ (ควรเก็บใน config หรือ environment variables)
        $partner_id = 1178090; // เปลี่ยนเป็นของคุณ
        $partner_key = 'shpk6573466d784257526c476e4e796e7950694d4c6c516946744e6a4e556854'; // เปลี่ยนเป็นของคุณ
        $redirect_url = Url::to(['site/shopee-callback'], true); // สร้าง URL อัตโนมัติ

        $timestamp = time();
        $state = Yii::$app->security->generateRandomString(12);

        // เก็บ state ใน session เพื่อตรวจสอบความปลอดภัย
        Yii::$app->session->set('shopee_oauth_state', $state);

        // สร้าง signature สำหรับ authorization
        $base_string = "$partner_id$redirect_url$timestamp";
        $sign = hash_hmac('sha256', $base_string, $partner_key);

        // สร้าง authorization URL
      //  $auth_url = "https://partner.shopeemobile.com/api/v2/shop/auth_partner?" .
            $auth_url = "https://partner.test-stable.shopeemobile.com/api/v2/shop/auth_partner?" .
                http_build_query([
                'partner_id' => $partner_id,
                'redirect' => $redirect_url,
                'timestamp' => $timestamp,
                'sign' => $sign,
                'state' => $state
            ]);

        return $this->redirect($auth_url);
    }

    public function actionConnectShopee()
    {
        $partner_id = 1178090;
        $partner_key = 'shpk6573466d784257526c476e4e796e7950694d4c6c516946744e6a4e556854';
        $redirect_url = Url::to(['https://www.pjrichth.co/site/shopee-callback'], true);

        $timestamp = time();
        $state = Yii::$app->security->generateRandomString(12);

        Yii::$app->session->set('shopee_oauth_state', $state);

        // ✅ base_string ต้องใช้ raw redirect_url (ยังไม่ urlencode)
        $base_string = $partner_id . $redirect_url . $timestamp;
        $sign = hash_hmac('sha256', $base_string, $partner_key);

        // ✅ redirect_url ต้อง urlencode ตอนส่ง query string
        $auth_url = "https://partner.test-stable.shopeemobile.com/api/v2/shop/auth_partner?" . http_build_query([
                'partner_id' => $partner_id,
                'redirect'   => $redirect_url,
                'timestamp'  => $timestamp,
                'sign'       => $sign,
                'state'      => $state
            ]);

        return $this->redirect($auth_url);
    }


    /**
     * รับ callback จาก Shopee หลังจากผู้ใช้อนุญาต
     */
    public function actionShopeeCallback()
    {
        $code = Yii::$app->request->get('code');
        $shop_id = Yii::$app->request->get('shop_id');
        $state = Yii::$app->request->get('state');
        $error = Yii::$app->request->get('error');

        // ตรวจสอบข้อผิดพลาด
        if ($error) {
            Yii::$app->session->setFlash('error', 'Shopee authorization error: ' . $error);
            return $this->redirect(['site/index']);
        }

        if (!$code) {
            Yii::$app->session->setFlash('error', 'Missing authorization code from Shopee');
            return $this->redirect(['site/index']);
        }

        if (!$shop_id) {
            Yii::$app->session->setFlash('error', 'Missing shop_id from Shopee');
            return $this->redirect(['site/index']);
        }

        // ตรวจสอบ state parameter เพื่อความปลอดภัย
        $sessionState = Yii::$app->session->get('shopee_oauth_state');
        if ($sessionState && $sessionState !== $state) {
            Yii::$app->session->setFlash('error', 'Invalid state parameter');
            return $this->redirect(['site/index']);
        }

        // ลบ state จาก session
        Yii::$app->session->remove('shopee_oauth_state');

        // ข้อมูลแอปของคุณ (ควรเก็บใน config)
        $partner_id = 1178090; // เปลี่ยนเป็นของคุณ
        $partner_key = 'shpk6573466d784257526c476e4e796e7950694d4c6c516946744e6a4e556854'; // เปลี่ยนเป็นของคุณ
        $redirect_url = Url::to(['site/shopee-callback'], true);

        $timestamp = time();

        // สร้าง signature สำหรับ token exchange
        $base_string = "$partner_id$redirect_url$timestamp$code";
        $sign = hash_hmac('sha256', $base_string, $partner_key);

        try {
            // ส่ง request ไปแลก access_token
            $client = new \GuzzleHttp\Client();
            $response = $client->post('https://partner.shopeemobile.com/api/v2/auth/token/get', [
                'form_params' => [
                    'code' => $code,
                    'partner_id' => $partner_id,
                    'sign' => $sign,
                    'timestamp' => $timestamp,
                    'redirect_uri' => $redirect_url,
                ],
                'timeout' => 30
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($statusCode !== 200) {
                throw new \Exception("HTTP Error: $statusCode - $body");
            }

            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("JSON decode error: " . json_last_error_msg());
            }

            if (isset($data['access_token'])) {
                // บันทึกลงฐานข้อมูล
                $this->saveShopeeToken($shop_id, $data);

                Yii::$app->session->setFlash('success', 'เชื่อมต่อ Shopee สำเร็จ! Shop ID: ' . $shop_id);
            } else {
                $errorMsg = isset($data['message']) ? $data['message'] : 'Unknown error';
                $errorCode = isset($data['error']) ? $data['error'] : 'unknown';
                Yii::$app->session->setFlash('error', "ไม่สามารถเชื่อมต่อ Shopee ได้: [$errorCode] $errorMsg");
            }

        } catch (\Exception $e) {
            Yii::error('Shopee callback error: ' . $e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }

        return $this->redirect(['site/index']);
    }

    /**
     * บันทึก Shopee token ลงฐานข้อมูล
     */
    private function saveShopeeToken($shop_id, $tokenData)
    {
        try {
            // ตรวจสอบว่ามี token เก่าอยู่หรือไม่
            $existingToken = (new \yii\db\Query())
                ->from('shopee_token')
                ->where(['shop_id' => $shop_id])
                ->one();

            if ($existingToken) {
                // อัพเดท token เก่า
                Yii::$app->db->createCommand()->update('shopee_token', [
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'],
                    'expire_in' => $tokenData['expire_in'],
                    'updated_at' => date('Y-m-d H:i:s'),
                    'expires_at' => date('Y-m-d H:i:s', time() + $tokenData['expire_in'])
                ], ['shop_id' => $shop_id])->execute();
            } else {
                // สร้าง token ใหม่
                Yii::$app->db->createCommand()->insert('shopee_token', [
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'],
                    'expire_in' => $tokenData['expire_in'],
                    'shop_id' => $shop_id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'expires_at' => date('Y-m-d H:i:s', time() + $tokenData['expire_in'])
                ])->execute();
            }

            return true;
        } catch (\Exception $e) {
            Yii::error('Error saving Shopee token: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * ดึง Shopee token ที่ใช้งานได้
     */
    private function getValidShopeeToken($shop_id)
    {
        $token = (new \yii\db\Query())
            ->from('shopee_token')
            ->where(['shop_id' => $shop_id])
            ->andWhere(['>', 'expires_at', date('Y-m-d H:i:s')])
            ->one();

        if (!$token) {
            // พยายาม refresh token ถ้า token หมดอายุ
            return $this->refreshShopeeToken($shop_id);
        }

        return $token;
    }

    /**
     * Refresh Shopee token
     */
    private function refreshShopeeToken($shop_id)
    {
        $tokenRecord = (new \yii\db\Query())
            ->from('shopee_token')
            ->where(['shop_id' => $shop_id])
            ->one();

        if (!$tokenRecord || !$tokenRecord['refresh_token']) {
            return null;
        }

        $partner_id = 1178090; // เปลี่ยนเป็นของคุณ
        $partner_key = 'shpk6573466d784257526c476e4e796e7950694d4c6c516946744e6a4e556854'; // เปลี่ยนเป็นของคุณ
        $timestamp = time();

        $base_string = "$partner_id$timestamp";
        $sign = hash_hmac('sha256', $base_string, $partner_key);

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post('https://partner.shopeemobile.com/api/v2/auth/access_token/get', [
                'form_params' => [
                    'refresh_token' => $tokenRecord['refresh_token'],
                    'partner_id' => $partner_id,
                    'sign' => $sign,
                    'timestamp' => $timestamp,
                    'shop_id' => $shop_id
                ],
                'timeout' => 30
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['access_token'])) {
                $this->saveShopeeToken($shop_id, $data);
                return $data;
            }

        } catch (\Exception $e) {
            Yii::error('Error refreshing Shopee token: ' . $e->getMessage(), __METHOD__);
        }

        return null;
    }


}
