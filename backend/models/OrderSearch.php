<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Order;

class OrderSearch extends Order
{
    public $dateRange;
    public $startDate;
    public $endDate;

    public function rules()
    {
        return [
            [['id', 'channel_id', 'quantity'], 'integer'],
            [['order_id', 'sku', 'product_name', 'product_detail', 'order_date', 'dateRange'], 'safe'],
            [['price', 'total_amount'], 'number'],
            [['startDate', 'endDate'], 'safe'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Order::find()->with('channel');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'order_date' => SORT_DESC,
                    'id' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Grid filtering
        $query->andFilterWhere([
            'id' => $this->id,
            'channel_id' => $this->channel_id,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'total_amount' => $this->total_amount,
        ]);

        $query->andFilterWhere(['like', 'order_id', $this->order_id])
            ->andFilterWhere(['like', 'sku', $this->sku])
            ->andFilterWhere(['like', 'product_name', $this->product_name])
            ->andFilterWhere(['like', 'product_detail', $this->product_detail]);

        // Date range filter
        if (!empty($this->dateRange)) {
            list($start_date, $end_date) = explode(' - ', $this->dateRange);
            $query->andFilterWhere(['between', 'order_date',
                date('Y-m-d 00:00:00', strtotime($start_date)),
                date('Y-m-d 23:59:59', strtotime($end_date))
            ]);
        }

        // Created at filter
        if (!empty($this->created_at)) {
            $query->andFilterWhere([
                'DATE(FROM_UNIXTIME(created_at))' => date('Y-m-d', strtotime($this->created_at))
            ]);
        }

        return $dataProvider;
    }

    // Method สำหรับดึงข้อมูลรายงาน
    public function getReportData($params)
    {
        $this->load($params);

        $query = Order::find()
            ->select([
                'channel_id',
                'DATE(order_date) as order_date',
                'COUNT(*) as order_count',
                'SUM(quantity) as total_quantity',
                'SUM(total_amount) as total_sales',
                'AVG(total_amount) as avg_order_value'
            ])
            ->with('channel')
            ->groupBy(['channel_id', 'DATE(order_date)'])->orderBy(['order_date' => SORT_DESC]);

        // Apply filters
        if ($this->channel_id) {
            $query->andWhere(['channel_id' => $this->channel_id]);
        }

        if (!empty($this->dateRange)) {
            list($start_date, $end_date) = explode(' - ', $this->dateRange);
            list($d, $m, $y) = explode('/', $start_date);
            $start_date = "$y-$m-$d";

            list($d, $m, $y) = explode('/', $end_date);
            $end_date = "$y-$m-$d";
            $query->andWhere(['between', 'order_date',
                date('Y-m-d 00:00:00', strtotime($start_date)),
                date('Y-m-d 23:59:59', strtotime($end_date))
            ]);
        }

        return $query->asArray()->all();
    }
}