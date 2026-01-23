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

use backend\models\TiktokToken;

$tokens = TiktokToken::find()->all();
if (empty($tokens)) {
    echo "No TikTok tokens found!\n";
} else {
    echo "Found " . count($tokens) . " TikTok tokens:\n";
    foreach ($tokens as $t) {
        echo "ID: {$t->id}, Shop ID: {$t->shop_id}, Status: {$t->status}\n";
    }
}
