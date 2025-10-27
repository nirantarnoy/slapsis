<?php

use yii\db\Migration;

class m251027_151323_remove_order_sn_foreign_key extends Migration
{
    public function safeUp()
    {
        // เช็คว่า foreign key มีอยู่จริงหรือไม่ก่อนลบ
        $foreignKeys = $this->db->schema->getTableSchema('{{%shopee_transaction}}')->foreignKeys;

        foreach ($foreignKeys as $fkName => $fkData) {
            if (isset($fkData['order_sn'])) {
                $this->dropForeignKey($fkName, '{{%shopee_transaction}}');
                echo "✓ ลบ foreign key {$fkName} สำเร็จ\n";
            }
        }

        echo "✓ Transaction table สามารถบันทึก order_sn ได้โดยไม่ต้องเช็ค foreign key\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // สร้าง foreign key กลับ (ถ้าต้องการ rollback)
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

        echo "✓ สร้าง foreign key กลับแล้ว\n";
    }
}
