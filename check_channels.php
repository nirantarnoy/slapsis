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

use backend\models\OnlineChannel;

$channels = OnlineChannel::find()->all();
echo "Channels in database:\n";
foreach ($channels as $c) {
    echo "ID: {$c->id}, Name: '{$c->name}', Status: " . var_export($c->status, true) . "\n";
}
