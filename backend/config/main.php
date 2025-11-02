<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'modules' => [
        'gridview' => [
            'class' => '\kartik\grid\Module'
        ],
//        'api' => [
//            'class' => 'backend\modules\api\Api',
//            // 'basePath' => '@backend/modules/api',
//            // 'class' => 'backend\modules\api\Module',
//        ],
    ],
    'timeZone' => 'Asia/Bangkok',
//    'aliases'=>[
//        '@adminlte3' => '@backend/theme/AdminLTE-3.0.1',
//    ],
    'aliases' => [
        '@frontendWeb' => '@frontend/web',
    ],
    'components' => [
        'assetManager' => [
            'basePath' => '@webroot/assets',
            'baseUrl'  => '@web/assets',
            'bundles' => [
                'kartik\form\ActiveFormAsset' => [
                    'bsDependencyEnabled' => true // do not load bootstrap assets for a specific asset bundle
                ],
            ],
        ],
        'view' => [
            'theme' => [
                'pathMap' => [
                    '@app/views' => '@backend/theme/views'
                ],
                'basePath' => '@backend/theme/web',
                'baseUrl'  => '@web/theme',
            ],
        ],
//        'view' => [
//            'theme' => [
//                'pathMap' => [
//                    '@backend/views' => '@adminlte3/views'
//                ],
//            ],
//        ],

//        'request' => [
//            'csrfParam' => '_csrf-backend',
//            'enableCsrfValidation' => false,
//        ],
        'request' => [
            'csrfParam' => '_csrf-backend',
            'class' => 'yii\web\Request',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
            //'baseUrl' => '/backend/web',
            //'baseUrl' => '/backend/web', // ชี้ path ที่แท้จริงของเว็บ
          //  'baseUrl' => '/slapsis/backend/web',
            'baseUrl' => '/slapsis/backend/web',

//            'parsers' => [
//                'application/json'=> \yii\web\JsonParser::class,
//            ]
        ],
//        [
//            'session' =>[
//              //  'timeout' => 86400,
//                'timeout' => 60*60*24*14,
//            ]
//        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-backend', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the backend

//            'name' => 'advanced-backend',
//            'timeout' => 60*60*24*30,

            'class' => 'yii\web\Session',
            'name' => 'advanced-backend',
            'cookieParams' => ['lifetime' => 7 * 24 * 60 * 60],
            // 'cookieParams' => ['httpOnly'=>true],
            'timeout' => 60 * 60 * 24 * 30,
            'useCookies' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info', 'trace'], // เพิ่ม trace
                    'logVars' => [],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
//        'urlManagerFrontend' => [
//            'class' => 'yii\web\urlManager',
//            'baseUrl' => '/icesystem/frontend/web',
//            'scriptUrl'=>'/icesystem/frontend/web/index.php',
////            'baseUrl' => 'http://app.sst.ac.th',
////            'scriptUrl' => 'http://app.sst.ac.th/index.php',
////            'baseUrl' => 'http://app.sst.ac.th',
////            'scriptUrl' => 'http://app.sst.ac.th/index.php',
//            'enablePrettyUrl' => false,
//            'showScriptName' => true,
//        ],
//        'urlManager' => [
//            'enablePrettyUrl' => false,
//            'showScriptName' => false,
//            'enableStrictParsing' => false,
//            'rules' => [
//                '' => 'site/index',
//                'order/export-excel' => 'order/export-excel',
//                'order/export-pdf' => 'order/export-pdf',
//                'order/sync-orders' => 'order/sync-orders',
//                // ตัวอย่างการตั้ง rules เพิ่มเติม
//                '<controller:\w+>/<id:\d+>' => '<controller>/view',
//                '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
//                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
//            ],
//        ],
//        'urlManager' => [
//            'enablePrettyUrl' => true,
//            'showScriptName' => false,
//            'enableStrictParsing' => false,
//            'rules' => [
//                // กำหนด rule สำหรับ root URL
//                '' => 'site/login',
//                '/' => 'site/login',
//
//                // URL rules อื่นๆ
//                '<controller:\w+>/<id:\d+>' => '<controller>/view',
//                '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
//                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
//            ],
//        ],

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'suffix' => '',
            'rules' => [
                // กำหนด rule สำหรับ root URL
                '' => 'site/login',

                // URL rules เฉพาะเจาะจง (ต้องมาก่อน generic rules)
                'login' => 'site/login',
                'logout' => 'site/logout',

                // Generic rules (เรียงจากเฉพาะเจาะจงไปทั่วไป)
                '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
                '<controller:\w+>/<id:\d+>' => '<controller>/view',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
                '<controller:\w+>' => '<controller>/index',

                // Catch-all rule สำหรับ patterns ที่ไม่ตรงกับ rules ข้างต้น
//                '<module:\w+>/<controller:\w+>/<action:\w+>' => '<module>/<controller>/<action>',
            ],
        ],

        'formatter' => [
            'class' => 'yii\i18n\Formatter',
            'defaultTimeZone' => 'Asia/Bangkok',
            'timeZone' => 'Asia/Bangkok',
            'dateFormat' => 'php:d/m/Y',
            'datetimeFormat' => 'php:d/m/Y H:i:s',
            'decimalSeparator' => '.',
            'thousandSeparator' => ',',
            'currencyCode' => 'THB',
        ],
        // API Components (uncomment and configure when ready)
        /*
        'tiktokApi' => [
            'class' => 'backend\components\TikTokShopApi',
            'appKey' => 'your-app-key',
            'appSecret' => 'your-app-secret',
            'accessToken' => 'your-access-token',
            'shopId' => 'your-shop-id',
        ],
        'shopeeApi' => [
            'class' => 'backend\components\ShopeeApi',
            'partnerId' => 'your-partner-id',
            'partnerKey' => 'your-partner-key',
            'accessToken' => 'your-access-token',
            'shopId' => 'your-shop-id',
        ],
        */
        /*
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],
        */
    ],
    'params' => $params,
];
