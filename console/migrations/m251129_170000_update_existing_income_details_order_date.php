<?php

use yii\db\Migration;

/**
 * Class m251129_170000_update_existing_income_details_order_date
 */
class m251129_170000_update_existing_income_details_order_date extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Update Shopee
        // Assuming order_sn matches
        $sqlShopee = "
            UPDATE shopee_income_details s
            INNER JOIN `order` o ON s.order_sn = o.order_sn
            SET s.order_date = o.order_date
            WHERE s.order_date IS NULL
        ";
        $this->execute($sqlShopee);

        // Update TikTok
        // TikTok order_id in income details is the pure ID (e.g. 576...)
        // Order table order_id might be pure or combined (e.g. 576..._123...)
        // We join by checking if order.order_id starts with income.order_id
        $sqlTiktok = "
            UPDATE tiktok_income_details t
            INNER JOIN `order` o ON o.order_id LIKE CONCAT(t.order_id, '%')
            SET t.order_date = o.order_date
            WHERE t.order_date IS NULL
        ";
        $this->execute($sqlTiktok);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m251129_170000_update_existing_income_details_order_date cannot be reverted.\n";
        return false;
    }
}
