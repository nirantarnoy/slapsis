<?php

use yii\db\Migration;

/**
 * Class m251129_154500_add_order_date_column_to_income_details_tables
 */
class m251129_154500_add_order_date_column_to_income_details_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('shopee_income_details', 'order_date', $this->dateTime()->after('order_sn'));
        $this->addColumn('tiktok_income_details', 'order_date', $this->dateTime()->after('order_id'));
        
        // Add index for better performance
        $this->createIndex('idx-shopee_income_details-order_date', 'shopee_income_details', 'order_date');
        $this->createIndex('idx-tiktok_income_details-order_date', 'tiktok_income_details', 'order_date');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-tiktok_income_details-order_date', 'tiktok_income_details');
        $this->dropIndex('idx-shopee_income_details-order_date', 'shopee_income_details');
        
        $this->dropColumn('shopee_income_details', 'order_date');
        $this->dropColumn('tiktok_income_details', 'order_date');
    }
}
