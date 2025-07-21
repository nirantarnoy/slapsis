<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%order_line}}`.
 */
class m250716_020317_create_order_line_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%order_line}}', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer(),
            'product_id' => $this->integer(),
            'sku' => $this->string(),
            'description' => $this->string(),
            'qty' => $this->float(),
            'sale_price' => $this->float(),
            'discount_price' => $this->float(),
            'line_total' => $this->float(),
            'status' => $this->integer(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%order_line}}');
    }
}
