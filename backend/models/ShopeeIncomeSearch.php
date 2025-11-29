<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * ShopeeIncomeSearch represents the model behind the search form of `backend\models\ShopeeIncomeDetails`.
 */
class ShopeeIncomeSearch extends ShopeeIncomeDetails
{
    public $date_range;
    public $start_date;
    public $end_date;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_sn', 'date_range', 'start_date', 'end_date'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = ShopeeIncomeDetails::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['created_at' => SORT_DESC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Grid filtering conditions
        $query->andFilterWhere(['like', 'order_sn', $this->order_sn]);

        if (!empty($this->start_date) && !empty($this->end_date)) {
            $query->andFilterWhere(['between', 'created_at', $this->start_date . ' 00:00:00', $this->end_date . ' 23:59:59']);
        }

        return $dataProvider;
    }

    public function getReportData($params)
    {
        $this->load($params);
        $query = ShopeeIncomeDetails::find();

        if (!empty($this->order_sn)) {
            $query->andWhere(['order_sn' => $this->order_sn]);
        }

        if (!empty($this->start_date) && !empty($this->end_date)) {
            $query->andFilterWhere(['between', 'created_at', $this->start_date . ' 00:00:00', $this->end_date . ' 23:59:59']);
        }

        return $query->all();
    }
}
