<?php
namespace backend\components;

use Yii;
use backend\models\Expense;

class ExpenseHelper
{
    /**
     * สร้าง backup ข้อมูลค่าใช้จ่าย
     */
    public static function backupExpenses()
    {
        $expenses = Expense::find()->all();
        $backupData = [];

        foreach ($expenses as $expense) {
            $backupData[] = [
                'category' => $expense->category,
                'description' => $expense->description,
                'expense_date' => $expense->expense_date,
                'amount' => $expense->amount,
                'receipt_file' => $expense->receipt_file,
                'status' => $expense->status,
                'created_at' => $expense->created_at,
            ];
        }

        $backupFile = Yii::getAlias('@runtime/expense_backup_' . date('Ymd_His') . '.json');
        file_put_contents($backupFile, json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $backupFile;
    }

    /**
     * ดึงข้อมูลสถิติค่าใช้จ่าย
     */
    public static function getExpenseStatistics($year = null)
    {
        $year = $year ?: date('Y');

        $monthlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $amount = Expense::find()
                ->where(['YEAR(expense_date)' => $year, 'MONTH(expense_date)' => $month])
                ->andWhere(['status' => Expense::STATUS_ACTIVE])
                ->sum('amount') ?: 0;

            $monthlyData[] = [
                'month' => $month,
                'month_name' => Yii::$app->formatter->asDate("$year-$month-01", 'MMMM'),
                'amount' => (float) $amount,
            ];
        }

        return $monthlyData;
    }

    /**
     * ตรวจสอบและล้างไฟล์ใบเสร็จที่ไม่ได้ใช้
     */
    public static function cleanupReceiptFiles()
    {
        $uploadPath = Yii::getAlias('@backend/web/uploads/receipts/');
        if (!is_dir($uploadPath)) {
            return;
        }

        $usedFiles = Expense::find()
            ->select('receipt_file')
            ->where(['not', ['receipt_file' => null]])
            ->andWhere(['!=', 'receipt_file', ''])
            ->column();

        $allFiles = array_diff(scandir($uploadPath), ['.', '..']);
        $unusedFiles = array_diff($allFiles, $usedFiles);

        $deletedCount = 0;
        foreach ($unusedFiles as $file) {
            $filePath = $uploadPath . $file;
            if (is_file($filePath) && unlink($filePath)) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    /**
     * สร้างรายงาน Excel แบบละเอียด
     */
    public static function generateDetailedExcelReport($startDate, $endDate)
    {
        $expenses = Expense::getExpensesByDateRange($startDate, $endDate);
        $categoryData = Expense::getExpensesByCategory($startDate, $endDate);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // Sheet 1: Summary
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('สรุป');

        // Headers
        $sheet1->setCellValue('A1', 'รายงานสรุปค่าใช้จ่าย');
        $sheet1->mergeCells('A1:D1');
        $sheet1->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $sheet1->setCellValue('A3', 'ช่วงเวลา: ' . Yii::$app->formatter->asDate($startDate) . ' ถึง ' . Yii::$app->formatter->asDate($endDate));
        $sheet1->setCellValue('A4', 'ยอดรวม: ' . number_format(array_sum(array_column($expenses, 'amount')), 2) . ' บาท');

        // Category Summary
        $sheet1->setCellValue('A6', 'หมวดหมู่');
        $sheet1->setCellValue('B6', 'จำนวนครั้ง');
        $sheet1->setCellValue('C6', 'จำนวนเงิน');
        $sheet1->setCellValue('D6', 'เปอร์เซ็นต์');

        $row = 7;
        $totalAmount = array_sum(array_column($categoryData, 'total_amount'));
        foreach ($categoryData as $category) {
            $sheet1->setCellValue('A' . $row, $category['category']);
            $sheet1->setCellValue('B' . $row, $category['count']);
            $sheet1->setCellValue('C' . $row, $category['total_amount']);
            $sheet1->setCellValue('D' . $row, round(($category['total_amount'] / max(1, $totalAmount)) * 100, 1) . '%');
            $row++;
        }

        // Style headers
        $sheet1->getStyle('A6:D6')->getFont()->setBold(true);
        $sheet1->getStyle('A6:D6')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFCCCCCC');

        // Sheet 2: Detailed Data
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('รายละเอียด');

        $sheet2->setCellValue('A1', 'วันที่');
        $sheet2->setCellValue('B1', 'หมวดหมู่');
        $sheet2->setCellValue('C1', 'รายละเอียด');
        $sheet2->setCellValue('D1', 'จำนวนเงิน');

        $row = 2;
        foreach ($expenses as $expense) {
            $sheet2->setCellValue('A' . $row, $expense->expense_date);
            $sheet2->setCellValue('B' . $row, $expense->category);
            $sheet2->setCellValue('C' . $row, $expense->description);
            $sheet2->setCellValue('D' . $row, $expense->amount);
            $row++;
        }

        // Style
        $sheet2->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet2->getStyle('A1:D1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFCCCCCC');

        // Auto width
        foreach (['A', 'B', 'C', 'D'] as $col) {
            $sheet1->getColumnDimension($col)->setAutoSize(true);
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}
