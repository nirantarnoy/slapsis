<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%tiktok_income_details}}`.
 */
class m251129_113000_create_tiktok_income_details_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%tiktok_income_details}}', [
            'id' => $this->primaryKey(),
            'order_id' => $this->string(100)->notNull()->unique(),
            'settlement_amount' => $this->decimal(10, 2),
            'revenue_amount' => $this->decimal(10, 2),
            'shipping_cost_amount' => $this->decimal(10, 2),
            'fee_and_tax_amount' => $this->decimal(10, 2),
            'adjustment_amount' => $this->decimal(10, 2),
            'currency' => $this->string(10),
            
            // Added columns from log
            'actual_shipping_fee_amount' => $this->decimal(10, 2),
            'affiliate_commission_amount' => $this->decimal(10, 2),
            'customer_payment_amount' => $this->decimal(10, 2),
            'customer_refund_amount' => $this->decimal(10, 2),
            'gross_sales_amount' => $this->decimal(10, 2),
            'gross_sales_refund_amount' => $this->decimal(10, 2),
            'net_sales_amount' => $this->decimal(10, 2),
            'platform_commission_amount' => $this->decimal(10, 2),
            'platform_discount_amount' => $this->decimal(10, 2),
            'platform_discount_refund_amount' => $this->decimal(10, 2),
            'platform_shipping_fee_discount_amount' => $this->decimal(10, 2),
            'sales_tax_amount' => $this->decimal(10, 2),
            'sales_tax_payment_amount' => $this->decimal(10, 2),
            'sales_tax_refund_amount' => $this->decimal(10, 2),
            'shipping_fee_amount' => $this->decimal(10, 2),
            'shipping_fee_subsidy_amount' => $this->decimal(10, 2),
            'transaction_fee_amount' => $this->decimal(10, 2),
            
            'statement_transactions' => $this->json(),
            'sku_transactions' => $this->json(),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ]);

        $this->createIndex(
            '{{%idx-tiktok_income_details-order_id}}',
            '{{%tiktok_income_details}}',
            'order_id'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%tiktok_income_details}}');
    }
}
