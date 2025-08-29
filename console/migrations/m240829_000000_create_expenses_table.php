<?php
/**
 * Migration สำหรับสร้างตาราง expenses
 * คำสั่งรัน: php yii migrate/create create_expenses_table
 */

use yii\db\Migration;

class m240829_000000_create_expenses_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%expenses}}', [
            'id' => $this->primaryKey(),
            'category' => $this->string(100)->notNull()->comment('หัวข้อการบันทึก'),
            'description' => $this->text()->comment('รายละเอียด'),
            'expense_date' => $this->date()->notNull()->comment('วันที่'),
            'amount' => $this->decimal(15, 2)->notNull()->comment('จำนวนเงิน'),
            'receipt_file' => $this->string(255)->comment('ไฟล์ใบเสร็จ'),
            'status' => $this->string(20)->defaultValue('active')->comment('สถานะ'),
            'created_by' => $this->integer()->comment('ผู้สร้าง'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // สร้าง index
        $this->createIndex('idx_expenses_date', '{{%expenses}}', 'expense_date');
        $this->createIndex('idx_expenses_category', '{{%expenses}}', 'category');
        $this->createIndex('idx_expenses_amount', '{{%expenses}}', 'amount');

        // Foreign key ถ้ามี user table
        // $this->addForeignKey('fk_expenses_user', '{{%expenses}}', 'created_by', '{{%user}}', 'id');
    }

    public function safeDown()
    {
        $this->dropTable('{{%expenses}}');
    }
}