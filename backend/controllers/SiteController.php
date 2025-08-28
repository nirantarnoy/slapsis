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
                        'actions' => ['logout', 'index', 'changepassword','grab','logoutdriver','connect-tiktok','tiktok-callback','shopee-callback',
                            'connect-shopee','test-shopee-signature','test-shopee-token-exchange','test-with-real-code'],
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

//    public function actionConnectTiktok()
//    {
//        $appKey = '6h9n461r774e1'; // ✅ ใช้ app key จริง
//        $redirectUri = 'https://www.pjrichth.co/site/tiktok-callback';
//        $state = Yii::$app->security->generateRandomString(32);
//
//        // ✅ เปิด session และบันทึก state
//        Yii::$app->session->open();
//        Yii::$app->session->set('tiktok_oauth_state', $state);
//
//        // ✅ Debug: ตรวจสอบค่าที่ใช้
//        Yii::info("TikTok App Key: {$appKey}", __METHOD__);
//        Yii::info("TikTok Redirect URI: {$redirectUri}", __METHOD__);
//        Yii::info("TikTok State: {$state}", __METHOD__);
//
//        // ✅ สร้าง parameters สำหรับ TikTok authorization
//        $params = [
//            'app_key' => $appKey,
//            'redirect_uri' => $redirectUri,
//            'state' => $state,
//            'response_type' => 'code'
//        ];
//
//        // ✅ Debug: ตรวจสอบ parameters
//        Yii::info("TikTok Auth parameters: " . json_encode($params), __METHOD__);
//
//        $query_string = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
//        $auth_url = "https://auth.tiktok-shops.com/oauth/authorize?{$query_string}";
//
//        // ✅ Debug: ตรวจสอบ URL สุดท้าย
//        Yii::info("TikTok Final auth URL: {$auth_url}", __METHOD__);
//
//        // ✅ ตรวจสอบว่า URL มี app_key หรือไม่
//        if (strpos($auth_url, 'app_key=') === false) {
//            Yii::error("app_key not found in TikTok URL!", __METHOD__);
//            Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาดในการสร้าง URL authorization');
//            return $this->redirect(['site/index']);
//        }
//
//        return $this->redirect($auth_url);
//    }

//    public function actionConnectTiktok()
//    {
//        $appKey = '6h9n461r774e1';
//        $state = Yii::$app->security->generateRandomString(32);
//
//        // เปิด session และบันทึก state
//        Yii::$app->session->open();
//        Yii::$app->session->set('tiktok_oauth_state', $state);
//
//        // Debug: ตรวจสอบค่าที่ใช้
//        Yii::info("TikTok App Key: {$appKey}", __METHOD__);
//        Yii::info("TikTok State: {$state}", __METHOD__);
//
//        // ใช้ parameters ที่ถูกต้องสำหรับ TikTok Shop API
//        $params = [
//            'service_id' => $appKey, // เปลี่ยนจาก app_key เป็น service_id
//            'state' => $state
//            // ไม่ต้องใส่ redirect_uri และ response_type สำหรับ TikTok Shop
//        ];
//
//        // Debug: ตรวจสอบ parameters
//        Yii::info("TikTok Auth parameters: " . json_encode($params), __METHOD__);
//
//        $query_string = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
//        $auth_url = "https://services.tiktokshop.com/open/authorize?{$query_string}";
//
//        // Debug: ตรวจสอบ URL สุดท้าย
//        Yii::info("TikTok Final auth URL: {$auth_url}", __METHOD__);
//
//        // ตรวจสอบว่า URL มี service_id หรือไม่
//        if (strpos($auth_url, 'service_id=') === false) {
//            Yii::error("service_id not found in TikTok URL!", __METHOD__);
//            Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาดในการสร้าง URL authorization');
//            return $this->redirect(['site/index']);
//        }
//
//        // เพิ่มการตรวจสอบความถูกต้องของ App Key
//        if (empty($appKey) || strlen($appKey) < 10) {
//            Yii::error("Invalid TikTok App Key: {$appKey}", __METHOD__);
//            Yii::$app->session->setFlash('error', 'App Key ไม่ถูกต้อง');
//            return $this->redirect(['site/index']);
//        }
//
//        return $this->redirect($auth_url);
//    }

    public function actionConnectTiktok()
    {
        $appKey = '6h9n461r774e1'; // ✅ App Key จาก TikTok Developer Portal
        $redirectUri = Url::to(['site/tiktok-callback'], true); // ต้องตรงกับที่ลงทะเบียนใน TikTok
        $state = Yii::$app->security->generateRandomString(32);

        // เปิด session และบันทึก state
        Yii::$app->session->open();
        Yii::$app->session->set('tiktok_oauth_state', $state);

        // สร้าง parameter ตามเอกสาร TikTok
        $params = [
            'app_key' => $appKey,
            'state' => $state,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri
        ];

        $authUrl = "https://auth.tiktok-shops.com/oauth/authorize?" . http_build_query($params);

        Yii::info("TikTok OAuth URL: {$authUrl}", __METHOD__);

        return $this->redirect($authUrl);
    }


    public function actionTiktokCallback()
    {
        $fullUrl = Yii::$app->request->getAbsoluteUrl();
        Yii::info("TikTok Callback full URL: {$fullUrl}", __METHOD__);

        $allParams = Yii::$app->request->get();
        Yii::info('TikTok All callback parameters: ' . json_encode($allParams), __METHOD__);

        Yii::$app->session->open();

        $code  = Yii::$app->request->get('code');
        $state = Yii::$app->request->get('state');
        $error = Yii::$app->request->get('error');

        if ($error) {
            Yii::$app->session->setFlash('error', 'TikTok authorization error: ' . $error);
            return $this->redirect(['site/index']);
        }

        if (!$code) {
            Yii::$app->session->setFlash('error', 'Missing authorization code from TikTok');
            return $this->redirect(['site/index']);
        }

        // ✅ ตรวจสอบ state
        $sessionState = Yii::$app->session->get('tiktok_oauth_state');
        if ($sessionState && $state && $sessionState !== $state) {
            Yii::$app->session->setFlash('error', 'Invalid state parameter');
            return $this->redirect(['site/index']);
        }
        Yii::$app->session->remove('tiktok_oauth_state');

        $appKey     = '6h9n461r774e1';
        $appSecret  = '1c45a0c25224293abd7de681049f90de3363389a';
        $redirectUri = Url::to(['site/tiktok-callback'], true);

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 30]);

            // ✅ Endpoint TikTok OAuth v2 (มาตรฐาน)
            $endpointOauth = "https://open.tiktokapis.com/v2/oauth/token/";
            // ✅ Endpoint TikTok Shop (บางกรณี)
            $endpointShop = "https://auth.tiktok-shops.com/api/v2/token/get";

            // เตรียมข้อมูลสำหรับ OAuth v2
            $postDataOauth = [
                'client_key'    => $appKey,
                'client_secret' => $appSecret,
                'code'          => $code,
                'grant_type'    => 'authorization_code',
                'redirect_uri'  => $redirectUri,
            ];

            // เตรียมข้อมูลสำหรับ Shop API
            $postDataShop = [
                'app_key'    => $appKey,
                'app_secret' => $appSecret,
                'code'       => $code,
                'grant_type' => 'authorized_code',
            ];

            $response = null;
            $data = null;

            try {
                // 🔹 ลองเรียก OAuth v2 ก่อน
                $response = $client->post($endpointOauth, [
                    'form_params' => $postDataOauth,
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                ]);
                Yii::info("TikTok Used endpoint: {$endpointOauth}", __METHOD__);
            } catch (\Exception $e) {
                Yii::warning("OAuth v2 failed, fallback to Shop API: " . $e->getMessage(), __METHOD__);

                // 🔹 ถ้า fail ให้ลอง Shop API
                $response = $client->post($endpointShop, [
                    'form_params' => $postDataShop,
                    'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                ]);
                Yii::info("TikTok Used endpoint: {$endpointShop}", __METHOD__);
            }

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            Yii::info("TikTok Response status: {$statusCode}", __METHOD__);
            Yii::info("TikTok Response body: {$body}", __METHOD__);

            if ($statusCode !== 200) {
                throw new \Exception("HTTP Error: $statusCode - $body");
            }

            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("JSON decode error: " . json_last_error_msg());
            }

            // ✅ ตรวจสอบรูปแบบ response
            $tokenData = [];
            $shopId = null;

            if (isset($data['data']['access_token'])) {
                // TikTok Shop API response
                $tokenData = [
                    'access_token' => $data['data']['access_token'],
                    'refresh_token' => $data['data']['refresh_token'] ?? '',
                    'access_token_expire_in' => $data['data']['access_token_expire_in'] ?? $data['data']['expires_in'] ?? 86400,
                    'refresh_token_expire_in' => $data['data']['refresh_token_expire_in'] ?? 2592000,
                ];
                $shopId = $data['data']['shop_id'] ?? null;
            } elseif (isset($data['access_token'])) {
                // TikTok OAuth v2 response
                $tokenData = [
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? '',
                    'access_token_expire_in' => $data['expires_in'] ?? 86400,
                    'refresh_token_expire_in' => $data['refresh_expires_in'] ?? 2592000,
                ];
                $shopId = $data['open_id'] ?? null; // อาจใช้ open_id แทน shop_id ในกรณี OAuth v2
            }

            if (!empty($tokenData)) {
                if ($shopId && $this->saveTikTokToken($shopId, $tokenData)) {
                    Yii::$app->session->setFlash('success', 'เชื่อมต่อ TikTok สำเร็จ! Shop/Open ID: ' . $shopId);
                } else {
                    Yii::$app->session->setFlash('warning', 'เชื่อมต่อสำเร็จ แต่ไม่พบ shop_id/open_id ใน response');
                }
            } else {
                $errorMsg = $data['message'] ?? 'Unknown error';
                $errorCode = $data['code'] ?? 'unknown';
                Yii::$app->session->setFlash('error', "ไม่สามารถเชื่อมต่อ TikTok ได้: [$errorCode] $errorMsg");
            }

        } catch (\Exception $e) {
            Yii::error('TikTok callback error: ' . $e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }

        return $this->redirect(['site/index']);
    }



    /**
     * ✅ ฟังก์ชันบันทึก TikTok Token (ปรับให้ตรงกับตารางจริง)
     */
    private function saveTikTokToken($shop_id, $tokenData)
    {
        try {
            $now = date('Y-m-d H:i:s');
            // TikTok อาจใช้ access_token_expire_in หรือ expires_in
            $expireIn = (int)($tokenData['access_token_expire_in'] ?? $tokenData['expires_in'] ?? 86400);
            $expiresAt = date('Y-m-d H:i:s', time() + $expireIn);

            $db = Yii::$app->db;

            // ตรวจสอบว่ามี record อยู่แล้วหรือไม่
            $query = (new \yii\db\Query())
                ->from('tiktok_token')
                ->where(['shop_id' => $shop_id]);

            if ($query->exists()) {
                // อัปเดต token เดิม
                $db->createCommand()->update('tiktok_token', [
                    'access_token'  => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? '',
                    'expire_in'     => $expireIn,
                    'expires_at'    => $expiresAt,
                    'status'        => 'active',
                    'updated_at'    => $now,
                ], ['shop_id' => $shop_id])->execute();

                Yii::info("Updated TikTok token for shop_id: {$shop_id}", __METHOD__);
            } else {
                // เพิ่ม token ใหม่
                $db->createCommand()->insert('tiktok_token', [
                    'shop_id'       => $shop_id,
                    'access_token'  => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? '',
                    'expire_in'     => $expireIn,
                    'expires_at'    => $expiresAt,
                    'status'        => 'active',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ])->execute();

                Yii::info("Inserted new TikTok token for shop_id: {$shop_id}", __METHOD__);
            }

            return true;
        } catch (\Throwable $e) {
            Yii::error('Error saving TikTok token: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * เริ่มต้นการเชื่อมต่อ Shopee OAuth
     */

    public function actionConnectShopee()
    {
        $partner_id = 2012399;
        $partner_key = 'shpk72476151525864414e4b6e475449626679624f695a696162696570417043';
        $redirect_url = 'https://www.pjrichth.co/site/shopee-callback'; // Url::to(['https://www.pjrichth.co/site/shopee-callback'], true);

        $timestamp = time();
        $state = Yii::$app->security->generateRandomString(32);

        // ✅ เปิด session และบันทึก state
        Yii::$app->session->open();
        Yii::$app->session->set('shopee_oauth_state', $state);

        // ✅ Debug: ตรวจสอบค่าที่ใช้
        Yii::info("Partner ID: {$partner_id}", __METHOD__);
        Yii::info("Timestamp: {$timestamp}", __METHOD__);
        Yii::info("State: {$state}", __METHOD__);

        $path = "/api/v2/shop/auth_partner";
        $base_string = $partner_id . $path . $timestamp;
        $sign = hash_hmac('sha256', $base_string, $partner_key);

        // ✅ สร้าง parameters อย่างชัดเจน
        $params = [
            'partner_id' => $partner_id, // ✅ ใช้ค่าจริง
            'redirect'   => $redirect_url,
            'timestamp'  => $timestamp,
            'sign'       => $sign,
            'state'      => $state
        ];

        // ✅ Debug: ตรวจสอบ parameters
        Yii::info("Auth parameters: " . json_encode($params), __METHOD__);

        // ✅ สร้าง URL โดยใช้ http_build_query
        $query_string = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $auth_url = "https://partner.shopeemobile.com{$path}?{$query_string}";

        // ✅ Debug: ตรวจสอบ URL สุดท้าย
        Yii::info("Final auth URL: {$auth_url}", __METHOD__);

        // ✅ ตรวจสอบว่า URL มี partner_id หรือไม่
        if (strpos($auth_url, 'partner_id=') === false) {
            Yii::error("partner_id not found in URL!", __METHOD__);
            Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาดในการสร้าง URL authorization');
            return $this->redirect(['site/index']);
        }

        return $this->redirect($auth_url);
    }


    /**
     * รับ callback จาก Shopee หลังจากผู้ใช้อนุญาต
     */
    public function actionShopeeCallback()
    {
        // Debug URL ที่เข้ามา
        Yii::error("Callback URL: " . $_SERVER['REQUEST_URI'], __METHOD__);
        Yii::error("HTTP Referer: " . ($_SERVER['HTTP_REFERER'] ?? 'none'), __METHOD__);

        Yii::$app->session->open();

        $code = Yii::$app->request->get('code');
        $shop_id = Yii::$app->request->get('shop_id');
        $state = Yii::$app->request->get('state');
        $error = Yii::$app->request->get('error');

        // ✅ Debug: ตรวจสอบ parameters ทั้งหมดที่ได้รับ
        Yii::info('All GET parameters: ' . json_encode($_GET), __METHOD__);

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

        // ✅ ปรับปรุงการตรวจสอบ state (ให้อ่อนลงเล็กน้อย)
        $sessionState = Yii::$app->session->get('shopee_oauth_state');
        Yii::info('Session state: ' . ($sessionState ?: 'NOT_FOUND'), __METHOD__);
        Yii::info('Received state: ' . ($state ?: 'EMPTY'), __METHOD__);

        if ($sessionState && !empty($state) && $sessionState !== $state) {
            Yii::$app->session->setFlash('error', 'Invalid state parameter');
            return $this->redirect(['site/index']);
        }

        // ลบ state จาก session
        Yii::$app->session->remove('shopee_oauth_state');

        $partner_id = 2012399; // ✅ ใช้ค่าเดียวกับใน actionConnectShopee
        $partner_key = 'shpk72476151525864414e4b6e475449626679624f695a696162696570417043'; // ✅ ใส่ partner_key เต็ม
        $redirect_url = 'https://www.pjrichth.co/site/shopee-callback'; // ✅ ใช้ URL เดียวกัน

        $timestamp = time();

        // ✅ สร้าง signature สำหรับ token exchange ตาม Shopee docs ที่ถูกต้อง
        // Format: partner_id + path + timestamp (ไม่รวม code)
        $path = "/api/v2/auth/token/get";
        $base_string = $partner_id . $path . $timestamp;
        $sign = hash_hmac('sha256', $base_string, $partner_key);

        // ✅ Debug signature
        Yii::info("Token exchange base string: {$base_string}", __METHOD__);
        Yii::info("Token exchange signature: {$sign}", __METHOD__);

        try {
            $client = new \GuzzleHttp\Client();

            // ✅ แยก partner_id และ timestamp ไปเป็น query parameters
            $queryParams = [
                'partner_id' => $partner_id,
                'timestamp' => $timestamp,
                'sign' => $sign,
            ];

            $jsonPayload = [
                'code' => $code,
                'shop_id' => $shop_id,
                'partner_id' => $partner_id,
            ];

            // ✅ Debug
            Yii::info("Query params: " . json_encode($queryParams), __METHOD__);
            Yii::info("JSON payload: " . json_encode($jsonPayload), __METHOD__);

            $response = $client->post('https://partner.shopeemobile.com/api/v2/auth/token/get', [
                'query' => $queryParams, // ✅ ส่งเป็น query parameters
                'json' => $jsonPayload,   // ✅ ส่งเป็น JSON body (ไม่ใช่ form_params)
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'slapsis/1.0',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            Yii::info("Response status: {$statusCode}", __METHOD__);
            Yii::info("Response body: {$body}", __METHOD__);

            // ✅ ตรวจสอบ response headers
            $headers = $response->getHeaders();
            Yii::info("Response headers: " . json_encode($headers), __METHOD__);

            if ($statusCode !== 200) {
                throw new \Exception("HTTP Error inside try: $statusCode - $body");
            }

            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("JSON decode error: " . json_last_error_msg());
            }

            // ✅ ตรวจสอบว่า response มี error หรือไม่
            if (isset($data['error'])) {
                if(!empty($data['error'])){
                    $errorDetail = '';
                    if(isset($data['message'])){
                        $errorDetail = !empty($data['message']) ? $data['message'] : 'No error message';
                    }
                    throw new \Exception("API Error: {$data['error']} - {$errorDetail}");
                }

            }

            if (isset($data['access_token'])) {
                // บันทึกลงฐานข้อมูล
                if(!empty($data['access_token'])){
                    $this->saveShopeeToken($shop_id, $data);
                    Yii::$app->session->setFlash('success', 'เชื่อมต่อ Shopee สำเร็จ! Shop ID: ' . $shop_id);
                }else{
                    Yii::$app->session->setFlash('error', 'พบ access token เป็นค่าว่าง: ' . $shop_id);
                }

            } else {
                $errorMsg = isset($data['message']) ? $data['message'] : 'Unknown error';
                $errorCode = isset($data['error']) ? $data['error'] : 'unknown';

                // ✅ Debug ข้อมูลที่ได้รับทั้งหมด
                Yii::error("Complete API response: " . json_encode($data), __METHOD__);
                Yii::error("Query params sent: " . json_encode($queryParams), __METHOD__);
                Yii::error("JSON payload sent: " . json_encode($jsonPayload), __METHOD__);
                Yii::error("Base string used: {$base_string}", __METHOD__);
                Yii::error("Signature used: {$sign}", __METHOD__);

                Yii::$app->session->setFlash('error', "ไม่สามารถเชื่อมต่อ Shopee ได้: [$errorCode] $errorMsg");
            }

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $errorBody = $response->getBody()->getContents();

            Yii::error("HTTP Status: {$statusCode}", __METHOD__);
            Yii::error("Error Body: {$errorBody}", __METHOD__);
            Yii::error("Request URL: https://partner.shopeemobile.com/api/v2/auth/token/get", __METHOD__);

            Yii::$app->session->setFlash('error', "HTTP Error {$statusCode}: {$errorBody}");

        } catch (\Exception $e) {
            Yii::error('Shopee callback error: ' . $e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }

        return $this->redirect(['site/index']);
    }

    public function actionTestWithRealCode()
    {
        $code = '4a466f51734c697a6e654b4857757646'; // code จาก log
        $shop_id = '816547426'; // shop_id จาก log
        $partner_id = 2012399; // ✅ ใช้ค่าเดียวกับใน actionConnectShopee
        $partner_key = 'shpk72476151525864414e4b6e475449626679624f695a696162696570417043'; // ✅ ใส่ partner_key เต็ม

        $timestamp = time();

        // ✅ สร้าง signature สำหรับ token exchange ตาม Shopee docs ที่ถูกต้อง
        // Format: partner_id + path + timestamp (ไม่รวม code)
        $path = "/api/v2/auth/token/get";
        $base_string = $partner_id . $path . $timestamp;
        $sign = hash_hmac('sha256', $base_string, $partner_key);
        // ใช้โค้ดเดียวกับใน callback เพื่อทดสอบ
        // ... (คัดลอกส่วน API call จาก callback)
        try {
            $client = new \GuzzleHttp\Client();

            // ✅ แยก partner_id และ timestamp ไปเป็น query parameters
            $queryParams = [
                'partner_id' => $partner_id,
                'timestamp' => $timestamp,
                'sign' => $sign,
            ];

            $jsonPayload = [
                'code' => $code,
                'shop_id' => $shop_id,
                'partner_id' => $partner_id,
            ];

            // ✅ Debug
            Yii::info("Query params: " . json_encode($queryParams), __METHOD__);
            Yii::info("JSON payload: " . json_encode($jsonPayload), __METHOD__);

            $response = $client->post('https://partner.shopeemobile.com/api/v2/auth/token/get', [
                'query' => $queryParams, // ✅ ส่งเป็น query parameters
                'json' => $jsonPayload,   // ✅ ส่งเป็น JSON body (ไม่ใช่ form_params)
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'YourApp/1.0',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            Yii::info("Response status: {$statusCode}", __METHOD__);
            Yii::info("Response body: {$body}", __METHOD__);

            // ✅ ตรวจสอบ response headers
            $headers = $response->getHeaders();
            Yii::info("Response headers: " . json_encode($headers), __METHOD__);

            if ($statusCode !== 200) {
                throw new \Exception("HTTP Error: $statusCode - $body");
            }

            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("JSON decode error: " . json_last_error_msg());
            }

            // ✅ ตรวจสอบว่า response มี error หรือไม่
            if (isset($data['error'])) {
                $errorDetail = isset($data['message']) ? $data['message'] : 'No error message';
                throw new \Exception("API Error: {$data['error']} - {$errorDetail}");
            }

            if (isset($data['access_token'])) {
                // บันทึกลงฐานข้อมูล
                $this->saveShopeeToken($shop_id, $data);

                Yii::$app->session->setFlash('success', 'เชื่อมต่อ Shopee สำเร็จ! Shop ID: ' . $shop_id);
            } else {
                $errorMsg = isset($data['message']) ? $data['message'] : 'Unknown error';
                $errorCode = isset($data['error']) ? $data['error'] : 'unknown';

                // ✅ Debug ข้อมูลที่ได้รับทั้งหมด
                Yii::error("Complete API response: " . json_encode($data), __METHOD__);
                Yii::error("Query params sent: " . json_encode($queryParams), __METHOD__);
                Yii::error("JSON payload sent: " . json_encode($jsonPayload), __METHOD__);
                Yii::error("Base string used: {$base_string}", __METHOD__);
                Yii::error("Signature used: {$sign}", __METHOD__);

                Yii::$app->session->setFlash('error', "ไม่สามารถเชื่อมต่อ Shopee ได้: [$errorCode] $errorMsg");
            }

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $errorBody = $response->getBody()->getContents();

            Yii::error("HTTP Status: {$statusCode}", __METHOD__);
            Yii::error("Error Body: {$errorBody}", __METHOD__);
            Yii::error("Request URL: https://partner.shopeemobile.com/api/v2/auth/token/get", __METHOD__);

            Yii::$app->session->setFlash('error', "HTTP Error {$statusCode}: {$errorBody}");

        } catch (\Exception $e) {
            Yii::error('Shopee callback error: ' . $e->getMessage(), __METHOD__);
            Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }

        return $this->redirect(['site/index']);
    }

    /**
     * ฟังก์ชันสำหรับบันทึก Shopee Token (ปรับให้ตรงกับตารางจริง)
     */
    private function saveShopeeToken($shop_id, $tokenData)
    {
        try {
            //$now = date('Y-m-d H:i:s');
            $expiresAt = date('Y-m-d H:i:s', time() + (int)($tokenData['expire_in'] ?? 14400));

            $db = Yii::$app->db;

            // ตรวจสอบว่ามี record อยู่แล้วหรือไม่
            $query = (new \yii\db\Query())
                ->from('shopee_tokens')
                ->where(['shop_id' => $shop_id]);

            if ($query->exists()) {
                // อัปเดต token เดิม
                $db->createCommand()->update('shopee_tokens', [
                    'access_token'  => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? '',
                    'expire_in'     => (int)($tokenData['expire_in'] ?? 14400),
                    'expires_at'    => $expiresAt,
                    'status'        => 'active', // เพิ่มสถานะ active
                    'updated_at'    => date('Y-m-d H:i:s'),
                ], ['shop_id' => $shop_id])->execute();

                Yii::info("Updated Shopee token for shop_id: {$shop_id}", __METHOD__);
            } else {
                // เพิ่ม token ใหม่
                $db->createCommand()->insert('shopee_tokens', [
                    'shop_id'       => $shop_id,
                    'access_token'  => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? '',
                    'expire_in'     => (int)($tokenData['expire_in'] ?? 14400),
                    'expires_at'    => $expiresAt,
                    'status'        => 'active',
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s'),
                ])->execute();

                Yii::info("Inserted new Shopee token for shop_id: {$shop_id}", __METHOD__);
            }

            return true;
        } catch (\Throwable $e) {
            Yii::error('Error saving Shopee token: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * ฟังก์ชันสำหรับบันทึก TikTok Token (สร้างใหม่ให้ตรงกับ pattern เดียวกัน)
     */





    public function actionTestShopeeSignature()
    {
        $partner_id = 2012399;
        $partner_key = 'shpk72476151525864414e4b6e475449626679624f695a696162696570417043';
        $redirect_url = 'https://www.pjrichth.co/site/shopee-callback';
        $code = 'sample-code';
        $timestamp = 1672531200; // ใช้ timestamp คงที่เพื่อทดสอบ

        // ทดสอบ signature สำหรับ authorization
        $auth_path = "/api/v2/shop/auth_partner";
        $auth_base_string = $partner_id . $auth_path . $timestamp;
        $auth_sign = hash_hmac('sha256', $auth_base_string, $partner_key);

        // ทดสอบ signature สำหรับ token exchange
        $token_base_string = $partner_id . $redirect_url . $timestamp . $code;
        $token_sign = hash_hmac('sha256', $token_base_string, $partner_key);

        return $this->asJson([
            'partner_id' => $partner_id,
            'partner_key_length' => strlen($partner_key),
            'partner_key_preview' => substr($partner_key, 0, 10) . '...',
            'redirect_url' => $redirect_url,
            'code' => $code,
            'timestamp' => $timestamp,
            'auth' => [
                'path' => $auth_path,
                'base_string' => $auth_base_string,
                'signature' => $auth_sign,
            ],
            'token_exchange' => [
                'base_string' => $token_base_string,
                'signature' => $token_sign,
            ]
        ]);
    }

    /**
     * ✅ ฟังก์ชันทดสอบการส่ง token exchange request
     */
//    public function actionTestShopeeTokenExchange()
//    {
////        // ใช้ข้อมูลจริง
//        $partner_id = 2012399; // ✅ ใช้ partner_id จริง
//        $partner_key = 'shpk72476151525864414e4b6e475449626679624f695a696162696570417043'; // ✅ ใส่ partner_key เต็ม
//        $redirect_url = 'https://www.pjrichth.co/site/shopee-callback';
//  //      $code = 'test-code'; // ใส่ code จริงจากการทดสอบ
////        $timestamp = time();
////
////        // ✅ สร้าง signature แบบถูกต้อง (รวม path)
////        $path = "/api/v2/auth/token/get";
////        $base_string = $partner_id . $path . $timestamp . $code;
////        $sign = hash_hmac('sha256', $base_string, $partner_key);
////
////        // ✅ แยก parameters ตาม Shopee API format
////        $queryParams = [
////            'partner_id' => $partner_id,
////            'timestamp' => $timestamp,
////            'sign' => $sign,
////        ];
////
////        $postData = [
////            'code' => $code,
////            'redirect_uri' => $redirect_url,
////        ];
////
////        try {
////            $client = new \GuzzleHttp\Client();
////
////            // ✅ ทดสอบส่ง request แบบใหม่
////            $response = $client->post('https://partner.shopeemobile.com/api/v2/auth/token/get', [
////                'query' => $queryParams, // ✅ ส่งเป็น query parameters
////                'form_params' => $postData, // ✅ ส่งเป็น POST body
////                'timeout' => 30,
////                'debug' => false, // เปลี่ยนเป็น true เพื่อดู detail
////            ]);
////
////            $statusCode = $response->getStatusCode();
////            $body = $response->getBody()->getContents();
////
////            return $this->asJson([
////                'success' => true,
////                'status_code' => $statusCode,
////                'query_params' => $queryParams,
////                'post_data' => $postData,
////                'base_string' => $base_string,
////                'signature' => $sign,
////                'response_body' => json_decode($body, true),
////                'response_raw' => $body,
////            ]);
////
////        } catch (\GuzzleHttp\Exception\ClientException $e) {
////            // ✅ จับ error จาก HTTP client
////            $response = $e->getResponse();
////            $statusCode = $response->getStatusCode();
////            $body = $response->getBody()->getContents();
////
////            return $this->asJson([
////                'success' => false,
////                'error_type' => 'ClientException',
////                'status_code' => $statusCode,
////                'query_params' => $queryParams,
////                'post_data' => $postData,
////                'base_string' => $base_string,
////                'signature' => $sign,
////                'error_body' => $body,
////                'error_decoded' => json_decode($body, true),
////            ]);
////
////        } catch (\Exception $e) {
////            return $this->asJson([
////                'success' => false,
////                'error_type' => 'Exception',
////                'error_message' => $e->getMessage(),
////                'query_params' => $queryParams,
////                'post_data' => $postData,
////                'base_string' => $base_string,
////                'signature' => $sign,
////            ]);
////        }
//        $code = 'sample-code';
//        $timestamp = 1672531200; // ใช้ timestamp คงที่เพื่อทดสอบ
//
//        // ทดสอบ signature สำหรับ authorization
//        $auth_path = "/api/v2/shop/auth_partner";
//        $auth_base_string = $partner_id . $auth_path . $timestamp;
//        $auth_sign = hash_hmac('sha256', $auth_base_string, $partner_key);
//
//        // ทดสอบ signature สำหรับ token exchange (รวม path)
//        $token_path = "/api/v2/auth/token/get";
//        $token_base_string = $partner_id . $token_path . $timestamp . $code;
//        $token_sign = hash_hmac('sha256', $token_base_string, $partner_key);
//
//        return $this->asJson([
//            'partner_id' => $partner_id,
//            'partner_key_length' => strlen($partner_key),
//            'partner_key_preview' => substr($partner_key, 0, 10) . '...',
//            'redirect_url' => $redirect_url,
//            'code' => $code,
//            'timestamp' => $timestamp,
//            'auth' => [
//                'path' => $auth_path,
//                'base_string' => $auth_base_string,
//                'signature' => $auth_sign,
//            ],
//            'token_exchange' => [
//                'path' => $token_path,
//                'base_string' => $token_base_string,
//                'signature' => $token_sign,
//            ]
//        ]);
//    }

    public function actionTestShopeeTokenExchange()
    {
        $partner_id = 2012399;
        $partner_key = 'shpk72476151525864414e4b6e475449626679624f695a696162696570417043';
        $redirect_url = 'https://www.pjrichth.co/site/shopee-callback';
        $code = 'sample-code';
        $timestamp = 1672531200;

        // Authorization signature (ถูกต้อง)
        $auth_path = "/api/v2/shop/auth_partner";
        $auth_base_string = $partner_id . $auth_path . $timestamp;
        $auth_sign = hash_hmac('sha256', $auth_base_string, $partner_key);

        // Token exchange signature (แก้ไขแล้ว - ไม่รวม code)
        $token_path = "/api/v2/auth/token/get";
        $token_base_string = $partner_id . $token_path . $timestamp; // ✅ ลบ $code ออก
        $token_sign = hash_hmac('sha256', $token_base_string, $partner_key);

        return $this->asJson([
            'partner_id' => $partner_id,
            'partner_key_length' => strlen($partner_key),
            'partner_key_preview' => substr($partner_key, 0, 10) . '...',
            'redirect_url' => $redirect_url,
            'code' => $code,
            'timestamp' => $timestamp,
            'auth' => [
                'path' => $auth_path,
                'base_string' => $auth_base_string,
                'signature' => $auth_sign,
            ],
            'token_exchange' => [
                'path' => $token_path,
                'base_string' => $token_base_string,
                'signature' => $token_sign,
                'note' => 'Code is sent in JSON payload, not in signature'
            ]
        ]);
    }

}
