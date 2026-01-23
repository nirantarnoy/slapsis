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

$table = Yii::$app->db->getTableSchema('order');
if ($table) {
    echo "Table 'order' columns:\n";
    foreach ($table->columns as $column) {
        echo "- {$column->name} ({$column->type}) " . ($column->allowNull ? "NULL" : "NOT NULL") . "\n";
    }
} else {
    echo "Table 'order' not found.\n";
}
