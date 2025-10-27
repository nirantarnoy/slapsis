<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%shopee_settlement}}`.
 */
class m251014_144638_create_shopee_settlement_table extends Migration
{
    public function safeUp()
    {
        // สร้างตาราง shopee_settlement
        $this->createTable('{{%shopee_settlement}}', [
            'id' => $this->primaryKey(),
            'settlement_id' => $this->string(100)->notNull()->comment('Settlement ID จาก Shopee'),
            'channel_id' => $this->integer()->notNull()->comment('ID ของ channel'),
            'shop_id' => $this->string(50)->notNull()->comment('Shop ID จาก Shopee'),

            // ข้อมูลการจ่ายเงิน
            'settlement_amount' => $this->decimal(15, 2)->notNull()->comment('ยอดเงินที่จ่าย'),
            'settlement_fee' => $this->decimal(15, 2)->defaultValue(0)->comment('ค่าธรรมเนียมการถอนเงิน'),
            'net_settlement_amount' => $this->decimal(15, 2)->notNull()->comment('ยอดสุทธิที่ได้รับจริง'),

            // สถานะและข้อมูลเพิ่มเติม
            'status' => $this->string(50)->comment('สถานะการจ่ายเงิน (COMPLETED, PENDING, FAILED)'),
            'payment_method' => $this->string(50)->comment('วิธีการจ่ายเงิน (BANK_TRANSFER, etc)'),
            'bank_account' => $this->string(100)->comment('บัญชีธนาคารที่รับเงิน'),

            // วันที่
            'payout_time' => $this->dateTime()->notNull()->comment('วันที่จ่ายเงิน'),
            'settlement_period_from' => $this->dateTime()->comment('เริ่มต้นช่วงเวลาที่คำนวณ'),
            'settlement_period_to' => $this->dateTime()->comment('สิ้นสุดช่วงเวลาที่คำนวณ'),

            // ข้อมูลเพิ่มเติม
            'order_count' => $this->integer()->defaultValue(0)->comment('จำนวน orders ในรอบการจ่ายเงิน'),
            'total_sales' => $this->decimal(15, 2)->defaultValue(0)->comment('ยอดขายรวม'),
            'total_commission' => $this->decimal(15, 2)->defaultValue(0)->comment('ค่าคอมมิชชันรวม'),
            'total_transaction_fee' => $this->decimal(15, 2)->defaultValue(0)->comment('ค่าธรรมเนียมรวม'),
            'total_refund' => $this->decimal(15, 2)->defaultValue(0)->comment('เงินคืนรวม'),

            // ข้อมูลอื่นๆ (JSON)
            'details' => $this->text()->comment('รายละเอียดเพิ่มเติมในรูปแบบ JSON'),
            'remark' => $this->string(255)->comment('หมายเหตุ'),

            'created_at' => $this->dateTime()->notNull()->comment('วันที่สร้างข้อมูล'),
            'updated_at' => $this->dateTime()->notNull()->comment('วันที่อัปเดตข้อมูล'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT="เก็บข้อมูลการจ่ายเงิน/ถอนเงินจาก Shopee"');

        // สร้าง unique index สำหรับ settlement_id
        $this->createIndex(
            'idx-shopee_settlement-settlement_id',
            '{{%shopee_settlement}}',
            'settlement_id',
            true
        );

        // สร้าง index สำหรับ channel_id
        $this->createIndex(
            'idx-shopee_settlement-channel_id',
            '{{%shopee_settlement}}',
            'channel_id'
        );

        // สร้าง index สำหรับ shop_id
        $this->createIndex(
            'idx-shopee_settlement-shop_id',
            '{{%shopee_settlement}}',
            'shop_id'
        );

        // สร้าง index สำหรับ payout_time
        $this->createIndex(
            'idx-shopee_settlement-payout_time',
            '{{%shopee_settlement}}',
            'payout_time'
        );

        // สร้าง index สำหรับ status
        $this->createIndex(
            'idx-shopee_settlement-status',
            '{{%shopee_settlement}}',
            'status'
        );

        // สร้าง composite index สำหรับการ query ตามช่วงเวลาและ channel
        $this->createIndex(
            'idx-shopee_settlement-channel_payout',
            '{{%shopee_settlement}}',
            ['channel_id', 'payout_time']
        );

        // สร้าง foreign key ไปยัง table online_channel
        if ($this->db->schema->getTableSchema('{{%online_channel}}') !== null) {
            $this->addForeignKey(
                'fk-shopee_settlement-channel_id',
                '{{%shopee_settlement}}',
                'channel_id',
                '{{%online_channel}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        echo "✓ สร้างตาราง shopee_settlement สำเร็จ\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // ลบ foreign keys ก่อน
        if ($this->db->schema->getTableSchema('{{%online_channel}}') !== null) {
            $this->dropForeignKey('fk-shopee_settlement-channel_id', '{{%shopee_settlement}}');
        }

        // ลบ indexes
        $this->dropIndex('idx-shopee_settlement-channel_payout', '{{%shopee_settlement}}');
        $this->dropIndex('idx-shopee_settlement-status', '{{%shopee_settlement}}');
        $this->dropIndex('idx-shopee_settlement-payout_time', '{{%shopee_settlement}}');
        $this->dropIndex('idx-shopee_settlement-shop_id', '{{%shopee_settlement}}');
        $this->dropIndex('idx-shopee_settlement-channel_id', '{{%shopee_settlement}}');
        $this->dropIndex('idx-shopee_settlement-settlement_id', '{{%shopee_settlement}}');

        // ลบตาราง
        $this->dropTable('{{%shopee_settlement}}');

        echo "✓ ลบตาราง shopee_settlement สำเร็จ\n";
    }
}
