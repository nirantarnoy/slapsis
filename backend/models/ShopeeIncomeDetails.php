<?php

namespace backend\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "shopee_income_details".
 *
 * @property int $id
 * @property string $order_sn
 * @property string|null $order_date
 * @property string|null $buyer_user_name
 * @property float|null $buyer_total_amount
 * @property float|null $original_price
 * @property float|null $seller_return_refund_amount
 * @property float|null $shipping_fee_discount_from_3pl
 * @property float|null $seller_shipping_discount
 * @property float|null $drc_adjustable_refund
 * @property float|null $cost_of_goods_sold
 * @property float|null $original_cost_of_goods_sold
 * @property float|null $original_shopee_discount
 * @property float|null $seller_coin_cash_back
 * @property float|null $shopee_shipping_rebate
 * @property float|null $commission_fee
 * @property float|null $transaction_fee
 * @property float|null $service_fee
 * @property float|null $seller_voucher_code
 * @property float|null $shopee_voucher_code
 * @property float|null $escrow_amount
 * @property float|null $exchange_rate
 * @property float|null $reverse_shipping_fee
 * @property float|null $final_shipping_fee
 * @property float|null $actual_shipping_fee
 * @property float|null $order_chargeable_weight
 * @property float|null $payment_promotion_amount
 * @property float|null $cross_border_tax
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class ShopeeIncomeDetails extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'shopee_income_details';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_sn'], 'required'],
            [['buyer_total_amount', 'original_price', 'seller_return_refund_amount', 'shipping_fee_discount_from_3pl', 'seller_shipping_discount', 'drc_adjustable_refund', 'cost_of_goods_sold', 'original_cost_of_goods_sold', 'original_shopee_discount', 'seller_coin_cash_back', 'shopee_shipping_rebate', 'commission_fee', 'transaction_fee', 'service_fee', 'seller_voucher_code', 'shopee_voucher_code', 'escrow_amount', 'exchange_rate', 'reverse_shipping_fee', 'final_shipping_fee', 'actual_shipping_fee', 'order_chargeable_weight', 'payment_promotion_amount', 'cross_border_tax', 'shipping_fee_paid_by_buyer'], 'number'],
            [['created_at', 'updated_at', 'items', 'order_date'], 'safe'],
            [['order_sn'], 'string', 'max' => 50],
            [['buyer_user_name'], 'string', 'max' => 100],
            [['order_sn'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_sn' => 'Order SN',
            'order_date' => 'Order Date',
            'buyer_user_name' => 'Buyer User Name',
            'buyer_total_amount' => 'Buyer Total Amount',
            'original_price' => 'Original Price',
            'seller_return_refund_amount' => 'Seller Return Refund Amount',
            'shipping_fee_discount_from_3pl' => 'Shipping Fee Discount From 3pl',
            'seller_shipping_discount' => 'Seller Shipping Discount',
            'drc_adjustable_refund' => 'Drc Adjustable Refund',
            'cost_of_goods_sold' => 'Cost Of Goods Sold',
            'original_cost_of_goods_sold' => 'Original Cost Of Goods Sold',
            'original_shopee_discount' => 'Original Shopee Discount',
            'seller_coin_cash_back' => 'Seller Coin Cash Back',
            'shopee_shipping_rebate' => 'Shopee Shipping Rebate',
            'commission_fee' => 'Commission Fee',
            'transaction_fee' => 'Transaction Fee',
            'service_fee' => 'Service Fee',
            'seller_voucher_code' => 'Seller Voucher Code',
            'shopee_voucher_code' => 'Shopee Voucher Code',
            'escrow_amount' => 'Escrow Amount',
            'exchange_rate' => 'Exchange Rate',
            'reverse_shipping_fee' => 'Reverse Shipping Fee',
            'final_shipping_fee' => 'Final Shipping Fee',
            'actual_shipping_fee' => 'Actual Shipping Fee',
            'order_chargeable_weight' => 'Order Chargeable Weight',
            'payment_promotion_amount' => 'Payment Promotion Amount',
            'cross_border_tax' => 'Cross Border Tax',
            'shipping_fee_paid_by_buyer' => 'Shipping Fee Paid By Buyer',
            'items' => 'Items',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
