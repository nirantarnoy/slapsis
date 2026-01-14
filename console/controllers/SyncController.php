<?php

namespace console\controllers;

use yii\console\Controller;
use backend\services\OrderSyncService;
use backend\services\TiktokIncomeService;
use backend\services\ShopeeIncomeService;
use Yii;

class SyncController extends Controller
{
    /**
     * Sync orders and income for all channels
     */
    public function actionIndex()
    {
        echo "Starting Sync Process...\n";

        // 1. Sync Orders
        echo "Syncing Orders...\n";
        try {
            $totalSynced = 0;
            $errors = [];

            // 1.1 Sync TikTok (Original Service)
            $tiktokChannel = \backend\models\OnlineChannel::find()->where(['name' => 'Tiktok', 'status' => \backend\models\OnlineChannel::STATUS_ACTIVE])->one();
            if ($tiktokChannel) {
                echo "Syncing TikTok Orders...\n";
                $orderService = new OrderSyncService();
                $res = $orderService->syncOrders($tiktokChannel->id);
                $totalSynced += $res['count'];
                if (!empty($res['errors'])) {
                    $errors = array_merge($errors, $res['errors']);
                }
                echo "TikTok Orders Synced. Count: " . $res['count'] . "\n";
            }

            // 1.2 Sync Shopee (New Improved Service)
            $shopeeChannel = \backend\models\OnlineChannel::find()->where(['name' => 'Shopee', 'status' => \backend\models\OnlineChannel::STATUS_ACTIVE])->one();
            if ($shopeeChannel) {
                echo "Syncing Shopee Orders (New Logic)...\n";
                $newShopeeService = new \backend\services\NewTestOrderSyncService();
                $shopeeCount = $newShopeeService->syncShopeeOrders($shopeeChannel);
                $totalSynced += $shopeeCount;
                echo "Shopee Orders Synced. Count: " . $shopeeCount . "\n";
            }

            echo "Total Orders Synced: " . $totalSynced . "\n";
            if (!empty($errors)) {
                echo "Errors: " . implode(", ", $errors) . "\n";
            }
        } catch (\Exception $e) {
            echo "Error Syncing Orders: " . $e->getMessage() . "\n";
            Yii::error("Console Sync Error: " . $e->getMessage(), __METHOD__);
        }


        // 2. Sync TikTok Income
        echo "Syncing TikTok Income...\n";
        try {
            $tiktokService = new TiktokIncomeService();
            $count = $tiktokService->syncAllOrders();
            echo "TikTok Income Synced. Count: " . $count . "\n";
        } catch (\Exception $e) {
            echo "Error Syncing TikTok Income: " . $e->getMessage() . "\n";
        }

        // 3. Sync Shopee Income
        echo "Syncing Shopee Income...\n";
        try {
            $shopeeService = new ShopeeIncomeService();
            $count = $shopeeService->syncAllOrders();
            echo "Shopee Income Synced. Count: " . $count . "\n";
        } catch (\Exception $e) {
            echo "Error Syncing Shopee Income: " . $e->getMessage() . "\n";
        }

        echo "Sync Process Completed.\n";
    }
}
