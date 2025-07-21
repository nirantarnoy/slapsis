<?php

namespace backend\controllers;

use Exception;
use Yii;
use backend\models\JournalTrans;
use backend\models\journalTransSearch;
use common\models\JournalTransLine;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\widgets\ActiveForm;

/**
 * JournaltransController implements the CRUD actions for JournalTrans model.
 */
class JournaltransController extends Controller
{
    public $enableCsrfValidation = false;
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST','GET'],
                    'delete-line' => ['POST','GET'],
                ],
            ],
        ];
    }

    /**
     * Lists all JournalTrans models.
     * @return mixed
     */
    public function actionIndex()
    {
        $pageSize = \Yii::$app->request->post("perpage");
        $searchModel = new \backend\models\JournalTransSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->setSort(['defaultOrder' => ['id' => SORT_ASC]]);
        $dataProvider->pagination->pageSize = $pageSize;

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'perpage' => $pageSize,
        ]);
    }

    /**
     * Displays a single JournalTrans model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $lines = $model->journalTransLines;

        return $this->render('view', [
            'model' => $model,
            'lines' => $lines,
        ]);
    }

    /**
     * Creates a new JournalTrans model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($type = null)
    {
        $model = new JournalTrans();
        $modelLines = [new JournalTransLine()];

        if ($model->load(\Yii::$app->request->post())) {
            $modelLines = $this->createMultiple(JournalTransLine::class);
            JournalTransLine::loadMultiple($modelLines, \Yii::$app->request->post());

            // Ajax validation
            if (\Yii::$app->request->isAjax) {
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return ArrayHelper::merge(
                    ActiveForm::validateMultiple($modelLines),
                    ActiveForm::validate($model)
                );
            }

            //custom model data
            $model->stock_type_id = 2;
            if($model->trans_type_id == 7 || $model->trans_type_id == 5){ // ยืม และ ส่งช่าง
                $model->status =1;
            }else{
                $model->status =3;
            }

            // Validate all models
            $valid = $model->validate();
           // $valid = JournalTransLine::validateMultiple($modelLines) && $valid;

            if ($valid) {
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    if ($flag = $model->save(false)) {
                        foreach ($modelLines as $modelLine) {
                            $modelLine->journal_trans_id = $model->id;
                            $modelLine->warehouse_id = $model->warehouse_id;
                            if (!($flag = $modelLine->save(false))) {
                                break;
                            }
                        }
                    }

                    if($flag) {
                        $total_qty = 0;
                        foreach ($modelLines as $modelLine) {
                            $total_qty += $modelLine->qty;
                            $model_stock_trans = new \common\models\StockTrans();
                            $model_stock_trans->trans_date = $model->trans_date;
                            $model_stock_trans->journal_trans_id = $model->id;
                            $model_stock_trans->trans_type_id = $model->trans_type_id;
                            $model_stock_trans->product_id = $modelLine->product_id;
                            $model_stock_trans->qty = $modelLine->qty;
                            $model_stock_trans->warehouse_id = $model->warehouse_id;
                            $model_stock_trans->stock_type_id = $model->stock_type_id;
                            $model_stock_trans->remark = $modelLine->remark;
                            $model_stock_trans->created_by = $model->created_by;
                            if($model_stock_trans->save(false)){
                                $this->calStock($modelLine->product_id,$model->stock_type_id,$model->warehouse_id,$modelLine->qty);
                            }
                        }
                       \backend\models\JournalTrans::updateAll(['qty' => $total_qty], ['id' => $model->id]);
                    }

                    if ($flag) {
                        $transaction->commit();
                        Yii::$app->session->setFlash('success', 'บันทึกข้อมูลสำเร็จ');
                        return $this->redirect(['view', 'id' => $model->id]);
                    }
                } catch (Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
                }
            }
        }

        return $this->render('create', [
            'model' => $model,
            'modelLines' => (empty($modelLines)) ? [new JournalTransLine()] : $modelLines,
            'create_type' => $type,
        ]);
    }

    /**
     * Updates an existing JournalTrans model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $modelLines = $model->journalTransLines;

        if ($model->load(Yii::$app->request->post())) {

            $oldIDs = ArrayHelper::map($modelLines, 'id', 'id');
            $modelLines = $this->createMultiple(JournalTransLine::class, $modelLines);
            JournalTransLine::loadMultiple($modelLines, Yii::$app->request->post());
            $deletedIDs = array_diff($oldIDs, array_filter(ArrayHelper::map($modelLines, 'id', 'id')));

            // Ajax validation
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ArrayHelper::merge(
                    ActiveForm::validateMultiple($modelLines),
                    ActiveForm::validate($model)
                );
            }

            // Validate all models
            $valid = $model->validate();
            //$valid = JournalTransLine::validateMultiple($modelLines) && $valid;

            if ($valid) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    if ($flag = $model->save(false)) {
                        if (!empty($deletedIDs)) {
                            JournalTransLine::deleteAll(['id' => $deletedIDs]);
                        }
                        $total_qty = 0;
                        foreach ($modelLines as $modelLine) {
                            $total_qty += $modelLine->qty;
                            $modelLine->journal_trans_id = $model->id;
                            if (!($flag = $modelLine->save(false))) {
                                break;
                            }
                        }
                        \backend\models\JournalTrans::updateAll(['qty' => $total_qty], ['id' => $model->id]);
                    }

                    if ($flag) {
                        $transaction->commit();
                        Yii::$app->session->setFlash('success', 'บันทึกข้อมูลสำเร็จ');
                        return $this->redirect(['view', 'id' => $model->id]);
                    }
                } catch (Exception $e) {
                    $transaction->rollBack();
                    Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
                }
            }
        }

        return $this->render('update', [
            'model' => $model,
            'modelLines' => (empty($modelLines)) ? [new JournalTransLine()] : $modelLines
        ]);
    }

    /**
     * Deletes an existing JournalTrans model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Delete all related lines first
            JournalTransLine::deleteAll(['journal_trans_id' => $id]);

            // Delete master
            $model->delete();

            $transaction->commit();
            Yii::$app->session->setFlash('success', 'ลบข้อมูลสำเร็จ');
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    /**
     * Finds the JournalTrans model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return JournalTrans the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = JournalTrans::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    public function calStock($product_id,$stock_type_id,$warehouse_id,$qty){
        if($product_id && $stock_type_id && $qty){
           if($stock_type_id == 2){ // stock out
               $model = \common\models\StockSum::find()->where(['product_id'=>$product_id,'warehouse_id'=>$warehouse_id])->andWhere(['>=','qty',$qty])->one();
               if($model){
                   $model->qty -= $qty;
                   $model->save(false);
               }
           }
           if($stock_type_id == 1){ // stock in
               $model = \common\models\StockSum::find()->where(['product_id'=>$product_id,'warehouse_id'=>$warehouse_id])->one();
               if($model){
                   $model->qty += $qty;
                   $model->save(false);
               }else{
                   $model = new \common\models\StockSum();
                   $model->product_id = $product_id;
                   $model->warehouse_id = $warehouse_id;
                   $model->qty = $qty;
                   $model->updated_at = date('Y-m-d H:i:s');
                   if($model->save(false)){
                       $model_product = \backend\models\Product::findOne($product_id);
                       if($model_product){
                           $model_product->stock_qty += $qty; // update stock product
                           $model_product->save(false);
                       }
                   }
               }
           }
        }
    }

    /**
     * Function to create multiple models
     */
    protected function createMultiple($modelClass, $multipleModels = [])
    {
        $model = new $modelClass;
        $formName = $model->formName();
        $post = Yii::$app->request->post($formName);
        $models = [];

        if (!empty($multipleModels)) {
            $keys = array_keys(ArrayHelper::map($multipleModels, 'id', 'id'));
            $multipleModels = array_combine($keys, $multipleModels);
        }

        if ($post && is_array($post)) {
            foreach ($post as $i => $item) {
                if (isset($item['id']) && !empty($item['id']) && isset($multipleModels[$item['id']])) {
                    $models[] = $multipleModels[$item['id']];
                } else {
                    $models[] = new $modelClass;
                }
            }
        }

        unset($model, $formName, $post);

        return $models;
    }

    public function actionAddreturnproduct(){
         $product_id = \Yii::$app->request->post('product_id');
         $journal_trans_id = \Yii::$app->request->post('journal_trans_id');
         $qty = \Yii::$app->request->post('return_qty');
         $remark = \Yii::$app->request->post('return_remark');
         $return_to_type = \Yii::$app->request->post('return_to_type');
         $trans_type_id = \Yii::$app->request->post('trans_type_id');

         if($journal_trans_id && $qty !=null && $trans_type_id != null){
             $model = new \backend\models\JournalTrans();
             $model->trans_date = date('Y-m-d H:i:s');
             $model->journal_no = '';
             $model->remark = '';
             $model->trans_type_id = $trans_type_id; // 8 = คืนสินค้าช่าง 5 = คืนยืม
             $model->status = 3;
             $model->stock_type_id = 1; // 1 เข้า 2 ออก
             $model->trans_ref_id = $journal_trans_id;
             if($model->save(false)){
                 if($product_id != null){
                     for($i=0;$i<count($product_id);$i++){
                         $model_line = new \common\models\JournalTransLine();
                         $model_line->journal_trans_id = $model->id;
                         $model_line->product_id = $product_id[$i];
                         $model_line->qty = $qty[$i];
                         $model_line->remark = $remark[$i];
                         $model_line->warehouse_id = 1; // default
                         $model_line->return_to_type = $return_to_type != null ? $return_to_type[$i] : null;
                         if($model_line->save(false)){
                             $model_stock_trans = new \common\models\StockTrans();
                             $model_stock_trans->trans_date = date('Y-m-d H:i:s');
                             $model_stock_trans->trans_type_id = 8;
                             $model_stock_trans->product_id = $product_id[$i];
                             $model_stock_trans->journal_trans_id = $model->id;
                             $model_stock_trans->qty = $qty[$i];
                             $model_stock_trans->remark = $remark[$i];
                             $model_stock_trans->stock_type_id =1;
                             if($model_stock_trans->save(false)){
                                 $this->calStock($product_id[$i],1,1,$qty[$i]); // stock in
                             }
                             if($return_to_type!= null){ // update product type
                             \backend\models\Product::updateAll(['product_type_id'=>$return_to_type[$i]],['id'=>$product_id[$i]]);
                             }
                         }
                     }
                 }
                 $this->calForcomplete($journal_trans_id,$model->id);
             }

         }
         return $this->redirect(['journaltrans/view', 'id' => $journal_trans_id]);
    }
    function calForcomplete($journal_trans_origin_id,$journal_return_id){
        if($journal_trans_origin_id && $journal_return_id){
            $return_sum = \common\models\JournalTransLine::find()->where(['journal_trans_id'=>$journal_return_id])->sum('qty');
            $trans_sum = \common\models\JournalTransLine::find()->where(['journal_trans_id'=>$journal_trans_origin_id])->sum('qty');
            if($return_sum == $trans_sum){
                $model = \backend\models\JournalTrans::findOne($journal_trans_origin_id);
                $model->status = 3; // trans complete
                $model->save(false);
            }
        }
    }
}