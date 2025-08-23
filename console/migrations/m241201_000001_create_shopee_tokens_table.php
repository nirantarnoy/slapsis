<?php
class m241201_000001_create_shopee_tokens_table extends \yii\db\Migration
{
    public function safeUp()
    {
        $this->createTable('{{%shopee_tokens}}', [
            'id' => $this->primaryKey(),
            'channel_id' => $this->integer()->notNull(),
            'shop_id' => $this->string(100)->notNull(),
            'access_token' => $this->text()->notNull(),
            'refresh_token' => $this->text(),
            'expires_at' => $this->integer(),
            'status' => $this->string(20)->defaultValue('active'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // เพิ่ม index
        $this->createIndex('idx-shopee_tokens-channel_id', '{{%shopee_tokens}}', 'channel_id');
        $this->createIndex('idx-shopee_tokens-shop_id', '{{%shopee_tokens}}', 'shop_id');
        $this->createIndex('idx-shopee_tokens-status', '{{%shopee_tokens}}', 'status');

        // เพิ่ม foreign key
        $this->addForeignKey(
            'fk-shopee_tokens-channel_id',
            '{{%shopee_tokens}}',
            'channel_id',
            '{{%online_channel}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-shopee_tokens-channel_id', '{{%shopee_tokens}}');
        $this->dropTable('{{%shopee_tokens}}');
    }
}