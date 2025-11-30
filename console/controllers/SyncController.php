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
            $orderService = new OrderSyncService();
            $result = $orderService->syncOrders();
            echo "Orders Synced. Total: " . $result['count'] . "\n";
            if (!empty($result['errors'])) {
                echo "Errors: " . implode(", ", $result['errors']) . "\n";
            }
        } catch (\Exception $e) {
            echo "Error Syncing Orders: " . $e->getMessage() . "\n";
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
