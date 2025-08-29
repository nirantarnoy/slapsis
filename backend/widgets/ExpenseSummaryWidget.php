<?php
namespace backend\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use backend\models\Expense;

class ExpenseSummaryWidget extends Widget
{
    public $period = 'month'; // month, year, week
    public $showChart = true;
    public $template = 'default';

    public function run()
    {
        $data = $this->getData();

        return $this->render('expense-summary/' . $this->template, [
            'data' => $data,
            'period' => $this->period,
            'showChart' => $this->showChart,
        ]);
    }

    protected function getData()
    {
        switch ($this->period) {
            case 'week':
                $startDate = date('Y-m-d', strtotime('-7 days'));
                $endDate = date('Y-m-d');
                break;
            case 'year':
                $startDate = date('Y-01-01');
                $endDate = date('Y-12-31');
                break;
            case 'month':
            default:
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-t');
                break;
        }

        $expenses = Expense::getExpensesByDateRange($startDate, $endDate);
        $categoryData = Expense::getExpensesByCategory($startDate, $endDate);
        $totalAmount = array_sum(array_column($expenses, 'amount'));

        return [
            'expenses' => $expenses,
            'categoryData' => $categoryData,
            'totalAmount' => $totalAmount,
            'count' => count($expenses),
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }
}


