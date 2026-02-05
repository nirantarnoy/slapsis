const axios = require('axios');
const crypto = require('crypto');
const moment = require('moment');
const db = require('../models/db');

class TiktokIncomeService {
    constructor() {
        this.appKey = '6h9n461r774e1';
        this.appSecret = '1c45a0c25224293abd7de681049f90de3363389a';
    }

    async syncAllOrders() {
        console.log('Syncing TikTok Income...');
        const startTime = moment().format('YYYY-MM-DD HH:mm:ss');
        let logId;

        try {
            const [logResult] = await db.execute(
                'INSERT INTO sync_log (type, platform, start_time, status) VALUES (?, ?, ?, ?)',
                ['income', 'tiktok', startTime, 'pending']
            );
            logId = logResult.insertId;

            const [channels] = await db.execute('SELECT * FROM online_channel WHERE name = ?', ['Tiktok']);
            const tiktokChannel = channels[0];
            if (!tiktokChannel) throw new Error('Tiktok channel not found');

            const [syncedOrders] = await db.execute('SELECT order_id FROM tiktok_income_details');
            const syncedMap = new Set(syncedOrders.map(row => row.order_id));

            const [candidateOrders] = await db.execute(
                'SELECT DISTINCT order_id FROM `order` WHERE channel_id = ? AND order_id IS NOT NULL ORDER BY id DESC',
                [tiktokChannel.id]
            );

            let count = 0;
            const pendingOrderIds = candidateOrders
                .filter(row => {
                    if (!row.order_id) return false;
                    const actualOrderId = row.order_id.split('_')[0];
                    return !syncedMap.has(actualOrderId);
                })
                .map(row => row.order_id);

            console.log(`Found ${candidateOrders.length} total orders. Pending sync: ${pendingOrderIds.length}`);

            for (const orderId of pendingOrderIds) {
                const actualOrderId = orderId.split('_')[0];
                if (await this.syncOrderIncome(actualOrderId, orderId)) {
                    count++;
                }

                await new Promise(resolve => setTimeout(resolve, 200)); // Sleep 0.2s
                if (count >= 20) break;
            }

            const endTime = moment().format('YYYY-MM-DD HH:mm:ss');
            await db.execute(
                'UPDATE sync_log SET end_time = ?, status = ?, total_records = ? WHERE id = ?',
                [endTime, 'success', count, logId]
            );

            console.log(`TikTok Income Synced. Count: ${count}`);
            return count;

        } catch (error) {
            console.error('Tiktok income sync error:', error.message);
            if (logId) {
                const endTime = moment().format('YYYY-MM-DD HH:mm:ss');
                await db.execute(
                    'UPDATE sync_log SET end_time = ?, status = ?, message = ? WHERE id = ?',
                    [endTime, 'failed', error.message, logId]
                );
            }
            return 0;
        }
    }

    async syncOrderIncome(orderId, originOrderId) {
        const [tokens] = await db.execute(
            'SELECT * FROM tiktok_tokens WHERE status = "active" ORDER BY created_at DESC LIMIT 1'
        );
        let tokenModel = tokens[0];

        if (!tokenModel) {
            console.error('No active TikTok token found');
            return false;
        }

        if (tokenModel.expires_at && moment(tokenModel.expires_at).isBefore(moment())) {
            const refreshedToken = await this.refreshTikTokToken(tokenModel);
            if (refreshedToken) {
                tokenModel = refreshedToken;
            } else {
                console.error('Failed to refresh TikTok token');
                return false;
            }
        }

        if (!tokenModel.shop_cipher) {
            tokenModel.shop_cipher = await this.fetchShopCipher(tokenModel);
        }

        return await this.fetchAndSaveSettlementDetail(tokenModel, orderId, originOrderId);
    }

    async fetchShopCipher(tokenModel) {
        const timestamp = Math.floor(Date.now() / 1000);
        const path = '/authorization/202309/shops';
        const params = {
            app_key: this.appKey,
            timestamp: timestamp
        };

        const sign = this.generateSign(this.appSecret, params, path);
        const url = `https://open-api.tiktokglobalshop.com${path}?app_key=${params.app_key}&timestamp=${params.timestamp}&sign=${sign}`;

        try {
            const response = await axios.get(url, {
                headers: {
                    'Content-Type': 'application/json',
                    'x-tts-access-token': tokenModel.access_token
                }
            });

            const result = response.data;
            if (result.code !== 0) {
                console.error(`TikTok API error fetching cipher: [${result.code}] ${result.message}`);
                return null;
            }

            if (result.data && result.data.shops && result.data.shops[0]) {
                const shop = result.data.shops[0];
                const shopCipher = shop.cipher;
                const shopName = shop.name;

                await db.execute(
                    'UPDATE tiktok_tokens SET shop_cipher = ?, shop_name = ?, updated_at = ? WHERE id = ?',
                    [shopCipher, shopName, moment().format('YYYY-MM-DD HH:mm:ss'), tokenModel.id]
                );

                return shopCipher;
            }
        } catch (error) {
            console.error('Exception fetching shop cipher:', error.message);
        }
        return null;
    }

    async fetchAndSaveSettlementDetail(tokenModel, orderId, originOrderId) {
        const timestamp = Math.floor(Date.now() / 1000);
        const path = `/finance/202309/orders/${orderId}/statement_transactions`;
        const params = {
            app_key: this.appKey,
            shop_cipher: tokenModel.shop_cipher,
            timestamp: timestamp
        };

        const sign = this.generateSign(this.appSecret, params, path);
        const url = `https://open-api.tiktokglobalshop.com${path}?app_key=${params.app_key}&shop_cipher=${params.shop_cipher}&timestamp=${params.timestamp}&sign=${sign}&access_token=${tokenModel.access_token}`;

        try {
            const response = await axios.get(url, {
                headers: {
                    'Content-Type': 'application/json',
                    'x-tts-access-token': tokenModel.access_token
                },
                timeout: 30000
            });

            const data = response.data;
            if (data.code !== 0) {
                console.error(`TikTok API Error: ${data.code} - ${data.message || 'Unknown error'}`);
                return false;
            }

            const detail = data.data;
            if (!detail || !detail.statement_transactions || detail.statement_transactions.length === 0) {
                return false;
            }

            const transactions = detail.statement_transactions;
            const transaction = transactions[0];

            const getAmount = (key) => transaction[key] ? parseFloat(transaction[key]) : 0.0;

            const [existing] = await db.execute('SELECT id FROM tiktok_income_details WHERE order_id = ?', [orderId]);

            let orderDate = null;
            const [orders] = await db.execute('SELECT order_date FROM `order` WHERE order_id = ?', [originOrderId]);
            if (orders[0]) orderDate = orders[0].order_date;

            const incomeData = {
                order_id: orderId,
                order_date: orderDate,
                settlement_amount: getAmount('settlement_amount'),
                revenue_amount: getAmount('revenue_amount'),
                shipping_cost_amount: getAmount('shipping_cost_amount'),
                fee_and_tax_amount: getAmount('fee_amount'),
                adjustment_amount: getAmount('adjustment_amount'),
                actual_shipping_fee_amount: getAmount('actual_shipping_fee_amount'),
                affiliate_commission_amount: getAmount('affiliate_commission_amount'),
                customer_payment_amount: getAmount('customer_payment_amount'),
                customer_refund_amount: getAmount('customer_refund_amount'),
                gross_sales_amount: getAmount('gross_sales_amount'),
                gross_sales_refund_amount: getAmount('gross_sales_refund_amount'),
                net_sales_amount: getAmount('net_sales_amount'),
                platform_commission_amount: getAmount('platform_commission_amount'),
                platform_discount_amount: getAmount('platform_discount_amount'),
                platform_discount_refund_amount: getAmount('platform_discount_refund_amount'),
                platform_shipping_fee_discount_amount: getAmount('platform_shipping_fee_discount_amount'),
                sales_tax_amount: getAmount('sales_tax_amount'),
                sales_tax_payment_amount: getAmount('sales_tax_payment_amount'),
                sales_tax_refund_amount: getAmount('sales_tax_refund_amount'),
                shipping_fee_amount: getAmount('shipping_fee_amount'),
                shipping_fee_subsidy_amount: getAmount('shipping_fee_subsidy_amount'),
                transaction_fee_amount: getAmount('transaction_fee_amount'),
                currency: transaction.currency || 'THB',
                statement_transactions: JSON.stringify(transactions),
                sku_transactions: JSON.stringify(transaction.sku_statement_transactions || []),
                updated_at: moment().format('YYYY-MM-DD HH:mm:ss')
            };

            if (existing[0]) {
                const keys = Object.keys(incomeData).filter(k => k !== 'order_id');
                const setClause = keys.map(k => `\`${k}\` = ?`).join(', ');
                const values = keys.map(k => incomeData[k]);
                await db.execute(`UPDATE tiktok_income_details SET ${setClause} WHERE order_id = ?`, [...values, orderId]);
            } else {
                incomeData.created_at = moment().format('YYYY-MM-DD HH:mm:ss');
                const keys = Object.keys(incomeData);
                const columns = keys.map(k => `\`${k}\``).join(', ');
                const placeholders = keys.map(() => '?').join(', ');
                const values = keys.map(k => incomeData[k]);
                await db.execute(`INSERT INTO tiktok_income_details (${columns}) VALUES (${placeholders})`, values);
            }

            return true;
        } catch (error) {
            console.error(`Exception fetching TikTok income for order ${orderId}:`, error.message);
            return false;
        }
    }

    generateSign(appSecret, params, path) {
        const sortedKeys = Object.keys(params).sort();
        let stringToSign = appSecret + path;
        for (const key of sortedKeys) {
            stringToSign += key + params[key];
        }
        stringToSign += appSecret;
        return crypto.createHmac('sha256', appSecret).update(stringToSign).digest('hex');
    }

    async refreshTikTokToken(tokenModel) {
        try {
            const params = {
                app_key: this.appKey,
                app_secret: this.appSecret,
                refresh_token: tokenModel.refresh_token,
                grant_type: 'refresh_token'
            };

            const url = `https://auth.tiktok-shops.com/api/v2/token/refresh?app_key=${params.app_key}&app_secret=${params.app_secret}&refresh_token=${params.refresh_token}&grant_type=${params.grant_type}`;
            const response = await axios.get(url, { timeout: 30000 });
            const data = response.data;

            if (data.data && data.data.access_token) {
                const expiresAt = moment().add(data.data.access_token_expire_in, 'seconds').format('YYYY-MM-DD HH:mm:ss');
                const updatedAt = moment().format('YYYY-MM-DD HH:mm:ss');

                await db.execute(
                    'UPDATE tiktok_tokens SET access_token = ?, refresh_token = ?, expires_at = ?, updated_at = ? WHERE id = ?',
                    [data.data.access_token, data.data.refresh_token, expiresAt, updatedAt, tokenModel.id]
                );

                return {
                    ...tokenModel,
                    access_token: data.data.access_token,
                    refresh_token: data.data.refresh_token,
                    expires_at: expiresAt
                };
            }
            return null;
        } catch (error) {
            console.error('Failed to refresh TikTok token:', error.message);
            return null;
        }
    }
}

module.exports = TiktokIncomeService;
