<?php

namespace backend\controllers;

use Yii;
use backend\models\Order;
use backend\models\OrderSearch;
use backend\models\OnlineChannel;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use kartik\mpdf\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OrderController extends Controller
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
                    'sync-orders' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCreate()
    {
        $model = new Order();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'บันทึกข้อมูลเรียบร้อยแล้ว');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'แก้ไขข้อมูลเรียบร้อยแล้ว');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'ลบข้อมูลเรียบร้อยแล้ว');

        return $this->redirect(['index']);
    }

    // Action สำหรับดูรายงาน
    public function actionReport()
    {
        $searchModel = new OrderSearch();
        $reportData = $searchModel->getReportData(Yii::$app->request->queryParams);

        // เตรียมข้อมูลสำหรับกราฟ
        $chartData = $this->prepareChartData($reportData);

        return $this->render('report', [
            'searchModel' => $searchModel,
            'reportData' => $reportData,
            'chartData' => $chartData,
        ]);
    }

    // Export Excel
    public function actionExportExcel()
    {
        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination = false;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = ['เลขที่คำสั่งซื้อ', 'ช่องทางการขาย', 'SKU', 'ชื่อสินค้า', 'จำนวน', 'ราคา', 'ยอดรวม', 'วันที่สั่งซื้อ'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Data
        $row = 2;
        foreach ($dataProvider->models as $model) {
            $sheet->setCellValue('A' . $row, $model->order_id);
            $sheet->setCellValue('B' . $row, $model->channel->name);
            $sheet->setCellValue('C' . $row, $model->sku);
            $sheet->setCellValue('D' . $row, $model->product_name);
            $sheet->setCellValue('E' . $row, $model->quantity);
            $sheet->setCellValue('F' . $row, $model->price);
            $sheet->setCellValue('G' . $row, $model->total_amount);
            $sheet->setCellValue('H' . $row, Yii::$app->formatter->asDatetime($model->order_date, 'php:d/m/Y H:i'));
            $row++;
        }

        // Auto size columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create writer and output
        $writer = new Xlsx($spreadsheet);
        $filename = 'orders_' . date('YmdHis') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    // Export PDF
    public function actionExportPdf()
    {
        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->pagination = false;

        $content = $this->renderPartial('_pdf', [
            'dataProvider' => $dataProvider,
        ]);

        $pdf = new Pdf([
            'mode' => Pdf::MODE_UTF8,
            'format' => Pdf::FORMAT_A4,
            'orientation' => Pdf::ORIENT_LANDSCAPE,
            'destination' => Pdf::DEST_BROWSER,
            'content' => $content,
            'cssFile' => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
            'cssInline' => '.kv-heading-1{font-size:18px}',
            'options' => ['title' => 'รายงานคำสั่งซื้อ'],
            'methods' => [
                'SetHeader' => ['รายงานคำสั่งซื้อ||Generated On: ' . date("r")],
                'SetFooter' => ['|Page {PAGENO}|'],
            ]
        ]);

        return $pdf->render();
    }

    // Sync orders from channels
    public function actionSyncOrders()
    {
        $channelId = Yii::$app->request->post('channel_id');

        try {
            // เรียกใช้ service สำหรับ sync ข้อมูล
            $service = new \backend\services\OrderSyncService();
            $result = $service->syncOrders($channelId);

            Yii::$app->session->setFlash('success',
                "ดึงข้อมูลเรียบร้อยแล้ว จำนวน {$result['count']} รายการ"
            );
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    public function actionSyncShopeeFee()
    {
        $channelId = 1;
        // echo $channelId;return;

        try {
            // เรียกใช้ service สำหรับ sync ข้อมูล
            $service = new \backend\services\OrderSyncService();
            $result = $service->syncShopeeFree($channelId);

            Yii::$app->session->setFlash('success',
                "ดึงข้อมูล Sync Fee เรียบร้อยแล้ว จำนวน {$result['count']} รายการ"
            );
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }
    public function actionSyncShopeeFeeSettlement()
    {
        $channelId = 1;
        // echo $channelId;return;

        try {
            // เรียกใช้ service สำหรับ sync ข้อมูล
            $service = new \backend\services\OrderSyncService();
            $result = $service->syncMonthlyShopeeFees($channelId);

//            Yii::$app->session->setFlash('success',
//                "ดึงข้อมูล Sync Settlement เรียบร้อยแล้ว จำนวน {$result['transaction_count']} รายการ"
//            );
            Yii::$app->session->setFlash('success',
                "ดึงข้อมูล Shopee Sync Settlement เรียบร้อยแล้ว จำนวน {$result['period']['to']} รายการ"
            );
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Sync Monthly Shopee Free เกิดข้อผิดพลาด: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }
    public function actionSyncTiktokFeeSettlement()
    {
        $channelId = 2;
        // echo $channelId;return;

        try {
            // เรียกใช้ service สำหรับ sync ข้อมูล
            $service = new \backend\services\OrderSyncService();
            $result = $service->syncMonthlyTikTokFees($channelId);

//            Yii::$app->session->setFlash('success',
//                "ดึงข้อมูล Sync Settlement เรียบร้อยแล้ว จำนวน {$result['transaction_count']} รายการ"
//            );
            Yii::$app->session->setFlash('success',
                "ดึงข้อมูล Tiktok Sync Settlement เรียบร้อยแล้ว จำนวน {$result['period']['to']} รายการ"
            );
            print_r($result);return;
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Sync Monthly Tiktok Free เกิดข้อผิดพลาด: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = Order::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    private function prepareChartData($reportData)
    {
        $salesByDate = [];
        $salesByChannel = [];

        foreach ($reportData as $data) {
            $date = $data['order_date'];
            $channelName = OnlineChannel::findOne($data['channel_id'])->name;

            // ข้อมูลรายวัน
            if (!isset($salesByDate[$date])) {
                $salesByDate[$date] = 0;
            }
            $salesByDate[$date] += $data['total_sales'];

            // ข้อมูลตามช่องทาง
            if (!isset($salesByChannel[$channelName])) {
                $salesByChannel[$channelName] = 0;
            }
            $salesByChannel[$channelName] += $data['total_sales'];
        }

        return [
            'salesByDate' => $salesByDate,
            'salesByChannel' => $salesByChannel,
        ];
    }
}