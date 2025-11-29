<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * TiktokIncomeSearch represents the model behind the search form of `backend\models\TiktokIncomeDetails`.
 */
class TiktokIncomeSearch extends TiktokIncomeDetails
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
            [['order_id', 'date_range', 'start_date', 'end_date'], 'safe'],
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
        $query = TiktokIncomeDetails::find();

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
        $query->andFilterWhere(['like', 'order_id', $this->order_id]);

        if (!empty($this->start_date) && !empty($this->end_date)) {
            // Assuming created_at or updated_at is the transaction date. 
            // Or maybe we should use a specific date from the API if available?
            // The model has 'created_at'. Let's use that for now, or 'updated_at'.
            // Actually, usually reports are based on 'created_at' of the record or the order date.
            // Let's use created_at for now.
            $query->andFilterWhere(['between', 'order_date', $this->start_date . ' 00:00:00', $this->end_date . ' 23:59:59']);
        }

        return $dataProvider;
    }

    public function getReportData($params)
    {
        $this->load($params);
        $query = TiktokIncomeDetails::find();

        if (!empty($this->order_id)) {
            $query->andWhere(['order_id' => $this->order_id]);
        }

        if (!empty($this->start_date) && !empty($this->end_date)) {
            $query->andFilterWhere(['between', 'order_date', $this->start_date . ' 00:00:00', $this->end_date . ' 23:59:59']);
        }

        return $query->all();
    }
}
