<?php

namespace backend\controllers;

use backend\models\UsergroupSearch;
use Yii;
use backend\models\User;
use backend\models\UserSearch;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Session;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends Controller
{
    public $enableCsrfValidation = false;
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST','GET'],
                ],
            ],
//            'access'=>[
//                'class'=>AccessControl::className(),
//                'denyCallback' => function ($rule, $action) {
//                    throw new ForbiddenHttpException('คุณไม่ได้รับอนุญาติให้เข้าใช้งาน!');
//                },
//                'rules'=>[
//                    [
//                        'allow'=>true,
//                        'roles'=>['@'],
//                        'matchCallback'=>function($rule,$action){
//                            $currentRoute = \Yii::$app->controller->getRoute();
//                            if(\Yii::$app->user->can($currentRoute)){
//                                return true;
//                            }
//                        }
//                    ]
//                ]
//            ],
        ];
    }

    /**
     * Lists all User models.
     * @return mixed
     */
    public function actionIndex()
    {
        $viewstatus = 1;

        if(\Yii::$app->request->get('viewstatus')!=null){
            $viewstatus = \Yii::$app->request->get('viewstatus');
        }

        $pageSize = \Yii::$app->request->post("perpage");
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        if($viewstatus ==1){
            $dataProvider->query->andFilterWhere(['status'=>$viewstatus]);
        }
        if($viewstatus == 2){
            $dataProvider->query->andFilterWhere(['status'=>0]);
        }

        $dataProvider->setSort(['defaultOrder' => ['id' => SORT_DESC]]);
        $dataProvider->pagination->pageSize = $pageSize;

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'perpage' => $pageSize,
            'viewstatus'=>$viewstatus,
        ]);
    }

    /**
     * Displays a single User model.
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
    public function actionUpdatecustomer($id)
    {
        $customer_id = 0;
        $model = $this->findModel($id);
        if($model){
            $customer_id = $model->customer_ref_id;
        }
       return $this->redirect(['customer/update', 'id' => $customer_id]);
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new User();

        if ($model->load(Yii::$app->request->post())) {
            $model->setPassword($model->pwd);
            $model->generateAuthKey();
            $model->email = $model->username . '@vorapat.com';
            //$model->status = $model->status == 1?10:9;
            if ($model->save()) {
                //$model->assignment();
                $session = Yii::$app->session;
                $session->setFlash('msg', 'บันทึกรายการเรียบร้อย');
                return $this->redirect(['index']);
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->getRoleByUser();

      //  print_r($model);return;
        if ($model->load(Yii::$app->request->post())) {
            //$model->status = $model->status == 1?10:9;
            if ($model->save()) {
                $model->assignment();
                $session = \Yii::$app->session;
                $session->setFlash('msg', 'บันทึกรายการเรียบร้อย');
                return $this->redirect(['index']);
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
    public function actionPrintlogindaily(){
        $id = \Yii::$app->request->post('find_customer_id');
        $from_date = \Yii::$app->request->post('from_date');
        $to_date = \Yii::$app->request->post('to_date');
        return $this->render('_logindaily',[
            'find_from_date' => $from_date,
            'find_to_date'=> $to_date,
            'find_customer_id'=>$id,
        ]);
    }
}
