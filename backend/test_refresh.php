<?php
require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../common/config/bootstrap.php');
// require(__DIR__ . '/bootstrap.php'); // Removed as it doesn't exist

$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/../common/config/main.php'),
    require(__DIR__ . '/../common/config/main-local.php'),
    require(__DIR__ . '/config/main.php'),
    require(__DIR__ . '/config/main-local.php')
);

(new yii\web\Application($config));

use backend\models\ShopeeToken;
use backend\services\NewTestSyncShopeeService;

$token = ShopeeToken::find()->where(['status' => 'active'])->orderBy(['created_at' => SORT_DESC])->one();

if (!$token) {
    echo "No active Shopee token found.\n";
    exit;
}

echo "Found token for shop_id: " . $token->shop_id . "\n";
echo "Expires at: " . $token->expires_at . "\n";

$service = new NewTestSyncShopeeService();

// We can't easily call private method refreshShopeeToken directly.
// But we can call syncShopeeTransactionFeesV2 which calls it if expired.
// Or we can use Reflection to call it.

$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('refreshShopeeToken');
$method->setAccessible(true);

echo "Attempting to refresh token...\n";
$result = $method->invoke($service, $token);

if ($result) {
    echo "Token refreshed successfully!\n";
    $newToken = ShopeeToken::findOne($token->id);
    echo "New expires at: " . $newToken->expires_at . "\n";
} else {
    echo "Failed to refresh token. Check logs.\n";
}
