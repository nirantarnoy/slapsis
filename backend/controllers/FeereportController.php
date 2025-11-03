<?php
namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use backend\models\Order;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class FeereportController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $request = Yii::$app->request;

        $dateFrom = $request->get('date_from', date('Y-m-01'));
        $dateTo = $request->get('date_to', date('Y-m-d'));
        $channel = $request->get('channel', '');
        $reportType = $request->get('report_type', 'detail');

        $data = [];
        $chartData = [];

        if ($reportType == 'detail') {
            $query = Order::find()
                ->select([
                    'order_id',
                    'order_sn',
                    'channel_id',
                    'product_name',
                    'quantity',
                    'total_amount',
                    'commission_fee',
                    'transaction_fee',
                    'service_fee',
                    'payment_fee',
                    'escrow_amount',
                    'actual_income',
                    'buyer_paid_amount',
                    'seller_discount',
                    'shopee_discount',
                    'original_price',
                    'affiliate_fee',
                    'shipping_cost',
                    'platform_discount',
                    'tax_amount',
                    'revenue_amount',
                    'fee_and_tax_total',
                    'settlement_amount',
                    'referral_fee',
                    'credit_card_fee',
                    'affiliate_ads_fee',
                    'promotion_fee',
                    'logistics_fee',
                    'actual_shipping_fee',
                    'shipping_discount',
                    'customer_shipping_fee',
                    'return_shipping_fee',
                    'vat_amount',
                    'import_tax',
                    'customs_duty',
                    'subtotal_before_discount',
                    'seller_voucher_discount',
                    'platform_voucher_discount',
                    'order_date',
                    'created_at'
                ])
                ->where(['>=', 'order_date', $dateFrom])
                ->andWhere(['<=', 'order_date', $dateTo]);

            if (!empty($channel)) {
                $query->andWhere(['channel_id' => $channel]);
            }

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'pageSize' => 50,
                ],
                'sort' => [
                    'defaultOrder' => [
                        'order_date' => SORT_DESC,
                    ]
                ],
            ]);

            $data = $dataProvider;

        } elseif ($reportType == 'monthly') {
            $query = Order::find()
                ->select([
                    'DATE_FORMAT(order_date, "%Y-%m") as period',
                    'channel_id',
                    'COUNT(*) as total_orders',
                    'SUM(total_amount) as total_sales',
                    'SUM(commission_fee) as total_commission',
                    'SUM(transaction_fee) as total_transaction_fee',
                    'SUM(service_fee) as total_service_fee',
                    'SUM(payment_fee) as total_payment_fee',
                    'SUM(actual_income) as total_income',
                    'SUM(affiliate_fee) as total_affiliate_fee',
                    'SUM(shipping_cost) as total_shipping_cost',
                    'SUM(platform_discount) as total_platform_discount',
                    'SUM(tax_amount) as total_tax',
                    'SUM(settlement_amount) as total_settlement',
                    'SUM(referral_fee) as total_referral_fee',
                    'SUM(credit_card_fee) as total_credit_card_fee',
                    'SUM(affiliate_ads_fee) as total_affiliate_ads_fee',
                    'SUM(promotion_fee) as total_promotion_fee',
                    'SUM(logistics_fee) as total_logistics_fee',
                    'SUM(vat_amount) as total_vat',
                    'SUM(import_tax) as total_import_tax',
                    'SUM(customs_duty) as total_customs_duty',
                ])
                ->where(['>=', 'order_date', $dateFrom])
                ->andWhere(['<=', 'order_date', $dateTo]);

            if (!empty($channel)) {
                $query->andWhere(['channel_id' => $channel]);
            }

            $data = $query->groupBy(['DATE_FORMAT(order_date, "%Y-%m")', 'channel_id'])
                ->orderBy(['period' => SORT_DESC])
                ->asArray()
                ->all();

        } elseif ($reportType == 'yearly') {
            $query = Order::find()
                ->select([
                    'YEAR(order_date) as period',
                    'channel_id',
                    'COUNT(*) as total_orders',
                    'SUM(total_amount) as total_sales',
                    'SUM(commission_fee) as total_commission',
                    'SUM(transaction_fee) as total_transaction_fee',
                    'SUM(service_fee) as total_service_fee',
                    'SUM(payment_fee) as total_payment_fee',
                    'SUM(actual_income) as total_income',
                    'SUM(affiliate_fee) as total_affiliate_fee',
                    'SUM(shipping_cost) as total_shipping_cost',
                    'SUM(platform_discount) as total_platform_discount',
                    'SUM(tax_amount) as total_tax',
                    'SUM(settlement_amount) as total_settlement',
                    'SUM(referral_fee) as total_referral_fee',
                    'SUM(credit_card_fee) as total_credit_card_fee',
                    'SUM(affiliate_ads_fee) as total_affiliate_ads_fee',
                    'SUM(promotion_fee) as total_promotion_fee',
                    'SUM(logistics_fee) as total_logistics_fee',
                    'SUM(vat_amount) as total_vat',
                    'SUM(import_tax) as total_import_tax',
                    'SUM(customs_duty) as total_customs_duty',
                ])
                ->where(['>=', 'order_date', $dateFrom])
                ->andWhere(['<=', 'order_date', $dateTo]);

            if (!empty($channel)) {
                $query->andWhere(['channel_id' => $channel]);
            }

            $data = $query->groupBy(['YEAR(order_date)', 'channel_id'])
                ->orderBy(['period' => SORT_DESC])
                ->asArray()
                ->all();
        }

        // ข้อมูลสำหรับกราฟ
        if (in_array($reportType, ['monthly', 'yearly'])) {
            $chartData = $this->prepareChartData($data, $reportType);
        }

        return $this->render('index', [
            'data' => $data,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'channel' => $channel,
            'reportType' => $reportType,
            'chartData' => $chartData,
        ]);
    }

    protected function prepareChartData($data, $reportType)
    {
        $labels = [];
        $commissionData = [];
        $transactionData = [];
        $serviceData = [];
        $totalIncomeData = [];

        foreach ($data as $row) {
            $labels[] = $row['period'] . ' (CH' . $row['channel_id'] . ')';
            $commissionData[] = floatval($row['total_commission']);
            $transactionData[] = floatval($row['total_transaction_fee']);
            $serviceData[] = floatval($row['total_service_fee']);
            $totalIncomeData[] = floatval($row['total_income']);
        }

        return [
            'labels' => $labels,
            'commission' => $commissionData,
            'transaction' => $transactionData,
            'service' => $serviceData,
            'income' => $totalIncomeData,
        ];
    }

    public function actionExport()
    {
        $request = Yii::$app->request;

        $dateFrom = $request->get('date_from', date('Y-m-01'));
        $dateTo = $request->get('date_to', date('Y-m-d'));
        $channel = $request->get('channel', '');
        $reportType = $request->get('report_type', 'detail');
        $format = $request->get('format', 'excel');

        if ($reportType == 'detail') {
            $query = Order::find()
                ->where(['>=', 'order_date', $dateFrom])
                ->andWhere(['<=', 'order_date', $dateTo]);

            if (!empty($channel)) {
                $query->andWhere(['channel_id' => $channel]);
            }

            $data = $query->asArray()->all();

        } elseif ($reportType == 'monthly') {
            $query = Order::find()
                ->select([
                    'DATE_FORMAT(order_date, "%Y-%m") as period',
                    'channel_id',
                    'COUNT(*) as total_orders',
                    'SUM(total_amount) as total_sales',
                    'SUM(commission_fee) as total_commission',
                    'SUM(transaction_fee) as total_transaction_fee',
                    'SUM(service_fee) as total_service_fee',
                    'SUM(payment_fee) as total_payment_fee',
                    'SUM(actual_income) as total_income',
                ])
                ->where(['>=', 'order_date', $dateFrom])
                ->andWhere(['<=', 'order_date', $dateTo]);

            if (!empty($channel)) {
                $query->andWhere(['channel_id' => $channel]);
            }

            $data = $query->groupBy(['DATE_FORMAT(order_date, "%Y-%m")', 'channel_id'])
                ->orderBy(['period' => SORT_DESC])
                ->asArray()
                ->all();

        } elseif ($reportType == 'yearly') {
            $query = Order::find()
                ->select([
                    'YEAR(order_date) as period',
                    'channel_id',
                    'COUNT(*) as total_orders',
                    'SUM(total_amount) as total_sales',
                    'SUM(commission_fee) as total_commission',
                    'SUM(transaction_fee) as total_transaction_fee',
                    'SUM(service_fee) as total_service_fee',
                    'SUM(payment_fee) as total_payment_fee',
                    'SUM(actual_income) as total_income',
                ])
                ->where(['>=', 'order_date', $dateFrom])
                ->andWhere(['<=', 'order_date', $dateTo]);

            if (!empty($channel)) {
                $query->andWhere(['channel_id' => $channel]);
            }

            $data = $query->groupBy(['YEAR(order_date)', 'channel_id'])
                ->orderBy(['period' => SORT_DESC])
                ->asArray()
                ->all();
        }

        if ($format == 'excel') {
            return $this->exportToExcel($data, $reportType, $dateFrom, $dateTo);
        } else {
            return $this->exportToCsv($data, $reportType, $dateFrom, $dateTo);
        }
    }

    protected function exportToExcel($data, $reportType, $dateFrom, $dateTo)
    {
        $filename = 'fee_report_' . $reportType . '_' . date('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        require_once Yii::getAlias('@vendor/phpoffice/phpspreadsheet/src/Bootstrap.php');

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($reportType == 'detail') {
            $headers = ['Order ID', 'Order SN', 'Channel', 'Product', 'Qty', 'Total', 'Commission', 'Transaction', 'Service', 'Payment', 'Income', 'Date'];
            $sheet->fromArray($headers, NULL, 'A1');

            $row = 2;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, $item['order_id']);
                $sheet->setCellValue('B' . $row, $item['order_sn']);
                $sheet->setCellValue('C' . $row, $item['channel_id'] == 1 ? 'Shopee' : 'TikTok');
                $sheet->setCellValue('D' . $row, $item['product_name']);
                $sheet->setCellValue('E' . $row, $item['quantity']);
                $sheet->setCellValue('F' . $row, $item['total_amount']);
                $sheet->setCellValue('G' . $row, $item['commission_fee']);
                $sheet->setCellValue('H' . $row, $item['transaction_fee']);
                $sheet->setCellValue('I' . $row, $item['service_fee']);
                $sheet->setCellValue('J' . $row, $item['payment_fee']);
                $sheet->setCellValue('K' . $row, $item['actual_income']);
                $sheet->setCellValue('L' . $row, $item['order_date']);
                $row++;
            }
        } else {
            $headers = ['Period', 'Channel', 'Orders', 'Total Sales', 'Commission', 'Transaction', 'Service', 'Payment', 'Total Income'];
            $sheet->fromArray($headers, NULL, 'A1');

            $row = 2;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, $item['period']);
                $sheet->setCellValue('B' . $row, $item['channel_id'] == 1 ? 'Shopee' : 'TikTok');
                $sheet->setCellValue('C' . $row, $item['total_orders']);
                $sheet->setCellValue('D' . $row, $item['total_sales']);
                $sheet->setCellValue('E' . $row, $item['total_commission']);
                $sheet->setCellValue('F' . $row, $item['total_transaction_fee']);
                $sheet->setCellValue('G' . $row, $item['total_service_fee']);
                $sheet->setCellValue('H' . $row, $item['total_payment_fee']);
                $sheet->setCellValue('I' . $row, $item['total_income']);
                $row++;
            }
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    protected function exportToCsv($data, $reportType, $dateFrom, $dateTo)
    {
        $filename = 'fee_report_' . $reportType . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        if ($reportType == 'detail') {
            fputcsv($output, ['Order ID', 'Order SN', 'Channel', 'Product', 'Qty', 'Total', 'Commission', 'Transaction', 'Service', 'Payment', 'Income', 'Date']);

            foreach ($data as $item) {
                fputcsv($output, [
                    $item['order_id'],
                    $item['order_sn'],
                    $item['channel_id'] == 1 ? 'Shopee' : 'TikTok',
                    $item['product_name'],
                    $item['quantity'],
                    $item['total_amount'],
                    $item['commission_fee'],
                    $item['transaction_fee'],
                    $item['service_fee'],
                    $item['payment_fee'],
                    $item['actual_income'],
                    $item['order_date'],
                ]);
            }
        } else {
            fputcsv($output, ['Period', 'Channel', 'Orders', 'Total Sales', 'Commission', 'Transaction', 'Service', 'Payment', 'Total Income']);

            foreach ($data as $item) {
                fputcsv($output, [
                    $item['period'],
                    $item['channel_id'] == 1 ? 'Shopee' : 'TikTok',
                    $item['total_orders'],
                    $item['total_sales'],
                    $item['total_commission'],
                    $item['total_transaction_fee'],
                    $item['total_service_fee'],
                    $item['total_payment_fee'],
                    $item['total_income'],
                ]);
            }
        }

        fclose($output);
        exit;
    }

    public function actionPrint()
    {
        $this->layout = 'print';

        $request = Yii::$app->request;

        $dateFrom = $request->get('date_from', date('Y-m-01'));
        $dateTo = $request->get('date_to', date('Y-m-d'));
        $channel = $request->get('channel', '');
        $reportType = $request->get('report_type', 'detail');

        $data = [];

        if ($reportType == 'detail') {
            $query = Order::find()
                ->where(['>=', 'order_date', $dateFrom])
                ->andWhere(['<=', 'order_date', $dateTo]);

            if (!empty($channel)) {
                $query->andWhere(['channel_id' => $channel]);
            }

            $data = $query->all();

        } elseif ($reportType == 'monthly') {
            $query = Order::find()
                ->select([
                    'DATE_FORMAT(order_date, "%Y-%m") as period',
                    'channel_id',
                    'COUNT(*) as total_orders',
                    'SUM(total_amount) as total_sales',
                    'SUM(commission_fee) as total_commission',
                    'SUM(transaction_fee) as total_transaction_fee',
                    'SUM(service_fee) as total_service_fee',
                    'SUM(payment_fee) as total_payment_fee',
                    'SUM(actual_income) as total_income',
                ])
                ->where(['>=', 'order_date', $dateFrom])
                ->andWhere(['<=', 'order_date', $dateTo]);

            if (!empty($channel)) {
                $query->andWhere(['channel_id' => $channel]);
            }

            $data = $query->groupBy(['DATE_FORMAT(order_date, "%Y-%m")', 'channel_id'])
                ->orderBy(['period' => SORT_DESC])
                ->asArray()
                ->all();

        } elseif ($reportType == 'yearly') {
            $query = Order::find()
                ->select([
                    'YEAR(order_date) as period',
                    'channel_id',
                    'COUNT(*) as total_orders',
                    'SUM(total_amount) as total_sales',
                    'SUM(commission_fee) as total_commission',
                    'SUM(transaction_fee) as total_transaction_fee',
                    'SUM(service_fee) as total_service_fee',
                    'SUM(payment_fee) as total_payment_fee',
                    'SUM(actual_income) as total_income',
                ])
                ->where(['>=', 'order_date', $dateFrom])
                ->andWhere(['<=', 'order_date', $dateTo]);

            if (!empty($channel)) {
                $query->andWhere(['channel_id' => $channel]);
            }

            $data = $query->groupBy(['YEAR(order_date)', 'channel_id'])
                ->orderBy(['period' => SORT_DESC])
                ->asArray()
                ->all();
        }

        return $this->render('print', [
            'data' => $data,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'channel' => $channel,
            'reportType' => $reportType,
        ]);
    }
}