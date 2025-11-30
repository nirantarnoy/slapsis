<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%sync_log}}`.
 */
class m251130_100426_create_sync_log_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%sync_log}}', [
            'id' => $this->primaryKey(),
            'type' => $this->string()->notNull()->comment('Type of sync: order, income'),
            'platform' => $this->string()->notNull()->comment('Platform: tiktok, shopee'),
            'start_time' => $this->dateTime(),
            'end_time' => $this->dateTime(),
            'status' => $this->integer()->defaultValue(0)->comment('0: Pending, 1: Success, 2: Failed'),
            'total_records' => $this->integer()->defaultValue(0),
            'message' => $this->text(),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);
        
        $this->createIndex(
            '{{%idx-sync_log-type}}',
            '{{%sync_log}}',
            'type'
        );
        
        $this->createIndex(
            '{{%idx-sync_log-platform}}',
            '{{%sync_log}}',
            'platform'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%sync_log}}');
    }
}
