const axios = require('axios');
const crypto = require('crypto');
const moment = require('moment');
const db = require('../models/db');

class ShopeeIncomeService {
    constructor() {
        this.partnerId = 2012399;
        this.partnerKey = 'shpk72476151525864414e4b6e475449626679624f695a696162696570417043';
    }

    async syncAllOrders() {
        console.log('Syncing Shopee Income...');
        const startTime = moment().format('YYYY-MM-DD HH:mm:ss');
        let logId;

        try {
            const [logResult] = await db.execute(
                'INSERT INTO sync_log (type, platform, start_time, status) VALUES (?, ?, ?, ?)',
                ['income', 'shopee', startTime, 'pending']
            );
            logId = logResult.insertId;

            const [channels] = await db.execute('SELECT * FROM online_channel WHERE name = ?', ['Shopee']);
            const shopeeChannel = channels[0];
            if (!shopeeChannel) throw new Error('Shopee channel not found');

            const [syncedOrders] = await db.execute('SELECT order_sn FROM shopee_income_details');
            const syncedOrderSns = syncedOrders.map(row => row.order_sn);

            let query = 'SELECT DISTINCT order_sn FROM `order` WHERE channel_id = ? AND order_sn IS NOT NULL';
            let params = [shopeeChannel.id];

            if (syncedOrderSns.length > 0) {
                query += ` AND order_sn NOT IN (${syncedOrderSns.map(() => '?').join(',')})`;
                params = params.concat(syncedOrderSns);
            }
            query += ' ORDER BY id DESC';

            const [orderSns] = await db.execute(query, params);

            let count = 0;
            console.log(`Found ${orderSns.length} Shopee orders to sync income details`);

            for (const row of orderSns) {
                const orderSn = row.order_sn;
                if (!orderSn) continue;

                if (await this.syncOrderIncome(orderSn)) {
                    count++;
                }
                await new Promise(resolve => setTimeout(resolve, 200));

                if (count >= 50) break;
            }

            const endTime = moment().format('YYYY-MM-DD HH:mm:ss');
            await db.execute(
                'UPDATE sync_log SET end_time = ?, status = ?, total_records = ? WHERE id = ?',
                [endTime, 'success', count, logId]
            );

            console.log(`Synced income details for ${count}/${orderSns.length} orders`);
            return count;

        } catch (error) {
            console.error('Shopee income sync error:', error.message);
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

    async syncOrderIncome(orderSn) {
        const [tokens] = await db.execute(
            'SELECT * FROM shopee_tokens WHERE status = "active" ORDER BY created_at DESC LIMIT 1'
        );
        let tokenModel = tokens[0];

        if (!tokenModel) {
            console.error('No active Shopee token found');
            return false;
        }

        if (moment(tokenModel.expires_at).isBefore(moment())) {
            const refreshed = await this.refreshShopeeToken(tokenModel);
            if (refreshed) {
                tokenModel = refreshed;
            } else {
                console.error('Failed to refresh Shopee token');
                return false;
            }
        }

        return await this.fetchAndSaveEscrowDetail(tokenModel, orderSn);
    }

    async fetchAndSaveEscrowDetail(tokenModel, orderSn) {
        const shopId = tokenModel.shop_id;
        const accessToken = tokenModel.access_token;
        const timestamp = Math.floor(Date.now() / 1000);
        const path = "/api/v2/payment/get_escrow_detail";
        const baseString = this.partnerId + path + timestamp + accessToken + shopId;
        const sign = crypto.createHmac('sha256', this.partnerKey).update(baseString).digest('hex');

        try {
            const response = await axios.get('https://partner.shopeemobile.com' + path, {
                params: {
                    partner_id: parseInt(this.partnerId),
                    shop_id: parseInt(shopId),
                    sign: sign,
                    timestamp: timestamp,
                    access_token: accessToken,
                    order_sn: orderSn
                },
                timeout: 30000
            });

            const data = response.data;
            if (data.error) {
                console.error(`Shopee API Error: ${data.error} - ${data.message || 'Unknown error'}`);
                return false;
            }

            if (!data.response) return false;

            const detail = data.response;
            const income = detail.order_income || {};

            const [existing] = await db.execute('SELECT id FROM shopee_income_details WHERE order_sn = ?', [orderSn]);

            let orderDate = null;
            const [orders] = await db.execute('SELECT order_date FROM `order` WHERE order_sn = ? LIMIT 1', [orderSn]);
            if (orders[0]) orderDate = orders[0].order_date;

            const incomeData = {
                order_sn: orderSn,
                order_date: orderDate,
                buyer_user_name: detail.buyer_user_name || detail.buyer_username || null,
                buyer_total_amount: income.buyer_total_amount || detail.buyer_total_amount || detail.buyer_paid_amount || detail.total_amount || 0,
                original_price: income.original_price || detail.original_price || detail.order_original_price || 0,
                seller_return_refund_amount: income.seller_return_refund || detail.seller_return_refund_amount || 0,
                shipping_fee_discount_from_3pl: income.shipping_fee_discount_from_3pl || detail.shipping_fee_discount_from_3pl || 0,
                seller_shipping_discount: income.seller_shipping_discount || detail.seller_shipping_discount || 0,
                drc_adjustable_refund: income.drc_adjustable_refund || detail.drc_adjustable_refund || 0,
                cost_of_goods_sold: income.cost_of_goods_sold || detail.cost_of_goods_sold || 0,
                original_cost_of_goods_sold: income.original_cost_of_goods_sold || detail.original_cost_of_goods_sold || 0,
                original_shopee_discount: income.original_shopee_discount || detail.original_shopee_discount || 0,
                seller_coin_cash_back: income.seller_coin_cash_back || detail.seller_coin_cash_back || 0,
                shopee_shipping_rebate: income.shopee_shipping_rebate || detail.shopee_shipping_rebate || 0,
                commission_fee: income.commission_fee || detail.commission_fee || 0,
                transaction_fee: income.transaction_fee || detail.transaction_fee || 0,
                service_fee: income.service_fee || detail.service_fee || 0,
                seller_voucher_code: income.voucher_from_seller || detail.seller_voucher_code || 0,
                shopee_voucher_code: income.voucher_from_shopee || detail.shopee_voucher_code || 0,
                escrow_amount: income.escrow_amount || detail.escrow_amount || detail.estimated_seller_receive_amount || 0,
                exchange_rate: detail.exchange_rate || 1,
                reverse_shipping_fee: income.reverse_shipping_fee || detail.reverse_shipping_fee || 0,
                final_shipping_fee: income.final_shipping_fee || detail.final_shipping_fee || detail.actual_shipping_fee || 0,
                actual_shipping_fee: income.actual_shipping_fee || detail.actual_shipping_fee || 0,
                order_chargeable_weight: income.order_chargeable_weight || detail.order_chargeable_weight || 0,
                payment_promotion_amount: income.payment_promotion || detail.payment_promotion_amount || 0,
                cross_border_tax: income.cross_border_tax || detail.cross_border_tax || 0,
                shipping_fee_paid_by_buyer: income.buyer_paid_shipping_fee || detail.shipping_fee_paid_by_buyer || 0,
                items: JSON.stringify(income.items || detail.items || []),
                updated_at: moment().format('YYYY-MM-DD HH:mm:ss')
            };

            if (existing[0]) {
                const keys = Object.keys(incomeData).filter(k => k !== 'order_sn');
                const setClause = keys.map(k => `\`${k}\` = ?`).join(', ');
                const values = keys.map(k => incomeData[k]);
                await db.execute(`UPDATE shopee_income_details SET ${setClause} WHERE order_sn = ?`, [...values, orderSn]);
            } else {
                incomeData.created_at = moment().format('YYYY-MM-DD HH:mm:ss');
                const keys = Object.keys(incomeData);
                const columns = keys.map(k => `\`${k}\``).join(', ');
                const placeholders = keys.map(() => '?').join(', ');
                const values = keys.map(k => incomeData[k]);
                await db.execute(`INSERT INTO shopee_income_details (${columns}) VALUES (${placeholders})`, values);
            }

            return true;
        } catch (error) {
            console.error(`Exception fetching income for order ${orderSn}:`, error.message);
            return false;
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
                params: {
                    partner_id: parseInt(this.partnerId),
                    timestamp: timestamp,
                    sign: sign,
                },
                headers: { 'Content-Type': 'application/json' },
                timeout: 30000
            });

            const data = response.data;
            if (data.error) {
                console.error(`Shopee API Error Refresh: ${data.error} - ${data.message || 'Unknown error'}`);
                return false;
            }

            if (data.access_token) {
                const expiresAt = moment().add(data.expire_in || 14400, 'seconds').format('YYYY-MM-DD HH:mm:ss');
                const updatedAt = moment().format('YYYY-MM-DD HH:mm:ss');

                await db.execute(
                    'UPDATE shopee_tokens SET access_token = ?, refresh_token = ?, expires_at = ?, updated_at = ? WHERE id = ?',
                    [data.access_token, data.refresh_token, expiresAt, updatedAt, tokenModel.id]
                );

                return {
                    ...tokenModel,
                    access_token: data.access_token,
                    refresh_token: data.refresh_token,
                    expires_at: expiresAt
                };
            }
            return false;
        } catch (error) {
            console.error('Failed to refresh Shopee token:', error.message);
            return false;
        }
    }
}

module.exports = ShopeeIncomeService;
