<?php
define('YII_DEBUG', true);
define('YII_ENV', 'dev');

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/common/config/bootstrap.php');
require(__DIR__ . '/console/config/bootstrap.php');

$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/common/config/main.php'),
    require(__DIR__ . '/common/config/main-local.php'),
    require(__DIR__ . '/console/config/main.php'),
    require(__DIR__ . '/console/config/main-local.php')
);

$application = new yii\console\Application($config);

use backend\models\ShopeeToken;
use backend\services\NewTestOrderSyncService;
use backend\models\OnlineChannel;

$service = new NewTestOrderSyncService();
$channel = OnlineChannel::find()->where(['name' => 'Shopee'])->one();

if (!$channel) {
    echo "Shopee channel not found\n";
    exit;
}

echo "Testing Shopee Sync...\n";
try {
    $count = $service->syncShopeeOrders($channel);
    echo "Sync completed. Total synced: $count\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
