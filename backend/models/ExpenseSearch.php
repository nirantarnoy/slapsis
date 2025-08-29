<?php
namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class ExpenseSearch extends Expense
{
    public $dateFrom;
    public $dateTo;

    public function rules()
    {
        return [
            [['id', 'created_by'], 'integer'],
            [['category', 'description', 'status', 'dateFrom', 'dateTo'], 'safe'],
            [['amount'], 'number'],
        ];
    }

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Expense::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['expense_date' => SORT_DESC]
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'amount' => $this->amount,
            'created_by' => $this->created_by,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'category', $this->category])
            ->andFilterWhere(['like', 'description', $this->description]);

        // Date range filter
        if ($this->dateFrom) {
            $query->andWhere(['>=', 'expense_date', $this->dateFrom]);
        }
        if ($this->dateTo) {
            $query->andWhere(['<=', 'expense_date', $this->dateTo]);
        }

        return $dataProvider;
    }
}