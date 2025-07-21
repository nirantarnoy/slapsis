<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Stocktrans;

/**
 * StocktransSearch represents the model behind the search form of `backend\models\Stocktrans`.
 */
class StocktransSearch extends Stocktrans
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'trans_type_id', 'product_id', 'created_by', 'created_at', 'stock_type_id','warehouse_id'], 'integer'],
            [['trans_date'], 'safe'],
            [['qty'], 'number'],
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
        $query = Stocktrans::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'trans_date' => $this->trans_date,
            'trans_type_id' => $this->trans_type_id,
            'product_id' => $this->product_id,
            'qty' => $this->qty,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'stock_type_id' => $this->stock_type_id,
            'warehouse_id' => $this->warehouse_id,
        ]);

       // $query->andFilterWhere(['like', 'journal_no', $this->journal_no]);

        return $dataProvider;
    }
}
