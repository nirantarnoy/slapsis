<?php
namespace backend\services;

use backend\models\ShopeeSettlement;
use backend\models\ShopeeTransaction;
use Yii;
use backend\models\Order;
use backend\models\OnlineChannel;
use backend\models\ShopeeToken;
use backend\models\TiktokToken;
use yii\base\Exception;
use GuzzleHttp\Client;
use yii\helpers\Json;

class NewTestSyncShopeeService
{
    private $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }

    /**
     * Refresh Shopee access token
     * @param ShopeeToken $tokenModel
     * @return bool
     */
    private function refreshShopeeToken($tokenModel)
    {
        try {
            $partner_id = 2012399; // ใส่ partner_id จริง
            $partner_key = 'shpk72476151525864414e4b6e475449626679624f695a696162696570417043'; // ใส่ partner_key จริง
            $refresh_token = $tokenModel->refresh_token;
            $shop_id = $tokenModel->shop_id;

            $timestamp = time();
            $path = "/api/v2/auth/access_token/get";

            // ✅ แก้ base_string ให้ถูกต้อง (ไม่รวม shop_id และ refresh_token)
            $base_string = $partner_id . $path . $timestamp;
            $sign = hash_hmac('sha256', $base_string, $partner_key);

            // ✅ แยก partner_id และ timestamp ไปเป็น query parameters
            $queryParams = [
                'partner_id' => $partner_id,
                'timestamp' => $timestamp,
                'sign' => $sign,
            ];

            $jsonPayload = [
                'shop_id' => (int)$shop_id,
                'partner_id' => (int)$partner_id,
                'refresh_token' => $refresh_token,
            ];

            // ✅ เปลี่ยนจาก form_params เป็น json
            $response = $this->httpClient->post('https://partner.shopeemobile.com' . $path, [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'query' => $queryParams,
                'json' => $jsonPayload,
                'timeout' => 30
            ]);

            Yii::info('Http token refresh is: ' . $response->getStatusCode());

            // ✅ เช็ค HTTP status code
            if ($response->getStatusCode() !== 200) {
                Yii::error('HTTP Error: ' . $response->getStatusCode(), __METHOD__);
                return false;
            }

            $body = $response->getBody()->getContents();
            $data = Json::decode($body);

            // ✅ เพิ่มการเช็ค error response
            if (isset($data['error'])) {
                if (!empty($data['error'])) {
                    Yii::error("Shopee API Error: {$data['error']} - " . ($data['message'] ?? 'Unknown error'), __METHOD__);
                    return false;
                }

            }

            if (isset($data['access_token'])) {
                $expiresAt = date('Y-m-d H:i:s', time() + (int)($data['expire_in'] ?? 14400));

                $tokenModel->access_token = $data['access_token'];
                $tokenModel->refresh_token = $data['refresh_token'];
                $tokenModel->expires_at = $expiresAt;
                $tokenModel->updated_at = date('Y-m-d H:i:s');

                if ($tokenModel->save()) {
                    Yii::info("Token refreshed successfully for shop_id: $shop_id", __METHOD__);
                    return true;
                } else {
                    Yii::error('Failed to save token model: ' . Json::encode($tokenModel->errors), __METHOD__);
                    return false;
                }
            } else {
                Yii::error('No access_token in response: ' . $body, __METHOD__);
                return false;
            }

        } catch (\Exception $e) {
            Yii::error('Failed to refresh Shopee token: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    private function syncShopeeTransactionFeesV2($channel, $fromTime, $toTime)
    {
        $channel_id = is_object($channel) ? $channel->id : (int)$channel;

        if (is_int($channel)) {
            $channel = OnlineChannel::findOne($channel_id);
            if (!$channel) {
                Yii::error("Channel not found: $channel_id", __METHOD__);
                return 0;
            }
        }

        $tokenModel = ShopeeToken::find()
            ->where(['status' => 'active'])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if (!$tokenModel) {
            Yii::warning('No active Shopee token found', __METHOD__);
            return 0;
        }

        // Check token expiry
        if (strtotime($tokenModel->expires_at) < time()) {
            if (!$this->refreshShopeeToken($tokenModel)) {
                Yii::warning('Failed to refresh Shopee token', __METHOD__);
                return 0;
            }
        }

        $partner_id = 2012399;
        $partner_key = 'shpk72476151525864414e4b6e475449626679624f695a696162696570417043';
        $shop_id = $tokenModel->shop_id;
        $access_token = $tokenModel->access_token;

        Yii::info("=== Syncing Shopee Wallet Transactions (V2) ===", __METHOD__);
        Yii::info("Period: " . date('Y-m-d H:i:s', $fromTime) . " to " . date('Y-m-d H:i:s', $toTime), __METHOD__);
        Yii::info("Shop ID: $shop_id", __METHOD__);

        $totalCount = 0;
        $page_no = 1;
        $page_size = 100; // Max 100 per page

        try {
            do {
                $timestamp = time();

                // ✅ Use GET method as per Shopee API documentation
                $path = "/api/v2/payment/get_wallet_transaction_list";

                // Generate sign
                $base_string = $partner_id . $path . $timestamp . $access_token . $shop_id;
                $sign = hash_hmac('sha256', $base_string, $partner_key);

                // ✅ All parameters in query string for GET request
                $params = [
                    'partner_id' => (int)$partner_id,
                    'shop_id' => (int)$shop_id,
                    'sign' => $sign,
                    'timestamp' => $timestamp,
                    'access_token' => $access_token,
                    'transaction_time_from' => (int)$fromTime,
                    'transaction_time_to' => (int)$toTime,
                    'page_no' => $page_no,
                    'page_size' => $page_size,
                ];

                Yii::info("Fetching page $page_no (size: $page_size)", __METHOD__);
                Yii::debug("Request params: " . Json::encode($params), __METHOD__);

                // ✅ Use GET method (not POST)
                $response = $this->httpClient->get('https://partner.shopeemobile.com' . $path, [
                    'query' => $params,
                    'timeout' => 30,
                    'http_errors' => false,
                ]);

                $statusCode = $response->getStatusCode();
                $rawBody = (string)$response->getBody();

                Yii::info("Response Status: $statusCode", __METHOD__);
                Yii::debug("Response body: " . substr($rawBody, 0, 1000), __METHOD__);

                if ($statusCode !== 200) {
                    Yii::error("HTTP Error: $statusCode", __METHOD__);
                    Yii::error("Response: $rawBody", __METHOD__);
                    break;
                }

                $data = Json::decode($rawBody);

                // Check API error
                if (isset($data['error']) && !empty($data['error'])) {
                    Yii::error("Shopee API Error: {$data['error']}", __METHOD__);
                    if (isset($data['message'])) {
                        Yii::error("Error message: {$data['message']}", __METHOD__);
                    }
                    break;
                }

                // Get transaction list
                $response_data = $data['response'] ?? [];
                $transactionList = $response_data['transaction_list'] ?? [];
                $more = $response_data['more'] ?? false;
                $total = $response_data['total'] ?? 0;

                if (empty($transactionList)) {
                    Yii::info('No more transactions found', __METHOD__);
                    break;
                }

                Yii::info("Processing " . count($transactionList) . " transactions from page $page_no", __METHOD__);

                // Process each transaction
                $pageSuccess = 0;
                $pageFail = 0;

                foreach ($transactionList as $transaction) {
                    if ($this->processShopeeWalletTransaction($channel_id, $transaction, $shop_id)) {
                        $totalCount++;
                        $pageSuccess++;
                    } else {
                        $pageFail++;
                    }
                }

                Yii::info("Page $page_no result: Success=$pageSuccess, Fail=$pageFail", __METHOD__);

                // Check if has more pages
                if (!$more) {
                    Yii::info("No more pages (total processed: $totalCount/$total)", __METHOD__);
                    break;
                }

                $page_no++;
                usleep(300000); // 0.3 second delay

            } while (true);

            Yii::info("✓ Total synced: $totalCount transactions", __METHOD__);

        } catch (\Exception $e) {
            Yii::error('Shopee Transaction API error: ' . $e->getMessage(), __METHOD__);
            Yii::error('Stack trace: ' . $e->getTraceAsString(), __METHOD__);
        }

        return $totalCount;
    }

    private function processShopeeWalletTransaction($channel_id, $transaction, $shop_id)
    {
        try {
            $transaction_id = $transaction['transaction_id'] ?? null;
            if (empty($transaction_id)) {
                Yii::warning("Missing transaction_id", __METHOD__);
                return false;
            }

            $transaction_id = (string)$transaction_id;

            // Check if exists
            $existing = ShopeeTransaction::findOne(['transaction_id' => $transaction_id]);
            if ($existing) {
                Yii::debug("Transaction exists: $transaction_id", __METHOD__);
                return false;
            }

            $feeTransaction = new ShopeeTransaction();
            $feeTransaction->transaction_id = $transaction_id;
            $feeTransaction->channel_id = $channel_id;
            $feeTransaction->shop_id = (string)$shop_id;

            // Transaction type
            $transaction_type = $transaction['transaction_type'] ?? 'UNKNOWN';
            $feeTransaction->transaction_type = (string)$transaction_type;

            // Reason - use description if reason is empty
            $reason = $transaction['reason'] ?? '';
            if (empty($reason)) {
                $reason = $transaction['description'] ?? $transaction_type;
            }
            $feeTransaction->reason = (string)$reason;

            // Amount
            $amount = (float)($transaction['amount'] ?? 0);
            $feeTransaction->amount = $amount;

            // Current balance
            $feeTransaction->current_balance = (float)($transaction['current_balance'] ?? 0);

            // Status
            $feeTransaction->status = isset($transaction['status'])
                ? (string)$transaction['status']
                : 'COMPLETED';

            // Order reference - VALIDATE BEFORE SETTING
            $order_sn = $transaction['order_sn'] ?? $transaction['order_id'] ?? null;

            if (!empty($order_sn)) {
                $order_sn = (string)$order_sn;

                // Check if order exists in system
                $orderExists = Order::find()
                    ->where(['order_sn' => $order_sn])
                    ->orWhere(['order_id' => $order_sn])
                    ->exists();

                if ($orderExists) {
                    // Order found - set it
                    $feeTransaction->order_sn = $order_sn;
                    Yii::debug("Order found for transaction: $order_sn", __METHOD__);
                } else {
                    // Order not found - store in reason but don't set order_sn
                    $feeTransaction->reason .= " [Order: $order_sn]";
                    Yii::debug("Order not in system: $order_sn (stored in reason)", __METHOD__);
                }
            }

            // Transaction date
            $create_time = $transaction['create_time'] ?? time();
            $feeTransaction->transaction_date = date('Y-m-d H:i:s', $create_time);

            // Categorize fee
            $feeTransaction->fee_category = $this->categorizeShopeeTransaction($transaction);

            $feeTransaction->created_at = date('Y-m-d H:i:s');
            $feeTransaction->updated_at = date('Y-m-d H:i:s');

            // Log transaction data before save
            Yii::info("📝 Attempting to save transaction:", __METHOD__);
            Yii::info("  Transaction ID: {$transaction_id}", __METHOD__);
            Yii::info("  Type: {$feeTransaction->transaction_type}", __METHOD__);
            Yii::info("  Amount: {$feeTransaction->amount}", __METHOD__);
            Yii::info("  Reason: {$feeTransaction->reason}", __METHOD__);
            Yii::info("  Order SN: " . ($feeTransaction->order_sn ?? 'NULL'), __METHOD__);
            Yii::info("  Category: {$feeTransaction->fee_category}", __METHOD__);

            if ($feeTransaction->save()) {
                Yii::info("✓ Saved: $transaction_id (Type: {$feeTransaction->transaction_type}, Amount: $amount, Category: {$feeTransaction->fee_category})", __METHOD__);
                return true;
            } else {
                $errors = Json::encode($feeTransaction->errors);
                Yii::error("✗ Failed to save $transaction_id: $errors", __METHOD__);
                Yii::info("Full transaction data: " . Json::encode($transaction), __METHOD__);

                // Try to save without order_sn if validation failed on order_sn
                if (isset($feeTransaction->errors['order_sn'])) {
                    Yii::warning("Retrying save without order_sn for $transaction_id", __METHOD__);
                    if (!empty($feeTransaction->order_sn)) {
                        $feeTransaction->reason .= " [Failed Order: {$feeTransaction->order_sn}]";
                        $feeTransaction->order_sn = null;

                        if ($feeTransaction->save()) {
                            Yii::info("✓ Saved without order_sn: $transaction_id", __METHOD__);
                            return true;
                        } else {
                            Yii::error("✗ Still failed after removing order_sn: " . Json::encode($feeTransaction->errors), __METHOD__);
                        }
                    }
                }

                return false;
            }

        } catch (\Exception $e) {
            Yii::error('Error processing wallet transaction: ' . $e->getMessage(), __METHOD__);
            Yii::error('Transaction data: ' . Json::encode($transaction), __METHOD__);
            return false;
        }
    }

    private function syncShopeeOrderIncomeV2($channel, $fromTime, $toTime)
    {
        $channel_id = is_object($channel) ? $channel->id : (int)$channel;

        if (is_int($channel)) {
            $channel = OnlineChannel::findOne($channel_id);
            if (!$channel) {
                Yii::error("Channel not found: $channel_id", __METHOD__);
                return 0;
            }
        }

        // Get orders in the period
        $orders = Order::find()
            ->where(['channel_id' => $channel_id])
            ->andWhere(['>=', 'order_date', date('Y-m-d H:i:s', $fromTime)])
            ->andWhere(['<=', 'order_date', date('Y-m-d H:i:s', $toTime)])
            ->all();

        if (empty($orders)) {
            Yii::info('No orders found', __METHOD__);
            return 0;
        }

        Yii::info("Calculating fees for " . count($orders) . " orders from wallet transactions", __METHOD__);

        $successCount = 0;
        $skipCount = 0;

        foreach ($orders as $order) {
            // Skip if already synced recently (within 1 hour)
            if ($order->fee_sync_at && strtotime($order->fee_sync_at) > strtotime('-1 hour')) {
                $skipCount++;
                continue;
            }

            try {
                // Calculate fees from wallet transactions
                if ($this->calculateOrderFeesFromTransactions($channel_id, $order)) {
                    $successCount++;
                }

            } catch (\Exception $e) {
                Yii::error("Error calculating fees for order {$order->order_sn}: " . $e->getMessage(), __METHOD__);
                continue;
            }
        }

        Yii::info("✓ Updated {$successCount} orders with fee calculations (skipped: {$skipCount})", __METHOD__);
        return $successCount;
    }

    /**
     * Calculate order fees from wallet transactions
     * @param int $channel_id
     * @param Order $order
     * @return bool
     */
    private function calculateOrderFeesFromTransactions($channel_id, $order)
    {
        try {
            // Find all transactions related to this order
            $transactions = ShopeeTransaction::find()
                ->where(['channel_id' => $channel_id])
                ->andWhere(['order_sn' => $order->order_sn])
                ->all();

            if (empty($transactions)) {
                Yii::debug("No transactions found for order {$order->order_sn}", __METHOD__);
                return false;
            }

            // Calculate fees by category
            $commission_fee = 0;
            $transaction_fee = 0;
            $service_fee = 0;
            $payment_fee = 0;
            $shipping_fee = 0;
            $order_income = 0;

            foreach ($transactions as $trans) {
                $amount = abs($trans->amount);

                switch ($trans->fee_category) {
                    case 'ORDER_INCOME':
                        $order_income += $trans->amount; // positive
                        break;
                    case 'COMMISSION_FEE':
                        $commission_fee += $amount;
                        break;
                    case 'TRANSACTION_FEE':
                        $transaction_fee += $amount;
                        break;
                    case 'SERVICE_FEE':
                        $service_fee += $amount;
                        break;
                    case 'PAYMENT_FEE':
                        $payment_fee += $amount;
                        break;
                    case 'SHIPPING_FEE':
                        $shipping_fee += $amount;
                        break;
                    case 'AMS_COMMISSION_FEE_DEDUCT':
                        $commission_fee += $amount;
                        break;
                    case 'ESCROW_VERIFIED_MINUS':
                        $service_fee += $amount;
                        break;
                }
            }

            // Calculate actual income
            $actual_income = $order_income - $commission_fee - $transaction_fee - $service_fee - $payment_fee;

            Yii::info("Order {$order->order_sn} fees from transactions:", __METHOD__);
            Yii::info("  Order Income: {$order_income}", __METHOD__);
            Yii::info("  Commission: {$commission_fee}", __METHOD__);
            Yii::info("  Transaction: {$transaction_fee}", __METHOD__);
            Yii::info("  Service: {$service_fee}", __METHOD__);
            Yii::info("  Payment: {$payment_fee}", __METHOD__);
            Yii::info("  Actual Income: {$actual_income}", __METHOD__);

            // Update order
            $order->commission_fee = $commission_fee;
            $order->transaction_fee = $transaction_fee;
            $order->service_fee = $service_fee;
            $order->payment_fee = $payment_fee;
            $order->shipping_fee = $shipping_fee;
            $order->actual_income = $actual_income;
            $order->fee_sync_at = date('Y-m-d H:i:s');
            $order->updated_at = date('Y-m-d H:i:s');

            if ($order->save(false)) {
                Yii::info("✓ Updated order {$order->order_sn} with calculated fees", __METHOD__);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Yii::error("Error calculating fees from transactions: " . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    private function calculateFeeSummary($channel, $fromTime, $toTime)
    {
        $transactions = ShopeeTransaction::find()
            ->where(['channel_id' => $channel])
            ->andWhere(['>=', 'transaction_date', date('Y-m-d H:i:s', $fromTime)])
            ->andWhere(['<=', 'transaction_date', date('Y-m-d H:i:s', $toTime)])
            ->andWhere(['<', 'amount', 0]) // เฉพาะค่าใช้จ่าย
            ->all();

        $summary = [
            'commission_fee' => 0,
            'transaction_fee' => 0,
            'service_fee' => 0,
            'shipping_fee' => 0,
            'campaign_fee' => 0,
            'penalty_fee' => 0,
            'refund' => 0,
            'other' => 0,
            'total_fees' => 0,
        ];

        foreach ($transactions as $trans) {
            $amount = abs($trans->amount);
            $summary['total_fees'] += $amount;

            switch ($trans->fee_category) {
                case 'COMMISSION_FEE':
                    $summary['commission_fee'] += $amount;
                    break;
                case 'TRANSACTION_FEE':
                    $summary['transaction_fee'] += $amount;
                    break;
                case 'SERVICE_FEE':
                    $summary['service_fee'] += $amount;
                    break;
                case 'SHIPPING_FEE':
                    $summary['shipping_fee'] += $amount;
                    break;
                case 'CAMPAIGN_FEE':
                    $summary['campaign_fee'] += $amount;
                    break;
                case 'PENALTY_FEE':
                    $summary['penalty_fee'] += $amount;
                    break;
                case 'REFUND':
                    $summary['refund'] += $amount;
                    break;
                default:
                    $summary['other'] += $amount;
                    break;
            }
        }

        return $summary;
    }
    /**
     * Main sync function - Updated to use V2 methods
     */
    public function syncMonthlyShopeeFeesV2($channel, $year = null, $month = null)
    {
        if ($year === null) {
            $year = (int)date('Y');
        }
        if ($month === null) {
            $month = (int)date('m');
        }

        $fromTime = strtotime("$year-$month-01 00:00:00");
        if ($month == 12) {
            $toTime = strtotime(($year + 1) . "-01-01 00:00:00") - 1;
        } else {
            $toTime = strtotime("$year-" . ($month + 1) . "-01 00:00:00") - 1;
        }

        Yii::info("=== Syncing Shopee fees for {$year}-{$month} (V2) ===", __METHOD__);

        $results = [
            'success' => true,
            'platform' => 'Shopee',
            'period' => [
                'year' => $year,
                'month' => $month,
                'from' => date('Y-m-d H:i:s', $fromTime),
                'to' => date('Y-m-d H:i:s', $toTime),
            ]
        ];

        try {
            // 1. Sync wallet transactions (V2)
            Yii::info('Step 1: Syncing wallet transactions (V2)...', __METHOD__);
            $transactionCount = $this->syncShopeeTransactionFeesV2($channel, $fromTime, $toTime);
            $results['transaction_count'] = $transactionCount;
            Yii::info("✓ Synced {$transactionCount} transactions", __METHOD__);

            // 2. Sync order income details (V2)
            Yii::info('Step 2: Syncing order income details (V2)...', __METHOD__);
            $orderIncomeCount = $this->syncShopeeOrderIncomeV2($channel, $fromTime, $toTime);
            $results['order_income_count'] = $orderIncomeCount;
            Yii::info("✓ Updated {$orderIncomeCount} orders", __METHOD__);

//            // 3. Sync settlements
//            Yii::info('Step 3: Syncing settlements...', __METHOD__);
//            $settlementCount = $this->syncShopeeSettlements($channel, $fromTime, $toTime);
//            $results['settlement_count'] = $settlementCount;
//            Yii::info("✓ Synced {$settlementCount} settlements", __METHOD__);

            // 4-6. Calculate summaries (use existing functions)
            $results['transaction_summary'] = $this->calculateFeeSummary($channel, $fromTime, $toTime);
            $results['order_summary'] = $this->calculateOrderFeeSummary($channel, $fromTime, $toTime);
            $results['settlement_summary'] = $this->calculateSettlementSummary($channel, $fromTime, $toTime);

            $results['grand_summary'] = [
                'total_revenue' => $results['order_summary']['total_revenue'],
                'total_buyer_paid' => $results['order_summary']['total_buyer_paid'],
                'total_all_fees' => $results['order_summary']['total_all_fees'],
                'total_actual_income' => $results['order_summary']['total_actual_income'],
                'total_settlements_received' => $results['settlement_summary']['total_net_amount'],
                'fee_percentage' => $results['order_summary']['fee_percentage'],
                'total_orders' => $results['order_summary']['total_orders'],
                'total_settlements' => $results['settlement_summary']['total_settlements'],
            ];

            Yii::info("=== Sync completed ===", __METHOD__);

        } catch (\Exception $e) {
            $results['success'] = false;
            $results['error'] = $e->getMessage();
            Yii::error("Sync failed: " . $e->getMessage(), __METHOD__);
        }

        return $results;
    }

    private function calculateOrderFeeSummary($channel, $fromTime, $toTime)
    {
        $orders = Order::find()
            ->where(['channel_id' => $channel])
            ->andWhere(['>=', 'order_date', date('Y-m-d H:i:s', $fromTime)])
            ->andWhere(['<=', 'order_date', date('Y-m-d H:i:s', $toTime)])
            ->all();

        $summary = [
            'total_orders' => count($orders),
            'total_quantity' => 0,
            'total_revenue' => 0,
            'total_buyer_paid' => 0,
            'total_commission_fee' => 0,
            'total_transaction_fee' => 0,
            'total_service_fee' => 0,
            'total_payment_fee' => 0,
            'total_seller_discount' => 0,
            'total_shopee_discount' => 0,
            'total_escrow' => 0,
            'total_actual_income' => 0,
            'total_all_fees' => 0,
        ];

        foreach ($orders as $order) {
            $summary['total_quantity'] += $order->quantity;
            $summary['total_revenue'] += $order->total_amount;
            $summary['total_buyer_paid'] += $order->buyer_paid_amount;
            $summary['total_commission_fee'] += $order->commission_fee;
            $summary['total_transaction_fee'] += $order->transaction_fee;
            $summary['total_service_fee'] += $order->service_fee;
            $summary['total_payment_fee'] += $order->payment_fee;
            $summary['total_seller_discount'] += $order->seller_discount;
            $summary['total_shopee_discount'] += $order->shopee_discount;
            $summary['total_escrow'] += $order->escrow_amount;
            $summary['total_actual_income'] += $order->actual_income;

            // รวมค่าธรรมเนียมทั้งหมด
            $summary['total_all_fees'] += ($order->commission_fee +
                $order->transaction_fee +
                $order->service_fee +
                $order->payment_fee);
        }

        // คำนวณเปอร์เซ็นต์
        if ($summary['total_revenue'] > 0) {
            $summary['fee_percentage'] = ($summary['total_all_fees'] / $summary['total_revenue']) * 100;
            $summary['commission_percentage'] = ($summary['total_commission_fee'] / $summary['total_revenue']) * 100;
        } else {
            $summary['fee_percentage'] = 0;
            $summary['commission_percentage'] = 0;
        }

        return $summary;
    }
    /**
     * Sync Shopee settlements from wallet transactions
     * Since get_settlement_list API doesn't exist, we extract settlement data from wallet transactions
     */
    private function syncShopeeSettlements($channel, $fromTime, $toTime)
    {
        $channel_id = is_object($channel) ? $channel->id : (int)$channel;

        Yii::info("=== Syncing Shopee Settlements from Wallet Transactions ===", __METHOD__);

        // Get all withdrawal/payout transactions from wallet transactions that were already synced
        $payoutTransactions = ShopeeTransaction::find()
            ->where(['channel_id' => $channel_id])
            ->andWhere(['>=', 'transaction_date', date('Y-m-d H:i:s', $fromTime)])
            ->andWhere(['<=', 'transaction_date', date('Y-m-d H:i:s', $toTime)])
            ->andWhere(['fee_category' => 'WITHDRAWAL'])
            ->all();

        if (empty($payoutTransactions)) {
            Yii::info('No payout/withdrawal transactions found in the period', __METHOD__);
            return 0;
        }

        Yii::info('Found ' . count($payoutTransactions) . ' payout transactions', __METHOD__);

        $count = 0;

        foreach ($payoutTransactions as $trans) {
            try {
                // Check if settlement already exists
                $existing = ShopeeSettlement::find()
                    ->where([
                        'channel_id' => $channel_id,
                        'settlement_id' => $trans->transaction_id,
                    ])
                    ->one();

                if ($existing) {
                    Yii::debug("Settlement already exists for transaction: {$trans->transaction_id}", __METHOD__);
                    continue;
                }

                // Create settlement from transaction
                $settlement = new ShopeeSettlement();
                $settlement->channel_id = $channel_id;
                $settlement->shop_id = $trans->shop_id;
                $settlement->transaction_id = $trans->transaction_id;
                $settlement->settlement_no = $trans->transaction_id; // Use transaction ID as settlement number

                // Payout amount is the absolute value of withdrawal amount
                $payoutAmount = abs($trans->amount);
                $settlement->settlement_amount = $payoutAmount;

                // Try to get related order fees to calculate settlement fee
                // Settlement fee = sum of all fees for orders in this payout period
                $relatedFees = ShopeeTransaction::find()
                    ->where(['channel_id' => $channel_id])
                    ->andWhere(['<', 'amount', 0]) // negative amounts = fees
                    ->andWhere(['!=', 'fee_category', 'WITHDRAWAL'])
                    ->andWhere(['>=', 'transaction_date', date('Y-m-d H:i:s', strtotime($trans->transaction_date) - 86400 * 7)]) // 7 days before payout
                    ->andWhere(['<=', 'transaction_date', $trans->transaction_date])
                    ->sum('ABS(amount)');

                $settlement->settlement_fee = $relatedFees ?: 0;
                $settlement->net_settlement_amount = $payoutAmount;

                $settlement->payout_time = $trans->transaction_date;
                $settlement->status = 'completed';

                // Count related orders
                $orderCount = Order::find()
                    ->where(['channel_id' => $channel_id])
                    ->andWhere(['>=', 'order_date', date('Y-m-d H:i:s', strtotime($trans->transaction_date) - 86400 * 7)])
                    ->andWhere(['<=', 'order_date', $trans->transaction_date])
                    ->count();

                $settlement->order_count = $orderCount;
                $settlement->created_at = date('Y-m-d H:i:s');
                $settlement->updated_at = date('Y-m-d H:i:s');

                if ($settlement->save()) {
                    $count++;
                    Yii::info("✓ Created settlement from transaction: {$trans->transaction_id}, Amount: {$payoutAmount}", __METHOD__);
                } else {
                    Yii::error("Failed to save settlement: " . Json::encode($settlement->errors), __METHOD__);
                }

            } catch (\Exception $e) {
                Yii::error("Error processing settlement for transaction {$trans->transaction_id}: " . $e->getMessage(), __METHOD__);
                continue;
            }
        }

        Yii::info("✓ Created {$count} settlements from wallet transactions", __METHOD__);
        return $count;
    }
    private function calculateSettlementSummary($channel, $fromTime, $toTime)
    {
        $settlements = ShopeeSettlement::find()
            ->where(['channel_id' => $channel])
            ->andWhere(['>=', 'payout_time', date('Y-m-d H:i:s', $fromTime)])
            ->andWhere(['<=', 'payout_time', date('Y-m-d H:i:s', $toTime)])
            ->all();

        $summary = [
            'total_settlements' => count($settlements),
            'total_settlement_amount' => 0,
            'total_settlement_fee' => 0,
            'total_net_amount' => 0,
            'total_orders' => 0,
            'avg_settlement_amount' => 0,
            'avg_fee_percentage' => 0,
        ];

        $totalFeePercentage = 0;

        foreach ($settlements as $settlement) {
            $summary['total_settlement_amount'] += $settlement->settlement_amount;
            $summary['total_settlement_fee'] += $settlement->settlement_fee;
            $summary['total_net_amount'] += $settlement->net_settlement_amount;
            $summary['total_orders'] += $settlement->order_count;
            $totalFeePercentage += $settlement->getFeePercentage();
        }

        if (count($settlements) > 0) {
            $summary['avg_settlement_amount'] = $summary['total_settlement_amount'] / count($settlements);
            $summary['avg_fee_percentage'] = $totalFeePercentage / count($settlements);
        }

        return $summary;
    }



    private function syncShopeeWalletTransactionsOfficial($channel, $fromTime, $toTime)
    {
        $channel_id = is_object($channel) ? $channel->id : (int)$channel;

        if (is_int($channel)) {
            $channel = OnlineChannel::findOne($channel_id);
            if (!$channel) {
                Yii::error("Channel not found: $channel_id", __METHOD__);
                return 0;
            }
        }

        $tokenModel = ShopeeToken::find()
            ->where(['status' => 'active'])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if (!$tokenModel) {
            Yii::warning('No active Shopee token found', __METHOD__);
            return 0;
        }

        // Check token expiry
        if (strtotime($tokenModel->expires_at) < time()) {
            if (!$this->refreshShopeeToken($tokenModel)) {
                Yii::warning('Failed to refresh Shopee token', __METHOD__);
                return 0;
            }
        }

        $partner_id = 2012399;
        $partner_key = 'shpk72476151525864414e4b6e475449626679624f695a696162696570417043';
        $shop_id = $tokenModel->shop_id;
        $access_token = $tokenModel->access_token;

        Yii::info("=== Syncing Shopee Wallet Transactions (Official API) ===", __METHOD__);
        Yii::info("Period: " . date('Y-m-d H:i:s', $fromTime) . " to " . date('Y-m-d H:i:s', $toTime), __METHOD__);
        Yii::info("Shop ID: $shop_id", __METHOD__);

        $totalCount = 0;
        $page_no = 1;
        $page_size = 100; // Max 100 per request

        try {
            do {
                $timestamp = time();

                // Official API endpoint (GET method)
                $path = "/api/v2/payment/get_wallet_transaction_list";

                // Generate sign according to documentation
                // Format: partner_id + path + timestamp + access_token + shop_id
                $base_string = $partner_id . $path . $timestamp . $access_token . $shop_id;
                $sign = hash_hmac('sha256', $base_string, $partner_key);

                // Query parameters (all in URL for GET request)
                $params = [
                    'partner_id' => (int)$partner_id,
                    'shop_id' => (int)$shop_id,
                    'sign' => $sign,
                    'timestamp' => $timestamp,
                    'access_token' => $access_token,
                    'transaction_time_from' => (int)$fromTime,
                    'transaction_time_to' => (int)$toTime,
                    'page_no' => $page_no,
                    'page_size' => $page_size,
                ];

                Yii::info("Fetching page $page_no (size: $page_size)", __METHOD__);
                Yii::debug("Query params: " . Json::encode($params), __METHOD__);

                // Use GET method (as per documentation)
                $response = $this->httpClient->get('https://partner.shopeemobile.com' . $path, [
                    'query' => $params,
                    'timeout' => 30,
                    'http_errors' => false,
                ]);

                $statusCode = $response->getStatusCode();
                $rawBody = (string)$response->getBody();

                Yii::debug("Response Status: $statusCode", __METHOD__);
                Yii::debug("Response Body: " . substr($rawBody, 0, 1000), __METHOD__);

                if ($statusCode !== 200) {
                    Yii::error("HTTP Error: $statusCode", __METHOD__);
                    Yii::error("Response: $rawBody", __METHOD__);
                    break;
                }

                $data = Json::decode($rawBody);

                // Check API error
                if (isset($data['error']) && !empty($data['error'])) {
                    Yii::error("Shopee API Error: {$data['error']}", __METHOD__);
                    if (isset($data['message'])) {
                        Yii::error("Error message: {$data['message']}", __METHOD__);
                    }
                    break;
                }

                // Get response data
                $response_data = $data['response'] ?? [];
                $transactionList = $response_data['transaction_list'] ?? [];
                $more = $response_data['more'] ?? false;

                if (empty($transactionList)) {
                    Yii::info('No more transactions found', __METHOD__);
                    break;
                }

                Yii::info("Processing " . count($transactionList) . " transactions from page $page_no", __METHOD__);

                // Process each transaction
                $pageSuccess = 0;
                $pageSkip = 0;
                $pageFail = 0;

                foreach ($transactionList as $transaction) {
                    $result = $this->processShopeeWalletTransactionSafe($channel_id, $transaction, $shop_id);

                    if ($result === true) {
                        $totalCount++;
                        $pageSuccess++;
                    } elseif ($result === 'skip') {
                        $pageSkip++;
                    } else {
                        $pageFail++;
                    }
                }

                Yii::info("Page $page_no: Success=$pageSuccess, Skip=$pageSkip, Fail=$pageFail", __METHOD__);

                // Check if has more pages
                if (!$more) {
                    Yii::info("No more pages", __METHOD__);
                    break;
                }

                $page_no++;
                usleep(300000); // 0.3 second delay between pages

            } while (true);

            Yii::info("✓ Total synced: $totalCount wallet transactions", __METHOD__);

        } catch (\Exception $e) {
            Yii::error('Exception in wallet transaction sync: ' . $e->getMessage(), __METHOD__);
            Yii::error('Stack trace: ' . $e->getTraceAsString(), __METHOD__);
        }

        return $totalCount;
    }

    /**
     * Process Shopee wallet transaction safely (with order validation)
     * @param int $channel_id
     * @param array $transaction
     * @param string $shop_id
     * @return bool|string true=saved, 'skip'=already exists, false=failed
     */
    private function processShopeeWalletTransactionSafe($channel_id, $transaction, $shop_id)
    {
        try {
            // Extract transaction ID
            $transaction_id = $transaction['transaction_id'] ?? null;
            if (empty($transaction_id)) {
                Yii::warning("Missing transaction_id in: " . Json::encode($transaction), __METHOD__);
                return false;
            }

            $transaction_id = (string)$transaction_id;

            // Check if already exists
            $existing = ShopeeTransaction::findOne(['transaction_id' => $transaction_id]);
            if ($existing) {
                Yii::debug("Transaction exists: $transaction_id", __METHOD__);
                return 'skip';
            }

            // Create new transaction record
            $feeTransaction = new ShopeeTransaction();
            $feeTransaction->transaction_id = $transaction_id;
            $feeTransaction->channel_id = $channel_id;
            $feeTransaction->shop_id = (string)$shop_id;

            // Transaction type
            $transaction_type = $transaction['transaction_type'] ?? 'UNKNOWN';
            $feeTransaction->transaction_type = (string)$transaction_type;

            // Reason/Description
            $reason = $transaction['reason'] ?? $transaction['description'] ?? '';
            if (empty($reason)) {
                $reason = $transaction_type;
            }
            $feeTransaction->reason = (string)$reason;

            // Amount
            $amount = (float)($transaction['amount'] ?? 0);
            $feeTransaction->amount = $amount;

            // Current balance
            $feeTransaction->current_balance = (float)($transaction['current_balance'] ?? 0);

            // Status
            $feeTransaction->status = isset($transaction['status'])
                ? (string)$transaction['status']
                : 'COMPLETED';

            // Order reference - VALIDATE BEFORE SETTING
            $order_sn = $transaction['order_sn'] ?? $transaction['order_id'] ?? null;

            if (!empty($order_sn)) {
                $order_sn = (string)$order_sn;

                // Check if order exists in system
                $orderExists = Order::find()
                    ->where(['order_sn' => $order_sn])
                    ->orWhere(['order_id' => $order_sn])
                    ->exists();

                if ($orderExists) {
                    // Order found - set it
                    $feeTransaction->order_sn = $order_sn;
                    Yii::debug("Order found for transaction: $order_sn", __METHOD__);
                } else {
                    // Order not found - store in reason but don't set order_sn
                    $feeTransaction->reason .= " [Order: $order_sn]";
                    Yii::debug("Order not in system: $order_sn (stored in reason)", __METHOD__);
                }
            }

            // Transaction date
            $create_time = $transaction['create_time'] ?? $transaction['transaction_time'] ?? time();
            $feeTransaction->transaction_date = date('Y-m-d H:i:s', $create_time);

            // Categorize transaction
            $feeTransaction->fee_category = $this->categorizeShopeeTransaction($transaction);

            $feeTransaction->created_at = date('Y-m-d H:i:s');
            $feeTransaction->updated_at = date('Y-m-d H:i:s');

            // Save
            if ($feeTransaction->save()) {
                Yii::info("✓ Saved: $transaction_id (Type: {$transaction_type}, Amount: $amount, Category: {$feeTransaction->fee_category})", __METHOD__);
                return true;
            } else {
                $errors = Json::encode($feeTransaction->errors);
                Yii::error("✗ Failed to save $transaction_id: $errors", __METHOD__);
                Yii::debug("Transaction data: " . Json::encode($transaction), __METHOD__);

                // Try to save without order_sn if validation failed on order_sn
                if (isset($feeTransaction->errors['order_sn'])) {
                    Yii::warning("Retrying save without order_sn for $transaction_id", __METHOD__);
                    if (!empty($feeTransaction->order_sn)) {
                        $feeTransaction->reason .= " [Failed Order: {$feeTransaction->order_sn}]";
                        $feeTransaction->order_sn = null;

                        if ($feeTransaction->save()) {
                            Yii::info("✓ Saved without order_sn: $transaction_id", __METHOD__);
                            return true;
                        }
                    }
                }

                return false;
            }

        } catch (\Exception $e) {
            Yii::error('Exception processing transaction: ' . $e->getMessage(), __METHOD__);
            Yii::error('Transaction data: ' . Json::encode($transaction), __METHOD__);
            return false;
        }
    }

    /**
     * Categorize Shopee transaction (enhanced version with transaction_tab_type support)
     */
//    private function categorizeShopeeTransaction($transaction)
//    {
//        $type = strtoupper($transaction['transaction_type'] ?? '');
//        $reason = strtolower($transaction['reason'] ?? $transaction['description'] ?? '');
//        $amount = (float)($transaction['amount'] ?? 0);
//        $tab_type = strtolower($transaction['transaction_tab_type'] ?? '');
//
//        // Use transaction_tab_type if available (more accurate)
//        if (!empty($tab_type)) {
//            if (strpos($tab_type, 'wallet_order_income') !== false ||
//                strpos($tab_type, 'order_income') !== false) {
//                return 'ORDER_INCOME';
//            }
//            if (strpos($tab_type, 'wallet_adjustment') !== false ||
//                strpos($tab_type, 'adjustment') !== false) {
//                return $amount > 0 ? 'ADJUSTMENT_IN' : 'ADJUSTMENT';
//            }
//            if (strpos($tab_type, 'wallet_withdrawals') !== false ||
//                strpos($tab_type, 'withdrawal') !== false) {
//                return 'WITHDRAWAL';
//            }
//            if (strpos($tab_type, 'refund') !== false) {
//                return $amount > 0 ? 'REFUND_RECEIVED' : 'REFUND';
//            }
//        }
//
//        // Income (positive amounts)
//        if ($amount > 0) {
//            if (strpos($reason, 'order') !== false ||
//                strpos($reason, 'payment') !== false ||
//                strpos($reason, 'คำสั่งซื้อ') !== false ||
//                strpos($type, 'ORDER') !== false ||
//                strpos($type, 'ESCROW') !== false) {
//                return 'ORDER_INCOME';
//            }
//            if (strpos($reason, 'refund') !== false ||
//                strpos($reason, 'คืนเงิน') !== false ||
//                strpos($type, 'REFUND') !== false) {
//                return 'REFUND_RECEIVED';
//            }
//            if (strpos($reason, 'adjustment') !== false ||
//                strpos($reason, 'compensation') !== false ||
//                strpos($reason, 'ชดเชย') !== false) {
//                return 'ADJUSTMENT_IN';
//            }
//            return 'INCOME';
//        }
//
//        // Expenses (negative amounts)
//        // Commission
//        if (strpos($type, 'COMMISSION') !== false ||
//            strpos($reason, 'commission') !== false ||
//            strpos($reason, 'seller commission') !== false) {
//            return 'COMMISSION_FEE';
//        }
//
//        // Transaction fee
//        if (strpos($type, 'TRANSACTION_FEE') !== false ||
//            strpos($type, 'PAYMENT_FEE') !== false ||
//            strpos($type, 'transaction_fee') !== false ||
//            strpos($reason, 'transaction fee') !== false ||
//            strpos($reason, 'payment fee') !== false) {
//            return 'TRANSACTION_FEE';
//        }
//
//        // Service fee
//        if (strpos($type, 'SERVICE_FEE') !== false ||
//            strpos($reason, 'service fee') !== false ||
//            strpos($reason, 'platform fee') !== false) {
//            return 'SERVICE_FEE';
//        }
//
//        // Shipping
//        if (strpos($type, 'SHIPPING') !== false ||
//            strpos($reason, 'shipping') !== false ||
//            strpos($reason, 'logistic') !== false ||
//            strpos($reason, 'delivery') !== false) {
//            return 'SHIPPING_FEE';
//        }
//
//        // Campaign/Marketing
//        if (strpos($type, 'CAMPAIGN') !== false ||
//            strpos($type, 'PROMOTION') !== false ||
//            strpos($type, 'ADS') !== false ||
//            strpos($reason, 'voucher') !== false ||
//            strpos($reason, 'discount') !== false ||
//            strpos($reason, 'flash sale') !== false ||
//            strpos($reason, 'campaign') !== false ||
//            strpos($reason, 'promotion') !== false ||
//            strpos($reason, 'marketing') !== false) {
//            return 'CAMPAIGN_FEE';
//        }
//
//        // Penalty
//        if (strpos($type, 'PENALTY') !== false ||
//            strpos($type, 'FINE') !== false ||
//            strpos($reason, 'penalty') !== false ||
//            strpos($reason, 'fine') !== false ||
//            strpos($reason, 'violation') !== false) {
//            return 'PENALTY_FEE';
//        }
//
//        // Refund (outgoing)
//        if (strpos($type, 'REFUND') !== false ||
//            strpos($type, 'RETURN') !== false ||
//            strpos($reason, 'refund') !== false ||
//            strpos($reason, 'return') !== false) {
//            return 'REFUND';
//        }
//
//        // Adjustment
//        if (strpos($type, 'ADJUSTMENT') !== false ||
//            strpos($reason, 'adjustment') !== false ||
//            strpos($reason, 'correction') !== false) {
//            return 'ADJUSTMENT';
//        }
//
//        // Withdrawal/Payout
//        if (strpos($type, 'WITHDRAWAL') !== false ||
//            strpos($type, 'PAYOUT') !== false ||
//            strpos($reason, 'withdrawal') !== false ||
//            strpos($reason, 'payout') !== false ||
//            strpos($reason, 'transfer') !== false) {
//            return 'WITHDRAWAL';
//        }
//
//        // Reversal
//        if (strpos($type, 'REVERSAL') !== false ||
//            strpos($reason, 'reversal') !== false ||
//            strpos($reason, 'reverse') !== false) {
//            return 'REVERSAL';
//        }
//
//        // Other
//        return 'OTHER';
//    }

    private function categorizeShopeeTransaction($transaction)
    {
        $type = strtoupper($transaction['transaction_type'] ?? '');
        $reason = strtolower($transaction['reason'] ?? $transaction['description'] ?? '');
        $amount = (float)($transaction['amount'] ?? 0);
        $tab_type = strtolower($transaction['transaction_tab_type'] ?? '');

        // Use transaction_tab_type if available (more accurate)
        if (!empty($tab_type)) {
            if (strpos($tab_type, 'wallet_order_income') !== false ||
                strpos($tab_type, 'order_income') !== false) {
                return 'ORDER_INCOME';
            }
            if (strpos($tab_type, 'wallet_adjustment') !== false ||
                strpos($tab_type, 'adjustment') !== false) {
                return $amount > 0 ? 'ADJUSTMENT_IN' : 'ADJUSTMENT';
            }
            if (strpos($tab_type, 'wallet_withdrawals') !== false ||
                strpos($tab_type, 'withdrawal') !== false) {
                return 'WITHDRAWAL';
            }
            if (strpos($tab_type, 'refund') !== false) {
                return $amount > 0 ? 'REFUND_RECEIVED' : 'REFUND';
            }
        }

        // Check transaction_type first (most accurate)
        // Escrow - Order Income
        if ($type === 'ESCROW_VERIFIED_ADD' || $type === '101') {
            return 'ORDER_INCOME';
        }
        if ($type === 'ESCROW_VERIFIED_MINUS' || $type === '102') {
            return 'ORDER_DEDUCTION';
        }

        // Withdrawal
        if ($type === 'WITHDRAWAL_CREATED' || $type === '201' ||
            $type === 'WITHDRAWAL_COMPLETED' || $type === '202') {
            return 'WITHDRAWAL';
        }
        if ($type === 'WITHDRAWAL_CANCELLED' || $type === '203') {
            return 'WITHDRAWAL_CANCELLED';
        }

        // Refund Income
        if ($type === 'REFUND_VERIFIED_ADD' || $type === '301' ||
            $type === 'AUTO_REFUND_ADD' || $type === '302' ||
            $type === 'SPM_REFUND_ADD' || $type === '504' ||
            $type === 'APM_REFUND_ADD' || $type === '505' ||
            $type === 'DP_REFUND_VERIFIED_ADD' || $type === '701') {
            return 'REFUND_RECEIVED';
        }

        // Adjustments - Income
        if ($type === 'ADJUSTMENT_ADD' || $type === '401' ||
            $type === 'FBS_ADJUSTMENT_ADD' || $type === '404' ||
            $type === 'ADJUSTMENT_CENTER_ADD' || $type === '406' ||
            $type === 'AFFILIATE_COMMISSION_FEE_ADD' || $type === '412' ||
            $type === 'CROSS_MERCHANT_ADJUSTMENT_ADD' || $type === '413' ||
            $type === 'SELLER_COMPENSATE_ADD' || $type === '415' ||
            $type === 'CAMPAIGN_PACKAGE_ADD' || $type === '416' ||
            $type === 'PAID_ADS_REFUND' || $type === '451' ||
            $type === 'AFFILIATE_ADS_SELLER_FEE_REFUND' || $type === '456' ||
            $type === 'SPM_DISBURSE_ADD' || $type === '802') {
            return 'ADJUSTMENT_IN';
        }

        // Adjustments - Deductions
        if ($type === 'ADJUSTMENT_MINUS' || $type === '402' ||
            $type === 'FBS_ADJUSTMENT_MINUS' || $type === '405' ||
            $type === 'ADJUSTMENT_CENTER_DEDUCT' || $type === '407' ||
            $type === 'ESCROW_ADJUSTMENT_FOR_FD_DEDUCT' || $type === '408' ||
            $type === 'PERCEPTION_VAT_TAX_DEDUCT' || $type === '409' ||
            $type === 'ADJUSTMENT_FOR_RR_AFTER_ESCROW_VERIFIED' || $type === '411' ||
            $type === 'CROSS_MERCHANT_ADJUSTMENT_DEDUCT' || $type === '414' ||
            $type === 'CAMPAIGN_PACKAGE_MINUS' || $type === '417') {
            return 'ADJUSTMENT';
        }

        // Fast Escrow
        if ($type === 'FAST_ESCROW_DISBURSE' || $type === '452' ||
            $type === 'FAST_ESCROW_DISBURSE_REMAIN' || $type === '459') {
            return 'FAST_ESCROW_INCOME';
        }
        if ($type === 'FAST_ESCROW_DEDUCT' || $type === '458') {
            return 'FAST_ESCROW_DEDUCT';
        }

        // Marketing/Campaign Fees
        if ($type === 'PAID_ADS_CHARGE' || $type === '450' ||
            $type === 'AFFILIATE_ADS_SELLER_FEE' || $type === '455' ||
            $type === 'AFFILIATE_FEE_DEDUCT' || $type === '460') {
            return 'CAMPAIGN_FEE';
        }

        // Payment Methods (Shopee Wallet, SPM, APM)
        if ($type === 'SHOPEE_WALLET_PAY' || $type === '501' ||
            $type === 'SPM_DEDUCT' || $type === '502' ||
            $type === 'APM_DEDUCT' || $type === '503') {
            return 'PAYMENT_DEDUCTION';
        }

        // Seller Loan
        if ($type === 'SPM_DEDUCT_DIRECT' || $type === '801') {
            return 'LOAN_REPAYMENT';
        }

        // Income (positive amounts)
        if ($amount > 0) {
            if (strpos($reason, 'order') !== false ||
                strpos($reason, 'payment') !== false ||
                strpos($reason, 'คำสั่งซื้อ') !== false ||
                strpos($type, 'ORDER') !== false ||
                strpos($type, 'ESCROW') !== false) {
                return 'ORDER_INCOME';
            }
            if (strpos($reason, 'refund') !== false ||
                strpos($reason, 'คืนเงิน') !== false ||
                strpos($type, 'REFUND') !== false) {
                return 'REFUND_RECEIVED';
            }
            if (strpos($reason, 'adjustment') !== false ||
                strpos($reason, 'compensation') !== false ||
                strpos($reason, 'ชดเชย') !== false) {
                return 'ADJUSTMENT_IN';
            }
            return 'INCOME';
        }

        // Expenses (negative amounts)
        // Commission
        if (strpos($type, 'COMMISSION') !== false ||
            strpos($reason, 'commission') !== false ||
            strpos($reason, 'seller commission') !== false) {
            return 'COMMISSION_FEE';
        }

        // Transaction fee
        if (strpos($type, 'TRANSACTION_FEE') !== false ||
            strpos($type, 'PAYMENT_FEE') !== false ||
            strpos($type, 'transaction_fee') !== false ||
            strpos($reason, 'transaction fee') !== false ||
            strpos($reason, 'payment fee') !== false) {
            return 'TRANSACTION_FEE';
        }

        // Service fee
        if (strpos($type, 'SERVICE_FEE') !== false ||
            strpos($reason, 'service fee') !== false ||
            strpos($reason, 'platform fee') !== false) {
            return 'SERVICE_FEE';
        }

        // Shipping
        if (strpos($type, 'SHIPPING') !== false ||
            strpos($reason, 'shipping') !== false ||
            strpos($reason, 'logistic') !== false ||
            strpos($reason, 'delivery') !== false) {
            return 'SHIPPING_FEE';
        }

        // Campaign/Marketing
        if (strpos($type, 'CAMPAIGN') !== false ||
            strpos($type, 'PROMOTION') !== false ||
            strpos($type, 'ADS') !== false ||
            strpos($reason, 'voucher') !== false ||
            strpos($reason, 'discount') !== false ||
            strpos($reason, 'flash sale') !== false ||
            strpos($reason, 'campaign') !== false ||
            strpos($reason, 'promotion') !== false ||
            strpos($reason, 'marketing') !== false) {
            return 'CAMPAIGN_FEE';
        }

        // Penalty
        if (strpos($type, 'PENALTY') !== false ||
            strpos($type, 'FINE') !== false ||
            strpos($reason, 'penalty') !== false ||
            strpos($reason, 'fine') !== false ||
            strpos($reason, 'violation') !== false) {
            return 'PENALTY_FEE';
        }

        // Refund (outgoing)
        if (strpos($type, 'REFUND') !== false ||
            strpos($type, 'RETURN') !== false ||
            strpos($reason, 'refund') !== false ||
            strpos($reason, 'return') !== false) {
            return 'REFUND';
        }

        // Adjustment
        if (strpos($type, 'ADJUSTMENT') !== false ||
            strpos($reason, 'adjustment') !== false ||
            strpos($reason, 'correction') !== false) {
            return 'ADJUSTMENT';
        }

        // Withdrawal/Payout
        if (strpos($type, 'WITHDRAWAL') !== false ||
            strpos($type, 'PAYOUT') !== false ||
            strpos($reason, 'withdrawal') !== false ||
            strpos($reason, 'payout') !== false ||
            strpos($reason, 'transfer') !== false) {
            return 'WITHDRAWAL';
        }

        // Reversal
        if (strpos($type, 'REVERSAL') !== false ||
            strpos($reason, 'reversal') !== false ||
            strpos($reason, 'reverse') !== false) {
            return 'REVERSAL';
        }

        if(strpos($type, 'PAID_ADS_CHARGE') !== false){
            return 'PAID_ADS_CHARGE';
        }
        if(strpos($type, 'PAID_ADS_REFUND') !== false){
            return 'PAID_ADS_REFUND';
        }
        if(strpos($type, 'AFFILIATE_ADS_SELLER_FEE') !== false){
            return 'AFFILIATE_ADS_SELLER_FEE';
        }
        if(strpos($type, 'AFFILIATE_ADS_SELLER_FEE_REFUND') !== false){
            return 'AFFILIATE_ADS_SELLER_FEE_REFUND';
        }
        if(strpos($type, 'AFFILIATE_FEE_DEDUCT') !== false){
            return 'AFFILIATE_FEE_DEDUCT';
        }
        if(strpos($type, 'SHOPEE_WALLET_PAY') !== false){
            return 'SHOPEE_WALLET_PAY';
        }
        if(strpos($type, 'AFFILIATE_COMMISSION_FEE_ADD') !== false){
            return 'AFFILIATE_COMMISSION_FEE_ADD';
        }
        if(strpos($type, 'CROSS_MERCHANT_ADJUSTMENT_DEDUCT') !== false){
            return 'CROSS_MERCHANT_ADJUSTMENT_DEDUCT';
        }

        // Other
        return 'OTHER';
    }

    /**
     * Main Shopee sync function - Updated with official API
     */

}
