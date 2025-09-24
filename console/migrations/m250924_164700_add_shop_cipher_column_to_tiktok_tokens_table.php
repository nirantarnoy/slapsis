<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%tiktok_tokens}}`.
 */
class m250924_164700_add_shop_cipher_column_to_tiktok_tokens_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%tiktok_tokens}}', 'shop_cipher', $this->string());
        $this->addColumn('{{%tiktok_tokens}}', 'shop_name', $this->string());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%tiktok_tokens}}', 'shop_cipher');
        $this->dropColumn('{{%tiktok_tokens}}', 'shop_name');
    }
}
