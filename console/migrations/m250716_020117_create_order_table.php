<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%order}}`.
 */
class m250716_020117_create_order_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%order}}', [
            'id' => $this->primaryKey(),
            'order_no' => $this->string(),
            'order_date' => $this->datetime(),
            'sync_date' => $this->datetime(),
            'online_channel_id' => $this->integer(),
            'customer_name' => $this->string(),
            'status' => $this->integer(),
            'online_status' => $this->integer(),
            'payment_status' => $this->integer(),
            'delivery_status' => $this->integer(),
            'order_total_amount' => $this->float(),
            'note' => $this->string(),
            'created_at' => $this->integer(),
            'crated_by' => $this->integer(),
            'updated_at' => $this->integer(),
            'updated_by' => $this->integer(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%order}}');
    }
}
