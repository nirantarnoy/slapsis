<?php

namespace backend\controllers;

use backend\models\Product;
use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use backend\models\SalereportSearch;


/**
 * SalesReportController implements the CRUD actions for Sales Report.
 */
class SalereportController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Display crosstab sales report
     * @return mixed
     */
    public function actionCrosstab()
    {
        $searchModel = new SalereportSearch();
        $searchModel->load(Yii::$app->request->queryParams);
        $searchModel->search(Yii::$app->request->queryParams);

        // Get crosstab data
        $crosstabData = $searchModel->transformToCrosstab();

        // Get product list for filter dropdown
        $productList = ArrayHelper::map(
            Product::find()->orderBy('name')->all(),
            'id',
            function($model) {
                return $model->code . ' - ' . $model->name;
            }
        );

        return $this->render('crosstab', [
            'searchModel' => $searchModel,
            'crosstabData' => $crosstabData,
            'productList' => $productList,
        ]);
    }

    /**
     * Export crosstab report to Excel
     */
    public function actionExportExcel()
    {
        $searchModel = new SalereportSearch();
        $searchModel->load(Yii::$app->request->queryParams);
        $searchModel->search(Yii::$app->request->queryParams);

        $crosstabData = $searchModel->transformToCrosstab();

        // Set headers for Excel download
        $filename = 'sales_report_' . date('Y-m-d_H-i-s') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Header row
        $headers = ['รหัสสินค้า', 'ชื่อสินค้า'];
        foreach ($crosstabData['dateRange'] as $dateInfo) {
            $headers[] = $dateInfo['formatted'];
        }
        $headers[] = 'รวม';
        fputcsv($output, $headers);

        // Data rows
        foreach ($crosstabData['products'] as $product) {
          //  $row = [$product['product_code'], $product['product_name']];
            $row = [$product['product_name']];
            foreach ($crosstabData['dateRange'] as $dateInfo) {
                $amount = $product['daily_sales'][$dateInfo['date']]['qty'];
                $row[] = number_format($amount, 2);
            }
            $row[] = number_format($product['total_qty'], 2);
            fputcsv($output, $row);
        }

        // Total row
        $totalRow = ['', 'รวมทั้งหมด'];
        foreach ($crosstabData['dateRange'] as $dateInfo) {
            $totalRow[] = number_format($crosstabData['columnTotals'][$dateInfo['date']]['qty'], 2);
        }
        // Grand total
        $grandTotal = array_sum(array_column($crosstabData['products'], 'total_qty'));
        $totalRow[] = number_format($grandTotal, 2);
        fputcsv($output, $totalRow);

        fclose($output);
        exit;
    }

    /**
     * Print crosstab report
     */
    public function actionPrint()
    {
        $searchModel = new SalereportSearch();
        $searchModel->load(Yii::$app->request->queryParams);
        $searchModel->search(Yii::$app->request->queryParams);

        $crosstabData = $searchModel->transformToCrosstab();

        $this->layout = 'print'; // Use print layout

        return $this->render('print', [
            'searchModel' => $searchModel,
            'crosstabData' => $crosstabData,
        ]);
    }
}