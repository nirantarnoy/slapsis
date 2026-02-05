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
    /**
     * Sync everything (TikTok & Shopee Orders + Income)
     * Usage: php yii sync
     */
    public function actionIndex($days = 7)
    {
        echo "Starting Full Sync Process (Last $days days)...\n";

        // 1. Sync All Orders
        $orderService = new OrderSyncService();
        $res = $orderService->syncOrders(null, $days);
        
        echo "\n--- Order Sync Summary ---\n";
        echo "Total Records: " . $res['count'] . "\n";
        if (!empty($res['errors'])) {
            echo "Errors: " . implode(", ", $res['errors']) . "\n";
        }

        // 2. Sync TikTok Income
        echo "\nSyncing TikTok Income...\n";
        try {
            $tiktokService = new TiktokIncomeService();
            $count = $tiktokService->syncAllOrders();
            echo "TikTok Income Synced. Count: " . $count . "\n";
        } catch (\Exception $e) {
            echo "Error Syncing TikTok Income: " . $e->getMessage() . "\n";
        }

        // 3. Sync Shopee Income
        echo "\nSyncing Shopee Income...\n";
        try {
            $shopeeService = new \backend\services\ShopeeIncomeService();
            $count = $shopeeService->syncAllOrders();
            echo "Shopee Income Synced. Count: " . $count . "\n";
        } catch (\Exception $e) {
            echo "Error Syncing Shopee Income: " . $e->getMessage() . "\n";
        }

        echo "\nFull Sync Process Completed.\n";
    }

    /**
     * Sync TikTok orders only
     * Usage: php yii sync/tiktok-orders [days] [refresh]
     */
    public function actionTiktokOrders($days = 7, $refresh = 0)
    {
        echo "Starting TikTok Order Sync (Last $days days)...\n";
        $tiktokChannel = \backend\models\OnlineChannel::find()->where(['name' => 'Tiktok', 'status' => \backend\models\OnlineChannel::STATUS_ACTIVE])->one();
        
        if ($tiktokChannel) {
            // Check Token Status explicitly
            $token = \backend\models\TiktokToken::find()->orderBy(['created_at' => SORT_DESC])->one();
            if ($token) {
                echo "Token Found: " . substr($token->access_token, 0, 10) . "...\n";
                echo "Expires At: " . $token->expires_at . " (Now: " . date('Y-m-d H:i:s') . ")\n";
                if (strtotime($token->expires_at) < time()) {
                    echo "Status: EXPIRED! Attempting to refresh inside service...\n";
                } else {
                    echo "Status: ACTIVE\n";
                    if ($refresh) echo "Force Refresh requested...\n";
                }
            } else {
                echo "Status: NO TOKEN FOUND in tiktok_token table!\n";
            }

            $orderService = new OrderSyncService();
            $res = $orderService->syncOrders($tiktokChannel->id, $days, $refresh);
            echo "TikTok Orders Sync completed.\n";
            echo "Total records: " . $res['count'] . "\n";
            if (!empty($res['errors'])) {
                echo "Errors: " . implode(", ", $res['errors']) . "\n";
            }
        } else {
            echo "Error: TikTok Channel not found or inactive.\n";
        }
    }

    /**
     * Sync TikTok income only
     * Usage: php yii sync/tiktok-income
     */
    public function actionTiktokIncome()
    {
        echo "Starting TikTok Income Sync...\n";
        try {
            $tiktokService = new TiktokIncomeService();
            $count = $tiktokService->syncAllOrders();
            echo "TikTok Income Synced. Count: " . $count . "\n";
        } catch (\Exception $e) {
            echo "Error Syncing TikTok Income: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Sync Shopee orders only
     * Usage: php yii sync/shopee-orders [days] [refresh]
     */
    public function actionShopeeOrders($days = 15, $refresh = 0)
    {
        echo "Starting Shopee Order Sync (Last $days days)...\n";
        $shopeeChannel = \backend\models\OnlineChannel::find()->where(['name' => 'Shopee', 'status' => \backend\models\OnlineChannel::STATUS_ACTIVE])->one();
        
        if ($shopeeChannel) {
            $token = \backend\models\ShopeeToken::find()->orderBy(['created_at' => SORT_DESC])->one();
            if ($token) {
                echo "Token Found für Shop ID: " . $token->shop_id . "\n";
                echo "Expires At: " . $token->expires_at . " (Now: " . date('Y-m-d H:i:s') . ")\n";
                if (strtotime($token->expires_at) < time()) {
                    echo "Status: EXPIRED! Attempting to refresh inside service...\n";
                } else {
                    echo "Status: ACTIVE\n";
                    if ($refresh) echo "Force Refresh requested...\n";
                }
            } else {
                echo "Status: NO TOKEN FOUND in shopee_token table!\n";
            }

            $orderService = new \backend\services\OrderSyncService();
            $res = $orderService->syncOrders($shopeeChannel->id, $days, $refresh);
            echo "Shopee Orders Sync completed.\n";
            echo "Total records: " . $res['count'] . "\n";
            if (!empty($res['errors'])) {
                echo "Errors: " . implode(", ", $res['errors']) . "\n";
            }
        } else {
            echo "Error: Shopee Channel not found or inactive.\n";
        }
    }

    /**
     * Sync Shopee income only
     * Usage: php yii sync/shopee-income
     */
    public function actionShopeeIncome()
    {
        echo "Starting Shopee Income Sync...\n";
        try {
            $shopeeService = new \backend\services\ShopeeIncomeService();
            $count = $shopeeService->syncAllOrders();
            echo "Shopee Income Synced. Count: " . $count . "\n";
        } catch (\Exception $e) {
            echo "Error Syncing Shopee Income: " . $e->getMessage() . "\n";
        }
    }
}
