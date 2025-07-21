<?php

namespace backend\models;

use backend\models\Product;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * SalesReportSearch represents the model behind the search form for sales crosstab report.
 */
class SalereportSearch extends Model
{
    public $product_name;
    public $date_from;
    public $date_to;
    public $product_id;
    public $warehouse_id;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_name', 'date_from', 'date_to'], 'safe'],
            [['product_id', 'warehouse_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     */
    public function search($params)
    {
        $this->load($params);

        // Set default date range if not provided
        if (empty($this->date_from)) {
            $this->date_from = date('Y-m-01'); // First day of current month
        }
        if (empty($this->date_to)) {
            $this->date_to = date('Y-m-t'); // Last day of current month
        }

        return $this;
    }

    /**
     * Get crosstab data for the report
     */
    public function getCrosstabData()
    {
//        $sql = "
//            SELECT
//                p.id as product_id,
//                p.name as product_name,
//                p.code as product_code,
//                DAY(jt.trans_date) as day_of_month,
//                DATE(jt.trans_date) as trans_date,
//                SUM(jtl.qty * jtl.line_price) as total_amount,
//                SUM(jtl.qty) as total_qty
//            FROM journal_trans_line jtl
//            INNER JOIN journal_trans jt ON jtl.journal_trans_id = jt.id
//            INNER JOIN product p ON jtl.product_id = p.id
//            WHERE jt.trans_date BETWEEN :date_from AND :date_to
//        ";
        $sql = "
            SELECT 
                p.id as product_id,
                p.name as product_name,
                p.code as product_code,
                DAY(jt.trans_date) as day_of_month,
                DATE(jt.trans_date) as trans_date,
                SUM(jtl.qty * jtl.line_price) as total_amount,
                SUM(jtl.qty) as total_qty
            FROM journal_trans_line jtl
            INNER JOIN journal_trans jt ON jtl.journal_trans_id = jt.id
            INNER JOIN product p ON jtl.product_id = p.id
            WHERE jt.trans_date BETWEEN :date_from AND :date_to
        ";

        $params = [
            ':date_from' => $this->date_from,
            ':date_to' => $this->date_to
        ];

        // Apply filters
        if (!empty($this->product_name)) {
            $sql .= " AND p.name LIKE :product_name";
            $params[':product_name'] = '%' . $this->product_name . '%';
        }

        if (!empty($this->product_id)) {
            $sql .= " AND p.id = :product_id";
            $params[':product_id'] = $this->product_id;
        }

        if (!empty($this->warehouse_id)) {
            $sql .= " AND jtl.warehouse_id = :warehouse_id";
            $params[':warehouse_id'] = $this->warehouse_id;
        }

        $sql .= "
            GROUP BY p.id, p.name, p.code, DAY(jt.trans_date), DATE(jt.trans_date)
            ORDER BY p.name, jt.trans_date
        ";

        return Yii::$app->db->createCommand($sql, $params)->queryAll();
    }

    /**
     * Get date range for columns
     */
    public function getDateRange()
    {
        $dates = [];
        $start = new \DateTime($this->date_from);
        $end = new \DateTime($this->date_to);

        while ($start <= $end) {
            $dates[] = [
                'date' => $start->format('Y-m-d'),
                'day' => $start->format('j'),
                'formatted' => $start->format('d/m')
            ];
            $start->modify('+1 day');
        }

        return $dates;
    }

    /**
     * Transform raw data to crosstab format
     */
    public function transformToCrosstab()
    {
        $rawData = $this->getCrosstabData();
        $dateRange = $this->getDateRange();

        // Group data by product
        $productData = [];
        foreach ($rawData as $row) {
            $productId = $row['product_id'];
            if (!isset($productData[$productId])) {
                $productData[$productId] = [
                    'product_id' => $productId,
                    'product_name' => $row['product_name'],
                    'product_code' => $row['product_code'],
                    'daily_sales' => [],
                    'total_amount' => 0,
                    'total_qty' => 0
                ];
            }

            $productData[$productId]['daily_sales'][$row['trans_date']] = [
                'amount' => $row['total_amount'],
                'qty' => $row['total_qty']
            ];
            $productData[$productId]['total_amount'] += $row['total_amount'];
            $productData[$productId]['total_qty'] += $row['total_qty'];
        }

        // Fill missing dates with zeros and calculate column totals
        $columnTotals = [];
        foreach ($productData as &$product) {
            foreach ($dateRange as $dateInfo) {
                $date = $dateInfo['date'];
                if (!isset($product['daily_sales'][$date])) {
                    $product['daily_sales'][$date] = ['amount' => 0, 'qty' => 0];
                }

                // Calculate column totals
                if (!isset($columnTotals[$date])) {
                    $columnTotals[$date] = ['amount' => 0, 'qty' => 0];
                }
                $columnTotals[$date]['amount'] += $product['daily_sales'][$date]['amount'];
                $columnTotals[$date]['qty'] += $product['daily_sales'][$date]['qty'];
            }
        }

        return [
            'products' => array_values($productData),
            'dateRange' => $dateRange,
            'columnTotals' => $columnTotals
        ];
    }

    /**
     * Get product list for dropdown
     */
    public static function getProductList()
    {
        return Product::find()
            ->select(['id', 'name', 'code'])
            ->orderBy('name')
            ->all();
    }
}