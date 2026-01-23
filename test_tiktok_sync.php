<?php
define('YII_DEBUG', true);
define('YII_ENV', 'dev');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/common/config/bootstrap.php';
require __DIR__ . '/console/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/common/config/main.php',
    require __DIR__ . '/common/config/main-local.php',
    require __DIR__ . '/console/config/main.php',
    require __DIR__ . '/console/config/main-local.php'
);

$application = new yii\console\Application($config);

use backend\services\OrderSyncService;
use backend\models\OnlineChannel;

$tiktokChannel = OnlineChannel::find()->where(['name' => 'Tiktok'])->one();
if (!$tiktokChannel) {
    echo "TikTok channel not found!\n";
    exit;
}

echo "Starting TikTok Sync Debug...\n";
$service = new OrderSyncService();
$res = $service->syncOrders($tiktokChannel->id);

echo "Sync Result:\n";
print_r($res);
