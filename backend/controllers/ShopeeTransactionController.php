<?php

namespace backend\controllers;

use Yii;
use backend\models\ShopeeTransaction;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

class ShopeeTransactionController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all ShopeeTransaction models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ShopeeTransaction();
        $query = ShopeeTransaction::find();

        // ค้นหาตาม transaction_id
        if (!empty(Yii::$app->request->queryParams['transaction_id'])) {
            $query->andWhere(['like', 'transaction_id', Yii::$app->request->queryParams['transaction_id']]);
        }

        // ค้นหาตาม order_sn
        if (!empty(Yii::$app->request->queryParams['order_sn'])) {
            $query->andWhere(['like', 'order_sn', Yii::$app->request->queryParams['order_sn']]);
        }

        // ค้นหาตาม transaction_type
        if (!empty(Yii::$app->request->queryParams['transaction_type'])) {
            $query->andWhere(['transaction_type' => Yii::$app->request->queryParams['transaction_type']]);
        }

        // ค้นหาตาม status
        if (!empty(Yii::$app->request->queryParams['status'])) {
            $query->andWhere(['status' => Yii::$app->request->queryParams['status']]);
        }

        // ค้นหาตาม fee_category
        if (!empty(Yii::$app->request->queryParams['fee_category'])) {
            $query->andWhere(['fee_category' => Yii::$app->request->queryParams['fee_category']]);
        }

        // ค้นหาตามช่วงวันที่
        if (!empty(Yii::$app->request->queryParams['date_from'])) {
            $query->andWhere(['>=', 'transaction_date', Yii::$app->request->queryParams['date_from']]);
        }
        if (!empty(Yii::$app->request->queryParams['date_to'])) {
            $query->andWhere(['<=', 'transaction_date', Yii::$app->request->queryParams['date_to']]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => [
                    'transaction_date' => SORT_DESC,
                ]
            ],
        ]);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single ShopeeTransaction model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Finds the ShopeeTransaction model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return ShopeeTransaction the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = ShopeeTransaction::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}