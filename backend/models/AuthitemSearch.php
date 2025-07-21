<?php

namespace backend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Authitem;

/**
 * AuthitemSearch represents the model behind the search form of `backend\models\Authitem`.
 */
class AuthitemSearch extends Authitem
{
    public $globalSearch;
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'description', 'rule_name', 'data'], 'safe'],
            [['type', 'created_at', 'updated_at'], 'integer'],
            [['globalSearch'],'string'],
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
        $query = Authitem::find();

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
            'type' => $this->type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        if($this->globalSearch!='') {
            $query->orFilterWhere(['like', 'name', $this->globalSearch])
                  ->orFilterWhere(['like', 'description', $this->globalSearch])
                ->orFilterWhere(['like', 'rule_name', $this->globalSearch])
                ->orFilterWhere(['like', 'data', $this->globalSearch]);
        }
        return $dataProvider;
    }
}
