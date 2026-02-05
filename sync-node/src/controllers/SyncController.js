const moment = require('moment');
const db = require('../models/db');
const OrderSyncService = require('../services/OrderSyncService');
const NewTestOrderSyncService = require('../services/NewTestOrderSyncService');
const TiktokIncomeService = require('../services/TiktokIncomeService');
const ShopeeIncomeService = require('../services/ShopeeIncomeService');

class SyncController {
    async actionIndex() {
        console.log("Starting Sync Process...");

        const startTime = moment().format('YYYY-MM-DD HH:mm:ss');
        let logId;

        try {
            const [logResult] = await db.execute(
                'INSERT INTO sync_log (type, platform, start_time, status) VALUES (?, ?, ?, ?)',
                ['order', 'all', startTime, 'pending']
            );
            logId = logResult.insertId;

            // 1. Sync Orders
            console.log("Syncing Orders...");
            let totalSynced = 0;
            let errors = [];

            // 1.1 Sync TikTok
            const [tiktokChannels] = await db.execute('SELECT * FROM online_channel WHERE name = ? AND status = 1', ['Tiktok']);
            if (tiktokChannels[0]) {
                console.log("Syncing TikTok Orders...");
                const orderService = new OrderSyncService();
                const res = await orderService.syncOrders(tiktokChannels[0].id);
                totalSynced += res.count;
                if (res.errors && res.errors.length > 0) {
                    errors = errors.concat(res.errors);
                }
                console.log(`TikTok Orders Synced. Count: ${res.count}`);
            }

            // 1.2 Sync Shopee
            const [shopeeChannels] = await db.execute('SELECT * FROM online_channel WHERE name = ? AND status = 1', ['Shopee']);
            if (shopeeChannels[0]) {
                console.log("Syncing Shopee Orders (New Logic)...");
                const newShopeeService = new NewTestOrderSyncService();
                const shopeeCount = await newShopeeService.syncShopeeOrders(shopeeChannels[0]);
                totalSynced += shopeeCount;
                console.log(`Shopee Orders Synced. Count: ${shopeeCount}`);
            }

            console.log(`Total Orders Synced: ${totalSynced}`);
            if (errors.length > 0) {
                console.log(`Errors: ${errors.join(", ")}`);
            }

            const endTime = moment().format('YYYY-MM-DD HH:mm:ss');
            await db.execute(
                'UPDATE sync_log SET end_time = ?, status = ?, total_records = ? WHERE id = ?',
                [endTime, 'success', totalSynced, logId]
            );

        } catch (error) {
            console.error(`Error Syncing Orders: ${error.message}`);
            const endTime = moment().format('YYYY-MM-DD HH:mm:ss');
            if (logId) {
                await db.execute(
                    'UPDATE sync_log SET end_time = ?, status = ?, message = ? WHERE id = ?',
                    [endTime, 'failed', error.message, logId]
                );
            }
        }

        // 2. Sync TikTok Income
        try {
            const tiktokService = new TiktokIncomeService();
            const count = await tiktokService.syncAllOrders();
            console.log(`TikTok Income Synced. Count: ${count}`);
        } catch (error) {
            console.error(`Error Syncing TikTok Income: ${error.message}`);
        }

        // 3. Sync Shopee Income
        try {
            const shopeeService = new ShopeeIncomeService();
            const count = await shopeeService.syncAllOrders();
            console.log(`Shopee Income Synced. Count: ${count}`);
        } catch (error) {
            console.error(`Error Syncing Shopee Income: ${error.message}`);
        }

        console.log("Sync Process Completed.");
        process.exit(0);
    }
}

module.exports = SyncController;
