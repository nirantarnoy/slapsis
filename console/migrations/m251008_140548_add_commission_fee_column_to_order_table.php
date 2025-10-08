<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%order}}`.
 */
class m251008_140548_add_commission_fee_column_to_order_table extends Migration
{
    public function safeUp()
    {
        // เพิ่มคอลัมน์ค่าธรรมเนียมต่างๆ
        $this->addColumn('{{%order}}', 'commission_fee',
            $this->decimal(15, 2)->defaultValue(0)->comment('ค่าคอมมิชชัน Shopee')
        );

        $this->addColumn('{{%order}}', 'transaction_fee',
            $this->decimal(15, 2)->defaultValue(0)->comment('ค่าธรรมเนียมธุรกรรม')
        );

        $this->addColumn('{{%order}}', 'service_fee',
            $this->decimal(15, 2)->defaultValue(0)->comment('ค่าบริการ')
        );

        $this->addColumn('{{%order}}', 'payment_fee',
            $this->decimal(15, 2)->defaultValue(0)->comment('ค่าธรรมเนียมการชำระเงิน')
        );

        $this->addColumn('{{%order}}', 'escrow_amount',
            $this->decimal(15, 2)->defaultValue(0)->comment('ยอดเงินที่เก็บไว้ (escrow)')
        );

        $this->addColumn('{{%order}}', 'actual_income',
            $this->decimal(15, 2)->defaultValue(0)->comment('รายได้สุทธิที่ได้รับจริง')
        );

        $this->addColumn('{{%order}}', 'buyer_paid_amount',
            $this->decimal(15, 2)->defaultValue(0)->comment('ยอดที่ลูกค้าจ่ายจริง')
        );

        $this->addColumn('{{%order}}', 'seller_discount',
            $this->decimal(15, 2)->defaultValue(0)->comment('ส่วนลดที่ร้านรับผิดชอบ')
        );

        $this->addColumn('{{%order}}', 'shopee_discount',
            $this->decimal(15, 2)->defaultValue(0)->comment('ส่วนลดที่ Shopee รับผิดชอบ')
        );

        $this->addColumn('{{%order}}', 'original_price',
            $this->decimal(15, 2)->defaultValue(0)->comment('ราคาเดิมก่อนส่วนลด')
        );

        // สร้าง index สำหรับการ query
        $this->createIndex(
            'idx-order-actual_income',
            '{{%order}}',
            'actual_income'
        );

        $this->createIndex(
            'idx-order-commission_fee',
            '{{%order}}',
            'commission_fee'
        );

        echo "✓ เพิ่มฟิลด์ค่าธรรมเนียมในตาราง order สำเร็จ\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // ลบ indexes
        $this->dropIndex('idx-order-commission_fee', '{{%order}}');
        $this->dropIndex('idx-order-actual_income', '{{%order}}');

        // ลบคอลัมน์
        $this->dropColumn('{{%order}}', 'original_price');
        $this->dropColumn('{{%order}}', 'shopee_discount');
        $this->dropColumn('{{%order}}', 'seller_discount');
        $this->dropColumn('{{%order}}', 'buyer_paid_amount');
        $this->dropColumn('{{%order}}', 'actual_income');
        $this->dropColumn('{{%order}}', 'escrow_amount');
        $this->dropColumn('{{%order}}', 'payment_fee');
        $this->dropColumn('{{%order}}', 'service_fee');
        $this->dropColumn('{{%order}}', 'transaction_fee');
        $this->dropColumn('{{%order}}', 'commission_fee');

        echo "✓ ลบฟิลด์ค่าธรรมเนียมจากตาราง order สำเร็จ\n";
    }
}
