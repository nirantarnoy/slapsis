<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%shopee_transaction}}`.
 */
class m251008_140425_create_shopee_transaction_table extends Migration
{
    public function safeUp()
    {
        // สร้างตาราง shopee_transaction
        $this->createTable('{{%shopee_transaction}}', [
            'id' => $this->primaryKey(),
            'transaction_id' => $this->string(100)->notNull()->comment('Transaction ID จาก Shopee'),
            'channel_id' => $this->integer()->notNull()->comment('ID ของ channel'),
            'shop_id' => $this->string(50)->notNull()->comment('Shop ID จาก Shopee'),
            'transaction_type' => $this->string(50)->comment('ประเภทธุรกรรม เช่น PAYMENT, REFUND, ADJUSTMENT'),
            'status' => $this->string(50)->comment('สถานะธุรกรรม'),
            'reason' => $this->string(255)->comment('เหตุผลของธุรกรรม'),
            'amount' => $this->decimal(15, 2)->notNull()->comment('จำนวนเงิน (ติดลบ = หัก, บวก = รับ)'),
            'current_balance' => $this->decimal(15, 2)->comment('ยอดคงเหลือ wallet หลังทำธุรกรรม'),
            'order_sn' => $this->string(50)->comment('Order SN ที่เกี่ยวข้อง (ถ้ามี)'),
            'fee_category' => $this->string(50)->comment('หมวดหมู่ค่าธรรมเนียม'),
            'transaction_date' => $this->dateTime()->notNull()->comment('วันที่ทำธุรกรรม'),
            'created_at' => $this->dateTime()->notNull()->comment('วันที่สร้างข้อมูล'),
            'updated_at' => $this->dateTime()->notNull()->comment('วันที่อัปเดตข้อมูล'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT="เก็บข้อมูลธุรกรรมและค่าธรรมเนียมจาก Shopee"');

        // สร้าง unique index สำหรับ transaction_id
        $this->createIndex(
            'idx-shopee_transaction-transaction_id',
            '{{%shopee_transaction}}',
            'transaction_id',
            true
        );

        // สร้าง index สำหรับ channel_id
        $this->createIndex(
            'idx-shopee_transaction-channel_id',
            '{{%shopee_transaction}}',
            'channel_id'
        );

        // สร้าง index สำหรับ shop_id
        $this->createIndex(
            'idx-shopee_transaction-shop_id',
            '{{%shopee_transaction}}',
            'shop_id'
        );

        // สร้าง index สำหรับ order_sn
        $this->createIndex(
            'idx-shopee_transaction-order_sn',
            '{{%shopee_transaction}}',
            'order_sn'
        );

        // สร้าง index สำหรับ transaction_date
        $this->createIndex(
            'idx-shopee_transaction-transaction_date',
            '{{%shopee_transaction}}',
            'transaction_date'
        );

        // สร้าง index สำหรับ fee_category
        $this->createIndex(
            'idx-shopee_transaction-fee_category',
            '{{%shopee_transaction}}',
            'fee_category'
        );

        // สร้าง composite index สำหรับการ query ตามช่วงเวลาและ channel
        $this->createIndex(
            'idx-shopee_transaction-channel_date',
            '{{%shopee_transaction}}',
            ['channel_id', 'transaction_date']
        );

        // สร้าง foreign key ไปยัง table online_channel (ถ้ามี)
        // ปรับชื่อ table ให้ตรงกับระบบของคุณ
        if ($this->db->schema->getTableSchema('{{%online_channel}}') !== null) {
            $this->addForeignKey(
                'fk-shopee_transaction-channel_id',
                '{{%shopee_transaction}}',
                'channel_id',
                '{{%online_channel}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        // สร้าง foreign key ไปยัง table order (ถ้ามี order_sn)
        if ($this->db->schema->getTableSchema('{{%order}}') !== null) {
            $this->addForeignKey(
                'fk-shopee_transaction-order_sn',
                '{{%shopee_transaction}}',
                'order_sn',
                '{{%order}}',
                'order_sn',
                'SET NULL',
                'CASCADE'
            );
        }

        echo "✓ สร้างตาราง shopee_transaction สำเร็จ\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // ลบ foreign keys ก่อน
        if ($this->db->schema->getTableSchema('{{%online_channel}}') !== null) {
            $this->dropForeignKey('fk-shopee_transaction-channel_id', '{{%shopee_transaction}}');
        }

        if ($this->db->schema->getTableSchema('{{%order}}') !== null) {
            $this->dropForeignKey('fk-shopee_transaction-order_sn', '{{%shopee_transaction}}');
        }

        // ลบ indexes
        $this->dropIndex('idx-shopee_transaction-channel_date', '{{%shopee_transaction}}');
        $this->dropIndex('idx-shopee_transaction-fee_category', '{{%shopee_transaction}}');
        $this->dropIndex('idx-shopee_transaction-transaction_date', '{{%shopee_transaction}}');
        $this->dropIndex('idx-shopee_transaction-order_sn', '{{%shopee_transaction}}');
        $this->dropIndex('idx-shopee_transaction-shop_id', '{{%shopee_transaction}}');
        $this->dropIndex('idx-shopee_transaction-channel_id', '{{%shopee_transaction}}');
        $this->dropIndex('idx-shopee_transaction-transaction_id', '{{%shopee_transaction}}');

        // ลบตาราง
        $this->dropTable('{{%shopee_transaction}}');

        echo "✓ ลบตาราง shopee_transaction สำเร็จ\n";
    }
}
