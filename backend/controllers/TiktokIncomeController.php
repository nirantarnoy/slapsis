<?php

namespace backend\controllers;

use Yii;
use backend\models\TiktokIncomeDetails;
use backend\models\TiktokIncomeSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use kartik\mpdf\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * TiktokIncomeController implements the CRUD actions for TiktokIncomeDetails model.
 */
class TiktokIncomeController extends Controller
{
    /**
     * Lists all TiktokIncomeDetails models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new TiktokIncomeSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionReport()
    {
        $searchModel = new TiktokIncomeSearch();
        $data = $searchModel->getReportData(Yii::$app->request->queryParams);
        // print_r($data);return;
        
        // Calculate Summary
        $summary = $this->calculateSummary($data);
        
        // Debug
        // echo '<pre>';
        // print_r($data[0]->attributes); 
        // print_r($summary);
        // echo '</pre>';
        // exit;

        return $this->render('index', [
            'searchModel' => $searchModel,
            'data' => $data,
            'summary' => $summary,
        ]);
    }

    public function actionExportExcel()
    {
        $searchModel = new TiktokIncomeSearch();
        $data = $searchModel->getReportData(Yii::$app->request->queryParams);
        $summary = $this->calculateSummary($data);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Title
        $sheet->setCellValue('A1', 'รายงานรายได้ TikTok Shop');
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $row = 3;

        // Income Section
        $sheet->setCellValue('A' . $row, 'รายได้ (Income)');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        foreach ($summary['income'] as $label => $amount) {
            $sheet->setCellValue('A' . $row, $label);
            $sheet->setCellValue('C' . $row, $amount);
            $row++;
        }
        
        // Total Income
        $sheet->setCellValue('A' . $row, 'รวมรายได้');
        $sheet->setCellValue('C' . $row, $summary['total_income']);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
        $sheet->getStyle('C' . $row)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $row += 2;

        // Expense Section
        $sheet->setCellValue('A' . $row, 'ค่าใช้จ่าย (Expenses)');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        foreach ($summary['expense'] as $label => $amount) {
            $sheet->setCellValue('A' . $row, $label);
            $sheet->setCellValue('C' . $row, $amount);
            $row++;
        }

        // Total Expense
        $sheet->setCellValue('A' . $row, 'รวมค่าใช้จ่าย');
        $sheet->setCellValue('C' . $row, $summary['total_expense']);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
        $sheet->getStyle('C' . $row)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
        $row += 2;

        // Net Settlement
        $sheet->setCellValue('A' . $row, 'ยอดเงินโอนสุทธิ (Net Settlement)');
        $sheet->setCellValue('C' . $row, $summary['net_settlement']);
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('C' . $row)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);

        // Auto size
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);

        $writer = new Xlsx($spreadsheet);
        $filename = 'tiktok_income_report_' . date('YmdHis') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    public function actionExportPdf()
    {
        $searchModel = new TiktokIncomeSearch();
        $data = $searchModel->getReportData(Yii::$app->request->queryParams);
        $summary = $this->calculateSummary($data);

        $content = $this->renderPartial('_pdf', [
            'summary' => $summary,
            'searchModel' => $searchModel
        ]);

        $pdf = new Pdf([
            'mode' => Pdf::MODE_UTF8,
            'format' => Pdf::FORMAT_A4,
            'orientation' => Pdf::ORIENT_PORTRAIT,
            'destination' => Pdf::DEST_BROWSER,
            'content' => $content,
            'cssFile' => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
            'cssInline' => 'body { font-family: "Garuda"; }',
            'options' => ['title' => 'TikTok Income Report'],
            'methods' => [
                'SetHeader' => ['TikTok Income Report||Generated On: ' . date("r")],
                'SetFooter' => ['|Page {PAGENO}|'],
            ]
        ]);

        return $pdf->render();
    }

    private function calculateSummary($data)
    {
        $summary = [
            'income' => [
                'Gross Sales' => 0,
                'Customer Payment' => 0,
                'Platform Discount' => 0,
                'Shipping Fee Subsidy' => 0,
                'Revenue Amount' => 0,
            ],
            'expense' => [
                'Platform Commission' => 0,
                'Transaction Fee' => 0,
                'Affiliate Commission' => 0,
                'Shipping Cost' => 0,
                'Actual Shipping Fee' => 0,
                'Customer Refund' => 0,
                'Adjustment' => 0,
                'Sales Tax' => 0,
                'Fee & Tax' => 0,
            ],
            'total_income' => 0,
            'total_expense' => 0,
            'net_settlement' => 0,
        ];

        foreach ($data as $item) {
            // Income
            $summary['income']['Gross Sales'] += $item->gross_sales_amount;
            $summary['income']['Customer Payment'] += $item->customer_payment_amount;
            $summary['income']['Platform Discount'] += $item->platform_discount_amount;
            $summary['income']['Shipping Fee Subsidy'] += $item->shipping_fee_subsidy_amount;
            $summary['income']['Revenue Amount'] += $item->revenue_amount;

            // Expense (Add as is, assuming they are negative or we handle sign later)
            // Based on log, expenses are negative.
            $summary['expense']['Platform Commission'] += $item->platform_commission_amount;
            $summary['expense']['Transaction Fee'] += $item->transaction_fee_amount;
            $summary['expense']['Affiliate Commission'] += $item->affiliate_commission_amount;
            $summary['expense']['Shipping Cost'] += $item->shipping_cost_amount;
            $summary['expense']['Actual Shipping Fee'] += $item->actual_shipping_fee_amount;
            $summary['expense']['Customer Refund'] += $item->customer_refund_amount;
            $summary['expense']['Adjustment'] += $item->adjustment_amount;
            $summary['expense']['Sales Tax'] += $item->sales_tax_amount;
            $summary['expense']['Fee & Tax'] += $item->fee_and_tax_amount;

            // Net
            $summary['net_settlement'] += $item->settlement_amount;
        }

        // Calculate totals
        // Note: If expenses are negative in DB, summing them gives negative total.
        // If we want to display "Total Expense" as a positive number (to be subtracted), we might need to abs().
        // But usually in accounting: Income + Expenses (if negative) = Net.
        // Let's just sum them for now.
        $summary['total_income'] = array_sum($summary['income']);
        $summary['total_expense'] = array_sum($summary['expense']);

        return $summary;
    }

    public function actionDebug()
    {
        $channel = \backend\models\OnlineChannel::findOne(['name' => 'Tiktok']);
        echo "<h1>TikTok Income Sync Debug</h1>";
        echo "Channel: " . ($channel ? "Found (ID: {$channel->id})" : '<span style="color:red">Not Found</span>') . "<br>";
        
        if ($channel) {
            $totalOrders = \backend\models\Order::find()->where(['channel_id' => $channel->id])->count();
            echo "Total Orders in DB: <strong>$totalOrders</strong><br>";
            
            $syncedCount = TiktokIncomeDetails::find()->count();
            echo "Already Synced Orders: <strong>$syncedCount</strong><br>";
            
            // Check pending using PHP logic
            $syncedIds = TiktokIncomeDetails::find()->select('order_id')->column();
            $syncedMap = array_flip($syncedIds);
            
            $allOrders = \backend\models\Order::find()
                ->select('order_id')
                ->where(['channel_id' => $channel->id])
                ->andWhere(['IS NOT', 'order_id', null])
                ->column();
                
            $pendingOrders = [];
            foreach ($allOrders as $oid) {
                $parts = explode('_', $oid);
                $pureId = $parts[0];
                if (!isset($syncedMap[$pureId])) {
                    $pendingOrders[] = $oid;
                }
            }
                
            $pendingCount = count($pendingOrders);
            echo "Pending Orders to Sync (Real): <strong>$pendingCount</strong><br>";
            
            if ($pendingCount > 0) {
                $toSync = array_slice($pendingOrders, 0, 5);
                echo "<hr><h3>Testing Sync for first 5 pending orders:</h3>";
                
                $service = new \backend\services\TiktokIncomeService();
                
                foreach ($toSync as $order_id) {
                    $parts = explode('_', $order_id);
                    $actualOrderId = $parts[0];
                    
                    echo "Syncing Order: <strong>{$order_id}</strong> (Actual ID: $actualOrderId) ... ";
                    
                    try {
                        $result = $service->syncOrderIncome($actualOrderId, $order_id);
                        if ($result) {
                            echo "<span style='color:green'>SUCCESS</span>";
                        } else {
                            echo "<span style='color:red'>FAILED</span>";
                        }
                    } catch (\Exception $e) {
                        echo "<span style='color:red'>ERROR: " . $e->getMessage() . "</span>";
                    }
                    echo "<br>";
                }
            } else {
                echo "<hr><h3 style='color:green'>All orders are synced!</h3>";
            }
        }
    }
}
