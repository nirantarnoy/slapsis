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
