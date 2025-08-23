<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%shopee_token}}`.
 */
class m230823_100000_create_shopee_token_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%shopee_token}}', [
            'id' => $this->primaryKey(),
            'shop_id' => $this->bigInteger()->notNull(),
            'access_token' => $this->text()->notNull(),
            'refresh_token' => $this->text()->notNull(),
            'expire_time' => $this->dateTime()->notNull(),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // อาจจะเพิ่ม unique key กันซ้ำ shop_id
        $this->createIndex(
            'idx-shopee_token-shop_id',
            '{{%shopee_token}}',
            'shop_id',
            true // unique
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%shopee_token}}');
    }
}
