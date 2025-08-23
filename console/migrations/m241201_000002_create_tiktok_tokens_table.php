<?php
class m241201_000002_create_tiktok_tokens_table extends \yii\db\Migration
{
    public function safeUp()
    {
        $this->createTable('{{%tiktok_tokens}}', [
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
        $this->createIndex('idx-tiktok_tokens-channel_id', '{{%tiktok_tokens}}', 'channel_id');
        $this->createIndex('idx-tiktok_tokens-shop_id', '{{%tiktok_tokens}}', 'shop_id');
        $this->createIndex('idx-tiktok_tokens-status', '{{%tiktok_tokens}}', 'status');

        // เพิ่ม foreign key
        $this->addForeignKey(
            'fk-tiktok_tokens-channel_id',
            '{{%tiktok_tokens}}',
            'channel_id',
            '{{%online_channel}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-tiktok_tokens-channel_id', '{{%tiktok_tokens}}');
        $this->dropTable('{{%tiktok_tokens}}');
    }
}