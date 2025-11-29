<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use backend\models\ShopeeIncomeDetails;
use backend\models\TiktokIncomeDetails;
use yii\helpers\ArrayHelper;

class IncomeCompareController extends Controller
{
    public function actionIndex()
    {
        $startDate = Yii::$app->request->get('start_date', date('Y-m-01'));
        $endDate = Yii::$app->request->get('end_date', date('Y-m-d'));

        // Fetch Data
        $shopeeData = ShopeeIncomeDetails::find()
            ->where(['between', 'order_date', $startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->all();

        $tiktokData = TiktokIncomeDetails::find()
            ->where(['between', 'order_date', $startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->all();

        // Process Data
        $shopeeSummary = $this->processShopeeData($shopeeData);
        $tiktokSummary = $this->processTiktokData($tiktokData);

        // Prepare Chart Data
        $chartData = $this->prepareChartData($shopeeSummary['daily'], $tiktokSummary['daily']);

        return $this->render('index', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'shopeeSummary' => $shopeeSummary,
            'tiktokSummary' => $tiktokSummary,
            'chartData' => $chartData,
        ]);
    }

    private function processShopeeData($data)
    {
        $totalIncome = 0;
        $totalExpense = 0;
        $daily = [];

        foreach ($data as $item) {
            $date = date('Y-m-d', strtotime($item->order_date));
            
            // Income
            $income = $item->cost_of_goods_sold + 
                      $item->shopee_shipping_rebate + 
                      $item->shopee_voucher_code + 
                      $item->seller_coin_cash_back;

            // Expense
            $expense = $item->commission_fee + 
                       $item->transaction_fee + 
                       $item->service_fee + 
                       $item->seller_return_refund_amount + 
                       $item->reverse_shipping_fee + 
                       $item->final_shipping_fee + 
                       $item->seller_shipping_discount + 
                       $item->seller_voucher_code;

            $totalIncome += $income;
            $totalExpense += $expense;

            if (!isset($daily[$date])) {
                $daily[$date] = ['income' => 0, 'expense' => 0];
            }
            $daily[$date]['income'] += $income;
            $daily[$date]['expense'] += $expense;
        }

        return [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'daily' => $daily
        ];
    }

    private function processTiktokData($data)
    {
        $totalIncome = 0;
        $totalExpense = 0;
        $daily = [];

        foreach ($data as $item) {
            $date = date('Y-m-d', strtotime($item->order_date));

            // Income
            $income = $item->gross_sales_amount + 
                      $item->customer_payment_amount + 
                      $item->platform_discount_amount + 
                      $item->shipping_fee_subsidy_amount + 
                      $item->revenue_amount;

            // Expense
            $expense = $item->platform_commission_amount + 
                       $item->transaction_fee_amount + 
                       $item->affiliate_commission_amount + 
                       $item->shipping_cost_amount + 
                       $item->actual_shipping_fee_amount + 
                       $item->customer_refund_amount + 
                       $item->adjustment_amount + 
                       $item->sales_tax_amount + 
                       $item->fee_and_tax_amount;

            $totalIncome += $income;
            $totalExpense += $expense;

            if (!isset($daily[$date])) {
                $daily[$date] = ['income' => 0, 'expense' => 0];
            }
            $daily[$date]['income'] += $income;
            $daily[$date]['expense'] += $expense;
        }

        return [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'daily' => $daily
        ];
    }

    private function prepareChartData($shopeeDaily, $tiktokDaily)
    {
        $dates = array_unique(array_merge(array_keys($shopeeDaily), array_keys($tiktokDaily)));
        sort($dates);

        $shopeeIncomeSeries = [];
        $shopeeExpenseSeries = [];
        $tiktokIncomeSeries = [];
        $tiktokExpenseSeries = [];

        foreach ($dates as $date) {
            $shopeeIncomeSeries[] = isset($shopeeDaily[$date]) ? $shopeeDaily[$date]['income'] : 0;
            $shopeeExpenseSeries[] = isset($shopeeDaily[$date]) ? abs($shopeeDaily[$date]['expense']) : 0; // Use abs for chart visualization
            
            $tiktokIncomeSeries[] = isset($tiktokDaily[$date]) ? $tiktokDaily[$date]['income'] : 0;
            $tiktokExpenseSeries[] = isset($tiktokDaily[$date]) ? abs($tiktokDaily[$date]['expense']) : 0;
        }

        return [
            'categories' => $dates,
            'series' => [
                ['name' => 'Shopee Income', 'data' => $shopeeIncomeSeries],
                ['name' => 'Shopee Expense', 'data' => $shopeeExpenseSeries],
                ['name' => 'TikTok Income', 'data' => $tiktokIncomeSeries],
                ['name' => 'TikTok Expense', 'data' => $tiktokExpenseSeries],
            ]
        ];
    }
}
