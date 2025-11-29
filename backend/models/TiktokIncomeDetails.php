<?php

namespace backend\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "tiktok_income_details".
 *
 * @property int $id
 * @property string $order_id
 * @property float|null $settlement_amount
 * @property float|null $revenue_amount
 * @property float|null $shipping_cost_amount
 * @property float|null $fee_and_tax_amount
 * @property float|null $adjustment_amount
 * @property string|null $currency
 * @property string|null $statement_transactions
 * @property string|null $sku_transactions
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class TiktokIncomeDetails extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tiktok_income_details';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id'], 'required'],
            [['settlement_amount', 'revenue_amount', 'shipping_cost_amount', 'fee_and_tax_amount', 'adjustment_amount', 'actual_shipping_fee_amount', 'affiliate_commission_amount', 'customer_payment_amount', 'customer_refund_amount', 'gross_sales_amount', 'gross_sales_refund_amount', 'net_sales_amount', 'platform_commission_amount', 'platform_discount_amount', 'platform_discount_refund_amount', 'platform_shipping_fee_discount_amount', 'sales_tax_amount', 'sales_tax_payment_amount', 'sales_tax_refund_amount', 'shipping_fee_amount', 'shipping_fee_subsidy_amount', 'transaction_fee_amount'], 'number'],
            [['statement_transactions', 'sku_transactions', 'created_at', 'updated_at'], 'safe'],
            [['order_id'], 'string', 'max' => 100],
            [['currency'], 'string', 'max' => 10],
            [['order_id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'settlement_amount' => 'Settlement Amount',
            'revenue_amount' => 'Revenue Amount',
            'shipping_cost_amount' => 'Shipping Cost Amount',
            'fee_and_tax_amount' => 'Fee And Tax Amount',
            'adjustment_amount' => 'Adjustment Amount',
            'currency' => 'Currency',
            'actual_shipping_fee_amount' => 'Actual Shipping Fee Amount',
            'affiliate_commission_amount' => 'Affiliate Commission Amount',
            'customer_payment_amount' => 'Customer Payment Amount',
            'customer_refund_amount' => 'Customer Refund Amount',
            'gross_sales_amount' => 'Gross Sales Amount',
            'gross_sales_refund_amount' => 'Gross Sales Refund Amount',
            'net_sales_amount' => 'Net Sales Amount',
            'platform_commission_amount' => 'Platform Commission Amount',
            'platform_discount_amount' => 'Platform Discount Amount',
            'platform_discount_refund_amount' => 'Platform Discount Refund Amount',
            'platform_shipping_fee_discount_amount' => 'Platform Shipping Fee Discount Amount',
            'sales_tax_amount' => 'Sales Tax Amount',
            'sales_tax_payment_amount' => 'Sales Tax Payment Amount',
            'sales_tax_refund_amount' => 'Sales Tax Refund Amount',
            'shipping_fee_amount' => 'Shipping Fee Amount',
            'shipping_fee_subsidy_amount' => 'Shipping Fee Subsidy Amount',
            'transaction_fee_amount' => 'Transaction Fee Amount',
            'statement_transactions' => 'Statement Transactions',
            'sku_transactions' => 'Sku Transactions',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
