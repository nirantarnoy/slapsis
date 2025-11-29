<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
require __DIR__ . '/../config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../../common/config/main-local.php',
    require __DIR__ . '/../config/main.php',
    require __DIR__ . '/../config/main-local.php'
);

$application = new yii\web\Application($config);

use backend\models\TiktokIncomeDetails;
use backend\models\Order;

echo "Checking TiktokIncomeDetails...\n";
$total = TiktokIncomeDetails::find()->count();
$nullDates = TiktokIncomeDetails::find()->where(['order_date' => null])->count();

echo "Total Records: $total\n";
echo "Records with NULL order_date: $nullDates\n";

if ($nullDates > 0) {
    echo "Attempting to find matching orders for NULL dates...\n";
    $nullRecords = TiktokIncomeDetails::find()->where(['order_date' => null])->limit(5)->all();
    foreach ($nullRecords as $record) {
        echo "Order ID: " . $record->order_id . "\n";
        $order = Order::find()
            ->where(['or', 
                ['order_id' => $record->order_id],
                ['like', 'order_id', $record->order_id . '_%', false]
            ])
            ->one();
        
        if ($order) {
            echo "  Found match in Order table! Date: " . $order->order_date . "\n";
        } else {
            echo "  No match found in Order table.\n";
        }
    }
}
