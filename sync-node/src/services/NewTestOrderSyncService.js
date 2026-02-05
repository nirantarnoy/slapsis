const axios = require('axios');
const crypto = require('crypto');
const moment = require('moment');
const db = require('../models/db');

class NewTestOrderSyncService {
    constructor() {
        this.partnerId = 2012399;
        this.partnerKey = 'shpk72476151525864414e4b6e475449626679624f695a696162696570417043';
    }

    async syncShopeeOrders(channel) {
        const [tokens] = await db.execute(
            'SELECT * FROM shopee_tokens ORDER BY created_at DESC LIMIT 1'
        );
        let tokenModel = tokens[0];

        if (!tokenModel) {
            console.warn('No active Shopee token found for channel: ' + channel.id);
            return 0;
        }

        if (moment(tokenModel.expires_at).isBefore(moment())) {
            console.log('Access Token is expired, attempting to refresh...');
            const refreshed = await this.refreshShopeeToken(tokenModel);
            if (!refreshed) {
                console.warn('Failed to refresh Shopee token for channel: ' + channel.id);
                return 0;
            }
            tokenModel = refreshed;
        }

        let accessToken = tokenModel.access_token;
        const shopId = tokenModel.shop_id;
        let count = 0;
        const pageSize = 50;
        let cursor = '';

        try {
            do {
                const timestamp = Math.floor(Date.now() / 1000);
                const path = "/api/v2/order/get_order_list";
                const baseString = this.partnerId + path + timestamp + accessToken + shopId;
                const sign = crypto.createHmac('sha256', this.partnerKey).update(baseString).digest('hex');

                const params = {
                    partner_id: parseInt(this.partnerId),
                    shop_id: parseInt(shopId),
                    sign: sign,
                    timestamp: timestamp,
                    access_token: accessToken,
                    time_range_field: 'create_time',
                    time_from: Math.floor(moment().subtract(15, 'days').unix()),
                    time_to: timestamp,
                    page_size: pageSize,
                    response_optional_fields: 'order_status',
                };

                if (cursor) params.cursor = cursor;

                try {
                    const response = await axios.get('https://partner.shopeemobile.com' + path, {
                        params: params,
                        timeout: 30000,
                        validateStatus: () => true
                    });

                    if (response.status !== 200) {
                        console.error(`HTTP Shopee Sync Error: ${response.status}`);
                        if ([401, 403].includes(response.status)) {
                            const refreshed = await this.refreshShopeeToken(tokenModel);
                            if (refreshed) {
                                tokenModel = refreshed;
                                accessToken = tokenModel.access_token;
                                continue;
                            }
                        }
                        break;
                    }

                    const data = response.data;
                    if (data.error) {
                        console.error(`Shopee API Error Sync: ${data.error} - ${data.message || 'Unknown error'}`);
                        if (['error_auth', 'error_permission', 'error_token', 'error_invalid_token', 'invalid_acceess_token'].includes(data.error)) {
                            const refreshed = await this.refreshShopeeToken(tokenModel);
                            if (refreshed) {
                                tokenModel = refreshed;
                                accessToken = tokenModel.access_token;
                                continue;
                            }
                        }
                        break;
                    }

                    if (!data.response || !data.response.order_list || data.response.order_list.length === 0) {
                        break;
                    }

                    const orderSns = data.response.order_list.map(o => o.order_sn);
                    if (orderSns.length > 0) {
                        const batchCount = await this.processShopeeOrdersBatch(channel, orderSns, accessToken, shopId);
                        count += batchCount;
                    }

                    cursor = data.response.next_cursor || '';
                    if (cursor) await new Promise(r => setTimeout(r, 200));

                } catch (err) {
                    console.error("Exception during Shopee API call:", err.message);
                    break;
                }
            } while (cursor);

            console.log(`Synced ${count} Shopee orders for channel: ${channel.id}`);
            return count;

        } catch (error) {
            console.error('Shopee API error:', error.message);
            throw error;
        }
    }

    async processShopeeOrdersBatch(channel, orderSns, accessToken, shopId) {
        let count = 0;
        const orderSnString = orderSns.join(',');

        try {
            const timestamp = Math.floor(Date.now() / 1000);
            const path = "/api/v2/order/get_order_detail";
            const baseString = this.partnerId + path + timestamp + accessToken + shopId;
            const sign = crypto.createHmac('sha256', this.partnerKey).update(baseString).digest('hex');

            const response = await axios.get('https://partner.shopeemobile.com' + path, {
                params: {
                    partner_id: parseInt(this.partnerId),
                    timestamp: timestamp,
                    access_token: accessToken,
                    shop_id: parseInt(shopId),
                    sign: sign,
                    order_sn_list: orderSnString,
                    response_optional_fields: 'item_list,buyer_paid_amount,buyer_total_amount,total_amount,payment_info,escrow_amount,commission_fee,service_fee',
                },
                timeout: 30000
            });

            if (response.status !== 200) return 0;

            const data = response.data;
            if (data.error || !data.response || !data.response.order_list) return 0;

            const allowedStatuses = ['READY_TO_SHIP', 'PROCESSED', 'SHIPPED', 'COMPLETED', 'TO_CONFIRM_RECEIVE'];

            for (const orderDetail of data.response.order_list) {
                const orderSn = orderDetail.order_sn;
                const orderStatus = (orderDetail.order_status || 'UNKNOWN').toUpperCase();

                if (!allowedStatuses.includes(orderStatus)) continue;
                if (!orderDetail.item_list) continue;

                for (const item of orderDetail.item_list) {
                    const uniqueOrderId = `${orderSn}_${item.item_id}`;

                    const [existing] = await db.execute('SELECT id FROM `order` WHERE order_id = ?', [uniqueOrderId]);
                    if (existing[0]) continue;

                    const sku = item.model_sku || item.item_sku || 'UNKNOWN_SKU';
                    const productName = item.item_name;

                    await this.checkSaveNewProduct(sku, productName);

                    const quantity = item.model_quantity_purchased || item.quantity_purchased || 0;
                    const price = parseFloat(item.model_discounted_price || item.discounted_price || item.model_original_price || item.original_price || 0);

                    const orderDate = moment.unix(orderDetail.create_time || timestamp).format('YYYY-MM-DD HH:mm:ss');
                    const now = moment().format('YYYY-MM-DD HH:mm:ss');

                    const insertQuery = `
                        INSERT INTO \`order\` (order_id, channel_id, shop_id, order_sn, sku, product_name, quantity, price, total_amount, order_date, order_status, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    `;
                    const values = [
                        uniqueOrderId, channel.id, shopId, orderSn, sku, productName,
                        quantity, price, quantity * price, orderDate, orderStatus, now, now
                    ];

                    await db.execute(insertQuery, values);
                    count++;
                }
            }
        } catch (error) {
            console.error('Error processing Shopee batch:', error.message);
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
        } catch (error) {
            console.error(`Error in checkSaveNewProduct: ${error.message}`);
        }
    }

    async refreshShopeeToken(tokenModel) {
        try {
            const timestamp = Math.floor(Date.now() / 1000);
            const path = "/api/v2/auth/access_token/get";
            const baseString = this.partnerId + path + timestamp;
            const sign = crypto.createHmac('sha256', this.partnerKey).update(baseString).digest('hex');

            const response = await axios.post('https://partner.shopeemobile.com' + path, {
                shop_id: parseInt(tokenModel.shop_id),
                partner_id: parseInt(this.partnerId),
                refresh_token: tokenModel.refresh_token,
            }, {
                params: { partner_id: parseInt(this.partnerId), timestamp, sign },
                headers: { 'Content-Type': 'application/json' },
                timeout: 30000
            });

            const data = response.data;
            if (data.error || !data.access_token) return null;

            const expiresAt = moment().add(data.expire_in || 14400, 'seconds').format('YYYY-MM-DD HH:mm:ss');
            const now = moment().format('YYYY-MM-DD HH:mm:ss');

            await db.execute(
                'UPDATE shopee_tokens SET access_token = ?, refresh_token = ?, expires_at = ?, updated_at = ? WHERE id = ?',
                [data.access_token, data.refresh_token, expiresAt, now, tokenModel.id]
            );

            return {
                ...tokenModel,
                access_token: data.access_token,
                refresh_token: data.refresh_token,
                expires_at: expiresAt
            };
        } catch (error) {
            console.error('Failed to refresh Shopee token:', error.message);
            return null;
        }
    }
}

module.exports = NewTestOrderSyncService;
