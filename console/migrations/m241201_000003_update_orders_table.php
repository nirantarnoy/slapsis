<?php
class m241201_000003_update_orders_table extends \yii\db\Migration
{
    public function safeUp()
    {
        // เพิ่มคอลัมน์ใหม่ในตาราง orders
        $this->addColumn('{{%order}}', 'shop_id', $this->string(100)->after('channel_id'));
        $this->addColumn('{{%order}}', 'order_sn', $this->string(100)->after('order_id'));
        $this->addColumn('{{%order}}', 'order_status', $this->string(50)->after('total_amount'));

        // เพิ่ม index
        $this->createIndex('idx-order-shop_id', '{{%order}}', 'shop_id');
        $this->createIndex('idx-order-order_sn', '{{%order}}', 'order_sn');
        $this->createIndex('idx-order-order_status', '{{%order}}', 'order_status');
        $this->createIndex('idx-order-order_date', '{{%order}}', 'order_date');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-order-order_date', '{{%order}}');
        $this->dropIndex('idx-order-order_status', '{{%order}}');
        $this->dropIndex('idx-order-order_sn', '{{%order}}');
        $this->dropIndex('idx-order-shop_id', '{{%order}}');

        $this->dropColumn('{{%order}}', 'order_status');
        $this->dropColumn('{{%order}}', 'order_sn');
        $this->dropColumn('{{%order}}', 'shop_id');
    }
}