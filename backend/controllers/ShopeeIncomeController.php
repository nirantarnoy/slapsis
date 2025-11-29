<?php

namespace backend\controllers;

use Yii;
use backend\models\ShopeeIncomeDetails;
use backend\models\ShopeeIncomeSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use kartik\mpdf\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * ShopeeIncomeController implements the CRUD actions for ShopeeIncomeDetails model.
 */
class ShopeeIncomeController extends Controller
{
    /**
     * Lists all ShopeeIncomeDetails models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ShopeeIncomeSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionReport()
    {
        $searchModel = new ShopeeIncomeSearch();
        $data = $searchModel->getReportData(Yii::$app->request->queryParams);
        
        // Calculate Summary
        $summary = $this->calculateSummary($data);

        return $this->render('report', [
            'searchModel' => $searchModel,
            'data' => $data,
            'summary' => $summary,
        ]);
    }

    public function actionExportExcel()
    {
        $searchModel = new ShopeeIncomeSearch();
        $data = $searchModel->getReportData(Yii::$app->request->queryParams);
        $summary = $this->calculateSummary($data);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Title
        $sheet->setCellValue('A1', 'รายงานรายได้ Shopee');
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
        $filename = 'shopee_income_report_' . date('YmdHis') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    public function actionExportPdf()
    {
        $searchModel = new ShopeeIncomeSearch();
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
            'options' => ['title' => 'Shopee Income Report'],
            'methods' => [
                'SetHeader' => ['Shopee Income Report||Generated On: ' . date("r")],
                'SetFooter' => ['|Page {PAGENO}|'],
            ]
        ]);

        return $pdf->render();
    }

    private function calculateSummary($data)
    {
        $summary = [
            'income' => [
                'Buyer Total Amount' => 0,
                'Original Price' => 0,
                'Shipping Fee Paid by Buyer' => 0,
                'Shopee Shipping Rebate' => 0,
                'Shopee Voucher Code' => 0, 
                'Cost of Goods Sold' => 0,
                'Seller Coin Cash Back' => 0,
            ],
            'expense' => [
                'Commission Fee' => 0,
                'Transaction Fee' => 0,
                'Service Fee' => 0,
                'Seller Return Refund' => 0,
                'Reverse Shipping Fee' => 0,
                'Final Shipping Fee' => 0,
                'Actual Shipping Fee' => 0,
                'Shipping Fee Discount From 3PL' => 0,
                'DRC Adjustable Refund' => 0,
                'Payment Promotion Amount' => 0,
                'Cross Border Tax' => 0,
                'Seller Shipping Discount' => 0, 
                'Seller Voucher Code' => 0, 
            ],
            'total_income' => 0,
            'total_expense' => 0,
            'net_settlement' => 0,
        ];

        foreach ($data as $item) {
            // Mapping based on ShopeeIncomeDetails fields
            
            // Income Side (Positive flows to seller or gross amounts)
            // Note: Shopee API structure is a bit different. 
            // 'escrow_amount' is the final net.
            // We need to categorize fields.
            
            // Let's assume:
            // Income
            $summary['income']['Cost of Goods Sold'] += $item->cost_of_goods_sold; // Product Price
            $summary['income']['Shopee Shipping Rebate'] += $item->shopee_shipping_rebate;
            $summary['income']['Shopee Voucher Code'] += $item->shopee_voucher_code;
            $summary['income']['Seller Coin Cash Back'] += $item->seller_coin_cash_back; // If Shopee bears it?
            
            // Expenses (Deductions)
            $summary['expense']['Commission Fee'] += $item->commission_fee; // Usually negative in API or we subtract positive
            $summary['expense']['Transaction Fee'] += $item->transaction_fee;
            $summary['expense']['Service Fee'] += $item->service_fee;
            $summary['expense']['Seller Return Refund'] += $item->seller_return_refund_amount;
            $summary['expense']['Reverse Shipping Fee'] += $item->reverse_shipping_fee;
            
            // Shipping
            // 'final_shipping_fee' is often the deduction.
            // 'shipping_fee_paid_by_buyer' is income but 'actual_shipping_fee' is cost.
            // Shopee logic: (Price + Buyer Shipping) - (Actual Shipping + Fees) = Escrow
            // But 'cost_of_goods_sold' in API usually means 'Original Price' or 'Deal Price'.
            
            // Let's stick to the fields we have and group them logically.
            // If the value in DB is negative (like fees usually are in Shopee API response), we just sum them.
            // If they are positive but represent a cost, we might need to negate.
            // Based on Shopee API, fees are usually negative.
            
            $summary['expense']['Final Shipping Fee'] += $item->final_shipping_fee;
            $summary['expense']['Seller Shipping Discount'] += $item->seller_shipping_discount; // Seller pays
            $summary['expense']['Seller Voucher Code'] += $item->seller_voucher_code; // Seller pays
            
            // Net
            $summary['net_settlement'] += $item->escrow_amount;
        }

        $summary['total_income'] = array_sum($summary['income']);
        $summary['total_expense'] = array_sum($summary['expense']);

        return $summary;
    }
}
