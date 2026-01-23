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

use backend\models\ShopeeToken;
use backend\services\NewTestOrderSyncService;
use backend\models\OnlineChannel;

$tokens = ShopeeToken::find()->all();
if (empty($tokens)) {
    echo "No tokens found in shopee_token table at all!\n";
} else {
    echo "Found " . count($tokens) . " tokens:\n";
    foreach ($tokens as $t) {
        echo "ID: {$t->id}, Shop ID: {$t->shop_id}, Status: {$t->status}, Expires At: {$t->expires_at}\n";
    }
}

$tokenModel = ShopeeToken::find()
    ->where(['status' => 'active'])
    ->orderBy(['created_at' => SORT_DESC])
    ->one();

if (!$tokenModel) {
    echo "No active Shopee token found!\n";
    exit;
}

echo "Active Token Found:\n";
echo "Shop ID: " . $tokenModel->shop_id . "\n";
echo "Expires At: " . $tokenModel->expires_at . "\n";
echo "Current Time: " . date('Y-m-d H:i:s') . "\n";

$shopeeChannel = OnlineChannel::find()->where(['name' => 'Shopee'])->one();
if (!$shopeeChannel) {
    echo "Shopee Channel not found in online_channel table!\n";
    exit;
}

$service = new NewTestOrderSyncService();
echo "Starting sync...\n";
try {
    $count = $service->syncShopeeOrders($shopeeChannel);
    echo "Sync completed. Count: " . $count . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
