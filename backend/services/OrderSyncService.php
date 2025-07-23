<?php

namespace backend\services;

use Yii;
use backend\models\Order;
use backend\models\OnlineChannel;
use backend\components\TikTokShopApi;
use backend\components\ShopeeApi;
use yii\base\Exception;

class OrderSyncService
{
    private $tiktokApi;
    private $shopeeApi;

    public function __construct()
    {
        // Initialize APIs from config
        $this->tiktokApi = Yii::$app->get('tiktokApi', false);
        $this->shopeeApi = Yii::$app->get('shopeeApi', false);
    }

    /**
     * Sync orders from online channel
     * @param int $channelId
     * @return array
     * @throws Exception
     */
    public function syncOrders($channelId = null)
    {
        $channels = [];

        if ($channelId) {
            $channel = OnlineChannel::findOne($channelId);
            if (!$channel) {
                throw new Exception('Channel not found');
            }
            $channels[] = $channel;
        } else {
            $channels = OnlineChannel::find()->where(['status' => OnlineChannel::STATUS_ACTIVE])->all();
        }

        $totalSynced = 0;
        $errors = [];

        foreach ($channels as $channel) {
            try {
                switch ($channel->name) {
                    case 'TikTok Shop':
                        $totalSynced += $this->syncTikTokOrders($channel);
                        break;
                    case 'Shopee':
                        $totalSynced += $this->syncShopeeOrders($channel);
                        break;
                }
            } catch (\Exception $e) {
                $errors[] = $channel->name . ': ' . $e->getMessage();
            }
        }

        return [
            'count' => $totalSynced,
            'errors' => $errors
        ];
    }

    /**
     * Sync orders from TikTok Shop
     * @param OnlineChannel $channel
     * @return int
     */
    private function syncTikTokOrders($channel)
    {
        if (!$this->tiktokApi) {
            // Use sample data if API not configured
            return $this->syncTikTokSampleOrders($channel);
        }

        $count = 0;
        $page = 1;

        do {
            $orders = $this->tiktokApi->getOrders([
                'page' => $page,
                'create_time_from' => strtotime('-7 days'),
                'create_time_to' => time(),
            ]);

            foreach ($orders as $orderData) {
                $order = Order::findOne(['order_id' => $orderData['order_id']]);

                if (!$order) {
                    $order = new Order();
                    $order->order_id = $orderData['order_id'];
                    $order->channel_id = $channel->id;

                    // Map TikTok order data
                    foreach ($orderData['item_list'] as $item) {
                        $order->sku = $item['sku_id'] ?? '';
                        $order->product_name = $item['product_name'];
                        $order->quantity = $item['quantity'];
                        $order->price = $item['sale_price'];
                        $order->total_amount = $item['quantity'] * $item['sale_price'];
                        $order->order_date = date('Y-m-d H:i:s', $orderData['create_time']);

                        if ($order->save()) {
                            $count++;
                        }
                    }
                }
            }

            $page++;
        } while (!empty($orders));

        return $count;
    }

    /**
     * Sync orders from Shopee
     * @param OnlineChannel $channel
     * @return int
     */
    private function syncShopeeOrders($channel)
    {
        if (!$this->shopeeApi) {
            // Use sample data if API not configured
            return $this->shopeeSampleOrders($channel);
        }

        $count = 0;
        $offset = 0;

        do {
            $orders = $this->shopeeApi->getOrders([
                'offset' => $offset,
            ]);

            foreach ($orders as $orderData) {
                // Get detailed order info
                $orderDetail = $this->shopeeApi->getOrderDetail($orderData['order_sn']);

                if (empty($orderDetail)) {
                    continue;
                }

                foreach ($orderDetail['item_list'] as $item) {
                    $order = Order::findOne(['order_id' => $orderDetail['order_sn'] . '_' . $item['item_id']]);

                    if (!$order) {
                        $order = new Order();
                        $order->order_id = $orderDetail['order_sn'] . '_' . $item['item_id'];
                        $order->channel_id = $channel->id;
                        $order->sku = $item['model_sku'] ?? $item['item_sku'];
                        $order->product_name = $item['item_name'];
                        $order->quantity = $item['model_quantity_purchased'];
                        $order->price = $item['model_discounted_price'];
                        $order->total_amount = $item['model_quantity_purchased'] * $item['model_discounted_price'];
                        $order->order_date = date('Y-m-d H:i:s', $orderDetail['create_time']);

                        if ($order->save()) {
                            $count++;
                        }
                    }
                }
            }

            $offset += 50;
        } while (!empty($orders));

        return $count;
    }

    /**
     * Sample TikTok orders for demo
     */
    private function syncTikTokSampleOrders($channel)
    {
        $count = 0;
        $products = [
            ['sku' => 'TT-001', 'name' => 'เสื้อยืด Cotton 100%', 'price' => 299],
            ['sku' => 'TT-002', 'name' => 'กางเกงยีนส์ Slim Fit', 'price' => 590],
            ['sku' => 'TT-003', 'name' => 'รองเท้าผ้าใบ Sport', 'price' => 1290],
            ['sku' => 'TT-004', 'name' => 'กระเป๋าสะพายข้าง', 'price' => 450],
            ['sku' => 'TT-005', 'name' => 'หมวกแก๊ป Baseball', 'price' => 199],
        ];

        for ($i = 0; $i < 5; $i++) {
            $product = $products[array_rand($products)];
            $orderId = 'TT' . date('Ymd') . sprintf('%04d', rand(1, 9999));

            $order = Order::findOne(['order_id' => $orderId]);

            if (!$order) {
                $order = new Order();
                $order->order_id = $orderId;
                $order->channel_id = $channel->id;
                $order->sku = $product['sku'];
                $order->product_name = $product['name'];
                $order->quantity = rand(1, 5);
                $order->price = $product['price'];
                $order->total_amount = $order->quantity * $order->price;
                $order->order_date = date('Y-m-d H:i:s', strtotime('-' . rand(0, 7) . ' days -' . rand(0, 23) . ' hours'));

                if ($order->save()) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Sample Shopee orders for demo
     */
    private function shopeeSampleOrders($channel)
    {
        $count = 0;
        $products = [
            ['sku' => 'SH-001', 'name' => 'ชุดเดรสลายดอกไม้', 'price' => 390],
            ['sku' => 'SH-002', 'name' => 'เครื่องสำอาง Lipstick', 'price' => 159],
            ['sku' => 'SH-003', 'name' => 'อาหารเสริมวิตามิน C', 'price' => 290],
            ['sku' => 'SH-004', 'name' => 'หูฟัง Bluetooth 5.0', 'price' => 890],
            ['sku' => 'SH-005', 'name' => 'เคสโทรศัพท์ iPhone', 'price' => 99],
        ];

        for ($i = 0; $i < 5; $i++) {
            $product = $products[array_rand($products)];
            $orderId = 'SH' . date('Ymd') . sprintf('%04d', rand(1, 9999));

            $order = Order::findOne(['order_id' => $orderId]);

            if (!$order) {
                $order = new Order();
                $order->order_id = $orderId;
                $order->channel_id = $channel->id;
                $order->sku = $product['sku'];
                $order->product_name = $product['name'];
                $order->quantity = rand(1, 10);
                $order->price = $product['price'];
                $order->total_amount = $order->quantity * $order->price;
                $order->order_date = date('Y-m-d H:i:s', strtotime('-' . rand(0, 7) . ' days -' . rand(0, 23) . ' hours'));

                if ($order->save()) {
                    $count++;
                }
            }
        }

        return $count;
    }
}