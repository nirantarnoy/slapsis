<?php

namespace backend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Product;

/**
 * ProductSearch represents the model behind the search form of `backend\models\Product`.
 */
class ProductSearch extends Product
{
    public $globalSearch;
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'product_category_id', 'status','brand_id'], 'integer'],
            [['code', 'name', 'description'], 'safe'],
            [['globalSearch'],'string']
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
        $query = Product::find();

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
            'product_category_id' => $this->product_category_id,
            'brand_id' => $this->brand_id,
            'status' => $this->status,
        ]);

        if($this->globalSearch != ''){
            $query->orFilterWhere(['like', 'code', $this->globalSearch])
                ->orFilterWhere(['like', 'name', $this->globalSearch])
                ->orFilterWhere(['like', 'description', $this->globalSearch]);
        }


        return $dataProvider;
    }
}
