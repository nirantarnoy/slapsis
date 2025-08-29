<?php
namespace backend\controllers;

use Yii;
use backend\models\Expense;
use backend\models\ExpenseSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\helpers\Html;
use kartik\mpdf\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use yii\web\Response;

class ExpenseController extends Controller
{
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

    public function actionIndex()
    {
        $searchModel = new ExpenseSearch();
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
        $model = new Expense();

        if ($model->load(Yii::$app->request->post())) {
            $model->receiptUpload = UploadedFile::getInstance($model, 'receiptUpload');

            if ($model->validate()) {
                $model->upload();
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', 'บันทึกค่าใช้จ่ายเรียบร้อยแล้ว');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $oldReceiptFile = $model->receipt_file;

        if ($model->load(Yii::$app->request->post())) {
            $model->receiptUpload = UploadedFile::getInstance($model, 'receiptUpload');

            if ($model->validate()) {
                if ($model->receiptUpload) {
                    $model->upload();
                } else {
                    $model->receipt_file = $oldReceiptFile;
                }

                if ($model->save()) {
                    Yii::$app->session->setFlash('success', 'แก้ไขข้อมูลเรียบร้อยแล้ว');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            }
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

    public function actionReport()
    {
        $startDate = Yii::$app->request->get('start_date', date('Y-m-01'));
        $endDate = Yii::$app->request->get('end_date', date('Y-m-t'));

        $expenses = Expense::getExpensesByDateRange($startDate, $endDate);
        $categoryData = Expense::getExpensesByCategory($startDate, $endDate);

        $totalAmount = array_sum(array_column($expenses, 'amount'));

        return $this->render('report', [
            'expenses' => $expenses,
            'categoryData' => $categoryData,
            'totalAmount' => $totalAmount,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    public function actionExportPdf()
    {
        $startDate = Yii::$app->request->get('start_date', date('Y-m-01'));
        $endDate = Yii::$app->request->get('end_date', date('Y-m-t'));

        $expenses = Expense::getExpensesByDateRange($startDate, $endDate);
        $categoryData = Expense::getExpensesByCategory($startDate, $endDate);
        $totalAmount = array_sum(array_column($expenses, 'amount'));

        $content = $this->renderPartial('_pdf_report', [
            'expenses' => $expenses,
            'categoryData' => $categoryData,
            'totalAmount' => $totalAmount,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        $pdf = new Pdf([
            'mode' => Pdf::MODE_UTF8,
            'format' => Pdf::FORMAT_A4,
            'orientation' => Pdf::ORIENT_PORTRAIT,
            'destination' => Pdf::DEST_BROWSER,
            'content' => $content,
            'options' => ['title' => 'รายงานค่าใช้จ่าย'],
            'methods' => [
                'SetHeader' => ['รายงานค่าใช้จ่าย||Generated: ' . date('d/m/Y H:i:s')],
                'SetFooter' => ['|Page {PAGENO}|'],
            ]
        ]);

        return $pdf->render();
    }

    public function actionExportExcel()
    {
        $startDate = Yii::$app->request->get('start_date', date('Y-m-01'));
        $endDate = Yii::$app->request->get('end_date', date('Y-m-t'));

        $expenses = Expense::getExpensesByDateRange($startDate, $endDate);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $sheet->setCellValue('A1', 'วันที่');
        $sheet->setCellValue('B1', 'หัวข้อ');
        $sheet->setCellValue('C1', 'รายละเอียด');
        $sheet->setCellValue('D1', 'จำนวนเงิน');

        // Data
        $row = 2;
        foreach ($expenses as $expense) {
            $sheet->setCellValue('A' . $row, $expense->expense_date);
            $sheet->setCellValue('B' . $row, $expense->category);
            $sheet->setCellValue('C' . $row, $expense->description);
            $sheet->setCellValue('D' . $row, $expense->amount);
            $row++;
        }

        // Total
        $sheet->setCellValue('C' . $row, 'รวมทั้งหมด');
        $sheet->setCellValue('D' . $row, '=SUM(D2:D' . ($row - 1) . ')');

        // Style headers
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle('A1:D1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFCCCCCC');

        // Auto width
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'expense_report_' . date('Ymd_His') . '.xlsx';

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->add('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        Yii::$app->response->headers->add('Content-Disposition', 'attachment; filename="' . $filename . '"');

        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }

    protected function findModel($id)
    {
        if (($model = Expense::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}