const axios = require('axios');
const crypto = require('crypto');
const moment = require('moment');
const db = require('../models/db');

class OrderSyncService {
    constructor() {
        this.appKey = '6h9n461r774e1';
        this.appSecret = '1c45a0c25224293abd7de681049f90de3363389a';
    }

    async syncOrders(channelId = null) {
        let channels;
        if (channelId) {
            const [rows] = await db.execute('SELECT * FROM online_channel WHERE id = ?', [channelId]);
            channels = rows;
        } else {
            const [rows] = await db.execute('SELECT * FROM online_channel WHERE status = 1');
            channels = rows;
        }

        let totalSynced = 0;
        const errors = [];

        for (const channel of channels) {
            try {
                if (channel.name === 'Tiktok') {
                    totalSynced += await this.syncTikTokOrders(channel);
                } else if (channel.name === 'Shopee') {
                    // Logic for original Shopee sync if needed, 
                    // but the controller uses NewTestOrderSyncService for Shopee
                }
            } catch (error) {
                errors.push(`${channel.name}: ${error.message}`);
                console.error(`Order sync error for ${channel.name}:`, error.message);
            }
        }

        return { count: totalSynced, errors };
    }

    async syncTikTokOrders(channel) {
        const [tokens] = await db.execute(
            'SELECT * FROM tiktok_tokens ORDER BY created_at DESC LIMIT 1'
        );
        let tokenModel = tokens[0];

        if (!tokenModel) {
            console.error('No active TikTok token found');
            return 0;
        }

        if (tokenModel.expires_at && moment(tokenModel.expires_at).isBefore(moment())) {
            tokenModel = await this.refreshTikTokToken(tokenModel);
        }

        if (!tokenModel.shop_cipher) {
            tokenModel.shop_cipher = await this.fetchShopCipher(tokenModel);
        }

        const accessToken = tokenModel.access_token;
        const shopCipher = tokenModel.shop_cipher;
        const pageSize = 50;
        let pageToken = '';
        let count = 0;
        let pageCount = 0;
        const path = '/order/202309/orders/search';

        try {
            do {
                pageCount++;
                const timestamp = Math.floor(Date.now() / 1000);
                const queryParams = {
                    app_key: this.appKey,
                    page_size: pageSize,
                    shop_cipher: shopCipher,
                    sort_field: 'create_time',
                    sort_order: 'DESC',
                    timestamp: timestamp,
                };

                if (pageToken) queryParams.page_token = pageToken;

                const body = {
                    order_status: 'DELIVERED',
                    create_time_ge: Math.floor(moment().subtract(7, 'days').unix()),
                    create_time_lt: timestamp,
                };
                const bodyJson = JSON.stringify(body);

                const sign = this.generateSignForOrder(this.appSecret, queryParams, path, bodyJson);
                queryParams.sign = sign;
                queryParams.access_token = accessToken;

                const url = `https://open-api.tiktokglobalshop.com${path}`;

                const response = await axios.post(url, body, {
                    params: queryParams,
                    headers: {
                        'Content-Type': 'application/json',
                        'x-tts-access-token': accessToken,
                    },
                    timeout: 30000
                });

                const result = response.data;
                if (result.code !== 0) {
                    throw new Error(`TikTok API error [${result.code}] ${result.message}`);
                }

                const orders = result.data.orders || [];
                for (const order of orders) {
                    const saved = await this.processTikTokOrder(channel, order);
                    if (saved > 0) count += saved;
                }

                pageToken = result.data.next_page_token || '';
                if (pageCount >= 5) break;

            } while (pageToken);

        } catch (error) {
            console.error("TikTok sync error:", error.message);
        }

        console.log(`TikTok total orders synced: ${count}`);
        return count;
    }

    async processTikTokOrder(channel, orderData) {
        const orderId = orderData.id;
        let count = 0;

        try {
            for (const item of orderData.line_items) {
                const uniqueOrderId = `${orderId}_${item.id}`;

                const [existing] = await db.execute('SELECT id FROM `order` WHERE order_id = ?', [uniqueOrderId]);
                if (existing[0]) continue;

                let orderQty = 1;
                let skuId = '';
                const productName = item.product_name || '';

                if (item.combined_listing_skus && item.combined_listing_skus.length > 0) {
                    const sku = item.combined_listing_skus[0];
                    skuId = sku.sku_id || '';
                    orderQty = sku.sku_count || 1;
                } else {
                    skuId = item.sku_id || item.seller_sku || '';
                    orderQty = item.quantity || item.sku_count || 1;
                }

                if (!productName) continue;

                const salePrice = parseFloat(item.sale_price || 0);
                const price = salePrice > 1000 ? salePrice / 1000000 : salePrice;

                const orderDate = moment.unix(orderData.create_time).format('YYYY-MM-DD HH:mm:ss');
                const now = moment().format('YYYY-MM-DD HH:mm:ss');

                await this.checkSaveNewProduct(skuId, productName);

                const insertQuery = `
                    INSERT INTO \`order\` (order_id, channel_id, sku, product_name, quantity, price, total_amount, order_date, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                `;
                await db.execute(insertQuery, [
                    uniqueOrderId, channel.id, skuId, productName, orderQty, price, orderQty * price, orderDate, now
                ]);
                count++;
            }
        } catch (error) {
            console.error(`Error processing TikTok order ${orderId}:`, error.message);
        }
        return count;
    }

    async checkSaveNewProduct(sku, name) {
        try {
            const trimmedSku = (sku || '').trim();
            const trimmedName = (name || '').trim();
            const [existing] = await db.execute('SELECT id FROM product WHERE sku = ? AND name = ?', [trimmedSku, trimmedName]);
            if (!existing[0]) {
                await db.execute(
                    'INSERT INTO product (product_group_id, sku, name, status) VALUES (?, ?, ?, ?)',
                    [1, trimmedSku, trimmedName, 1]
                );
            }
        } catch (error) { }
    }

    generateSignForOrder(appSecret, params, path, body = '') {
        const sortedKeys = Object.keys(params).sort();
        let stringToSign = appSecret + path;
        for (const key of sortedKeys) {
            stringToSign += key + params[key];
        }
        if (body) stringToSign += body;
        stringToSign += appSecret;
        return crypto.createHmac('sha256', appSecret).update(stringToSign).digest('hex');
    }

    async fetchShopCipher(tokenModel) {
        const timestamp = Math.floor(Date.now() / 1000);
        const path = '/authorization/202309/shops';
        const params = { app_key: this.appKey, timestamp };
        const stringToSign = this.appSecret + path + 'app_key' + params.app_key + 'timestamp' + params.timestamp + this.appSecret;
        const sign = crypto.createHmac('sha256', this.appSecret).update(stringToSign).digest('hex');

        try {
            const response = await axios.get(`https://open-api.tiktokglobalshop.com${path}`, {
                params: { ...params, sign },
                headers: { 'x-tts-access-token': tokenModel.access_token }
            });
            const result = response.data;
            if (result.code === 0 && result.data && result.data.shops && result.data.shops[0]) {
                const shop = result.data.shops[0];
                await db.execute(
                    'UPDATE tiktok_tokens SET shop_cipher = ?, shop_name = ?, updated_at = ? WHERE id = ?',
                    [shop.cipher, shop.name, moment().format('YYYY-MM-DD HH:mm:ss'), tokenModel.id]
                );
                return shop.cipher;
            }
        } catch (error) {
            console.error('Error fetching shop cipher:', error.message);
        }
        return null;
    }

    async refreshTikTokToken(tokenModel) {
        try {
            const params = {
                app_key: this.appKey,
                app_secret: this.appSecret,
                refresh_token: tokenModel.refresh_token,
                grant_type: 'refresh_token'
            };
            const response = await axios.get('https://auth.tiktok-shops.com/api/v2/token/refresh', { params });
            if (response.data.data && response.data.data.access_token) {
                const data = response.data.data;
                const expiresAt = moment().add(data.access_token_expire_in, 'seconds').format('YYYY-MM-DD HH:mm:ss');
                await db.execute(
                    'UPDATE tiktok_tokens SET access_token = ?, refresh_token = ?, expires_at = ?, updated_at = ? WHERE id = ?',
                    [data.access_token, data.refresh_token, expiresAt, moment().format('YYYY-MM-DD HH:mm:ss'), tokenModel.id]
                );
                return { ...tokenModel, access_token: data.access_token, refresh_token: data.refresh_token, expires_at: expiresAt };
            }
        } catch (error) { }
        return tokenModel;
    }
}

module.exports = OrderSyncService;
