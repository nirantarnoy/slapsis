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

        $log = new \common\models\SyncLog();
        $log->type = \common\models\SyncLog::TYPE_ORDER;
        $log->platform = 'all';
        $log->start_time = date('Y-m-d H:i:s');
        $log->status = \common\models\SyncLog::STATUS_PENDING;
        $log->save();

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

            $log->end_time = date('Y-m-d H:i:s');
            $log->status = \common\models\SyncLog::STATUS_SUCCESS;
            $log->total_records = $totalSynced;
            $log->save();

        } catch (\Exception $e) {
            echo "Error Syncing Orders: " . $e->getMessage() . "\n";
            Yii::error("Console Sync Error: " . $e->getMessage(), __METHOD__);
            
            $log->end_time = date('Y-m-d H:i:s');
            $log->status = \common\models\SyncLog::STATUS_FAILED;
            $log->message = $e->getMessage();
            $log->save();
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

    /**
     * Sync TikTok orders only
     * Usage: php yii sync/tiktok-orders [days]
     */
    public function actionTiktokOrders($days = 7)
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
                }
            } else {
                echo "Status: NO TOKEN FOUND in tiktok_token table!\n";
            }

            $orderService = new OrderSyncService();
            $res = $orderService->syncOrders($tiktokChannel->id, $days);
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
}
