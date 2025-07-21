<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%online_channel}}`.
 */
class m250716_015259_create_online_channel_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%online_channel}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(),
            'description' => $this->string(),
            'logo' => $this->string(),
            'access_token' => $this->string(),
            'refresh_token' => $this->string(),
            'app_token' => $this->string(),
            'status' => $this->integer(),
            'created_at' => $this->integer(),
            'created_by' => $this->integer(),
            'updated_at' => $this->integer(),
            'updated_by' => $this->integer(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%online_channel}}');
    }
}
