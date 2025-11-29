<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%shopee_income_details}}`.
 */
class m251129_104500_create_shopee_income_details_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%shopee_income_details}}', [
            'id' => $this->primaryKey(),
            'order_sn' => $this->string(50)->notNull()->unique(),
            'buyer_user_name' => $this->string(100),
            'buyer_total_amount' => $this->decimal(10, 2),
            'original_price' => $this->decimal(10, 2),
            'seller_return_refund_amount' => $this->decimal(10, 2),
            'shipping_fee_discount_from_3pl' => $this->decimal(10, 2),
            'seller_shipping_discount' => $this->decimal(10, 2),
            'drc_adjustable_refund' => $this->decimal(10, 2),
            'cost_of_goods_sold' => $this->decimal(10, 2),
            'original_cost_of_goods_sold' => $this->decimal(10, 2),
            'original_shopee_discount' => $this->decimal(10, 2),
            'seller_coin_cash_back' => $this->decimal(10, 2),
            'shopee_shipping_rebate' => $this->decimal(10, 2),
            'commission_fee' => $this->decimal(10, 2),
            'transaction_fee' => $this->decimal(10, 2),
            'service_fee' => $this->decimal(10, 2),
            'seller_voucher_code' => $this->decimal(10, 2),
            'shopee_voucher_code' => $this->decimal(10, 2),
            'escrow_amount' => $this->decimal(10, 2),
            'exchange_rate' => $this->decimal(10, 4),
            'reverse_shipping_fee' => $this->decimal(10, 2),
            'final_shipping_fee' => $this->decimal(10, 2),
            'actual_shipping_fee' => $this->decimal(10, 2),
            'order_chargeable_weight' => $this->decimal(10, 2),
            'payment_promotion_amount' => $this->decimal(10, 2),
            'cross_border_tax' => $this->decimal(10, 2),
            'shipping_fee_paid_by_buyer' => $this->decimal(10, 2),
            'items' => $this->json(),
            'created_at' => $this->dateTime(),
            'updated_at' => $this->dateTime(),
        ]);

        $this->createIndex(
            '{{%idx-shopee_income_details-order_sn}}',
            '{{%shopee_income_details}}',
            'order_sn'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%shopee_income_details}}');
    }
}
