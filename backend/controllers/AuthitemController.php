<?php

namespace backend\controllers;

use Yii;
use backend\models\Authitem;
use backend\models\AuthitemSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * AuthitemController implements the CRUD actions for Authitem model.
 */
class AuthitemController extends Controller
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
                    'delete' => ['POST', 'GET'],
                ],
            ],
//            'access'=>[
//                'class'=>AccessControl::className(),
//                'rules'=>[
//                    [
//                        'allow'=>true,
//                        'actions'=>['index','create','update','view','resetpassword','managerule','initpermission','test'],
//                        'roles'=>['@'],
//                    ],
//                    [
//                        'allow'=>true,
//                        'actions'=>['delete'],
//                        'roles'=>['System Administrator'],
//                    ]
//
//                ]
//            ]
        ];
    }

    /**
     * Lists all Authitem models.
     * @return mixed
     */
    public function actionIndex()
    {
        $pageSize = \Yii::$app->request->post("perpage");
        $searchModel = new AuthitemSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->andFilterWhere(['type' => 1]);
        $dataProvider->pagination->pageSize = $pageSize;

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'perpage' => $pageSize,
        ]);
    }

    /**
     * Displays a single Authitem model.
     * @param string $id
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
     * Creates a new Authitem model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Authitem();

        if ($model->load(Yii::$app->request->post())) {
            $auth = Yii::$app->authManager;
            //$auth->removeAll();

            if ($model->type == 1) {
                $newrole = $auth->createRole($model->name);
                $newrole->description = $model->description;
                $newrole->type = $model->type;
                $auth->add($newrole);
            } else if ($model->type == 2) {
                $newrole = $auth->createPermission($model->name);
                $newrole->description = $model->description;
                $newrole->type = $model->type;
                $auth->add($newrole);
            }


//            $manage_plant = $auth->createRole('Manage Plant');
//            $manage_plant->description = "Manage plant";
//            $auth->add($manage_plant);
//            $auth->addChild($manage_plant,$plant_permission);

            $childlist = $model->child_list;


            //  if(sizeof($childlist)>0 && $childlist != null){
            if ($childlist != null && $childlist != null) {
                for ($i = 0; $i <= count($childlist) - 1; $i++) {
                    if ($model->type == 1) {
                        $olditem = $auth->getRole($model->name);
                        $childitem = $auth->getPermission($childlist[$i]);
                        $auth->addChild($olditem, $childitem);
                    } else if ($model->type == 2) {
                        $olditem = $auth->getPermission($model->name);
                        $childitem = $auth->getPermission($childlist[$i]);
                        $auth->addChild($olditem, $childitem);
                    }

                }

            }

            $session = Yii::$app->session;
            $session->setFlash('msg', 'บันทึกรายการเรียบร้อย');
            return $this->redirect(['index']);

        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Authitem model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $modelchild = \backend\models\Auhtitemchild::find()->where(['parent' => $model->name])->all();

        if ($model->load(Yii::$app->request->post())) {

            $childlist = $model->child_list;

            if ($model->type == 1) {
                $auth = Yii::$app->authManager;
                $olditem = $auth->getRole($model->name);
                $olditem->description = $model->description;
            } else if ($model->type == 2) {
                $auth = Yii::$app->authManager;
                $olditem = $auth->getPermission($model->name);
                $olditem->description = $model->description;
            }

            //$olditem->type = $model->type;

            // print_r($olditem);return;
            $auth->update($model->name, $olditem);

            if (sizeof($childlist) > 0) {
                $auth->removeChildren($olditem);
                for ($i = 0; $i <= count($childlist) - 1; $i++) {
                    //$achild = $auth->getChildren($childlist[$i]);
                    // print_r($childlist[$i]);return;
                    if ($auth->getRole($childlist[$i])) {
                        $childitem = $auth->getRole($childlist[$i]);
                        $auth->addChild($olditem, $childitem);
                    } else {
                        $childitem = $auth->getPermission($childlist[$i]);
                        $auth->addChild($olditem, $childitem);
                    }

                }

            }

            $session = Yii::$app->session;
            $session->setFlash('msg', 'บันทึกรายการเรียบร้อย');
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
            'modelchild' => $modelchild,
        ]);
    }

    /**
     * Deletes an existing Authitem model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        //if (\Yii::$app->user->can('deleteRecord', ['user_id' => Yii::$app->user->id])) {

//           if(\Yii::$app->user->get=='System Administrator'){
//               return  false;
//    }
//        $user_roles = \Yii::$app->authManager->getRolesByUser(Yii::$app->user->getId());
//        if ($user_roles != null) {
//            foreach ($user_roles as $value) {
//                if ($value->name == 'System Administrator') {
//
//                }
//            }
//        }
        $this->findModel($id)->delete();
        return $this->redirect(['index']);
        //}else{

        //}

    }

    /**
     * Finds the Authitem model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Authitem the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Authitem::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    public function actionInitpermission()
    {
        $auth = Yii::$app->authManager;
        $auth->removeAll();


        $plant_index = $auth->createPermission('plant/index');
        $auth->add($plant_index);
        $plant_update = $auth->createPermission('plant/update');
        $auth->add($plant_update);
        $plant_delete = $auth->createPermission('plant/delete');
        $auth->add($plant_delete);
        $plant_view = $auth->createPermission('plant/view');
        $auth->add($plant_view);
        $plant_create = $auth->createPermission('plant/create');
        $auth->add($plant_create);

        $plant_permission = $auth->createPermission('plantmodule');
        $plant_permission->description = "สิทธิ์ใช้งานโมดูล plant";
        $auth->add($plant_permission);

        $auth->addChild($plant_permission, $plant_index);
        $auth->addChild($plant_permission, $plant_view);
        $auth->addChild($plant_permission, $plant_update);
        $auth->addChild($plant_permission, $plant_delete);
        $auth->addChild($plant_permission, $plant_create);

        $manage_plant = $auth->createRole('Manage plant');
        $manage_plant->description = "Manage plant";
        $auth->add($manage_plant);
        $auth->addChild($manage_plant, $plant_permission);


        $customer_index = $auth->createPermission('customer/index');
        $auth->add($customer_index);
        $customer_update = $auth->createPermission('customer/update');
        $auth->add($customer_update);
        $customer_delete = $auth->createPermission('customer/delete');
        $auth->add($customer_delete);
        $customer_view = $auth->createPermission('customer/view');
        $auth->add($customer_view);
        $customer_create = $auth->createPermission('customer/create');
        $auth->add($customer_create);

        $customer_permission = $auth->createPermission('customermodule');
        $customer_permission->description = "สิทธิ์ใช้งานโมดูล customer";
        $auth->add($customer_permission);

        $auth->addChild($customer_permission, $customer_index);
        $auth->addChild($customer_permission, $customer_view);
        $auth->addChild($customer_permission, $customer_update);
        $auth->addChild($customer_permission, $customer_delete);
        $auth->addChild($customer_permission, $customer_create);

        $manage_customer = $auth->createRole('Manage customer');
        $manage_customer->description = "Manage customer";
        $auth->add($manage_customer);
        $auth->addChild($manage_customer, $customer_permission);

        $customergroup_index = $auth->createPermission('customergroup/index');
        $auth->add($customergroup_index);
        $customergroup_update = $auth->createPermission('customergroup/update');
        $auth->add($customergroup_update);
        $customergroup_delete = $auth->createPermission('customergroup/delete');
        $auth->add($customergroup_delete);
        $customergroup_view = $auth->createPermission('customergroup/view');
        $auth->add($customergroup_view);
        $customergroup_create = $auth->createPermission('customergroup/create');
        $auth->add($customergroup_create);

        $customergroup_permission = $auth->createPermission('customergroupmodule');
        $customergroup_permission->description = "สิทธิ์ใช้งานโมดูล customergroup";
        $auth->add($customergroup_permission);

        $auth->addChild($customergroup_permission, $customergroup_index);
        $auth->addChild($customergroup_permission, $customergroup_view);
        $auth->addChild($customergroup_permission, $customergroup_update);
        $auth->addChild($customergroup_permission, $customergroup_delete);
        $auth->addChild($customergroup_permission, $customergroup_create);

        $manage_customergroup = $auth->createRole('Manage customergroup');
        $manage_customergroup->description = "Manage customergroup";
        $auth->add($manage_customergroup);
        $auth->addChild($manage_customergroup, $customergroup_permission);


        $bike_index = $auth->createPermission('bike/index');
        $auth->add($bike_index);
        $bike_update = $auth->createPermission('bike/update');
        $auth->add($bike_update);
        $bike_delete = $auth->createPermission('bike/delete');
        $auth->add($bike_delete);
        $bike_view = $auth->createPermission('bike/view');
        $auth->add($bike_view);
        $bike_create = $auth->createPermission('bike/create');
        $auth->add($bike_create);

        $bike_permission = $auth->createPermission('bikemodule');
        $bike_permission->description = "สิทธิ์ใช้งานโมดูล bike";
        $auth->add($bike_permission);

        $auth->addChild($bike_permission, $bike_index);
        $auth->addChild($bike_permission, $bike_view);
        $auth->addChild($bike_permission, $bike_update);
        $auth->addChild($bike_permission, $bike_delete);
        $auth->addChild($bike_permission, $bike_create);

        $manage_bike = $auth->createRole('Manage bike');
        $manage_bike->description = "Manage bike";
        $auth->add($manage_bike);
        $auth->addChild($manage_bike, $bike_permission);

        $brand_index = $auth->createPermission('brand/index');
        $auth->add($brand_index);
        $brand_update = $auth->createPermission('brand/update');
        $auth->add($brand_update);
        $brand_delete = $auth->createPermission('brand/delete');
        $auth->add($brand_delete);
        $brand_view = $auth->createPermission('brand/view');
        $auth->add($brand_view);
        $brand_create = $auth->createPermission('brand/create');
        $auth->add($brand_create);

        $brand_permission = $auth->createPermission('brandmodule');
        $brand_permission->description = "สิทธิ์ใช้งานโมดูล brand";
        $auth->add($brand_permission);

        $auth->addChild($brand_permission, $brand_index);
        $auth->addChild($brand_permission, $brand_view);
        $auth->addChild($brand_permission, $brand_update);
        $auth->addChild($brand_permission, $brand_delete);
        $auth->addChild($brand_permission, $brand_create);

        $manage_brand = $auth->createRole('Manage brand');
        $manage_brand->description = "Manage brand";
        $auth->add($manage_brand);
        $auth->addChild($manage_brand, $brand_permission);


        $contactform_index = $auth->createPermission('contactform/index');
        $auth->add($contactform_index);
        $contactform_update = $auth->createPermission('contactform/update');
        $auth->add($contactform_update);
        $contactform_delete = $auth->createPermission('contactform/delete');
        $auth->add($contactform_delete);
        $contactform_view = $auth->createPermission('contactform/view');
        $auth->add($contactform_view);
        $contactform_create = $auth->createPermission('contactform/create');
        $auth->add($contactform_create);
        $contactform_print = $auth->createPermission('contactform/print');
        $auth->add($contactform_print);
        $contactform_color = $auth->createPermission('contactform/findcolor');
        $auth->add($contactform_color);
        $contactform_price = $auth->createPermission('contactform/findprice');
        $auth->add($contactform_price);


        $contactform_permission = $auth->createPermission('contactformmodule');
        $contactform_permission->description = "สิทธิ์ใช้งานโมดูล contactform";
        $auth->add($contactform_permission);

        $auth->addChild($contactform_permission, $contactform_index);
        $auth->addChild($contactform_permission, $contactform_view);
        $auth->addChild($contactform_permission, $contactform_update);
        $auth->addChild($contactform_permission, $contactform_delete);
        $auth->addChild($contactform_permission, $contactform_create);
        $auth->addChild($contactform_permission, $contactform_color);
        $auth->addChild($contactform_permission, $contactform_price);
        $auth->addChild($contactform_permission, $contactform_print);

        $manage_contactform = $auth->createRole('Manage contactform');
        $manage_contactform->description = "Manage contactform";
        $auth->add($manage_contactform);
        $auth->addChild($manage_contactform, $contactform_permission);

        $sale_index = $auth->createPermission('sale/index');
        $auth->add($sale_index);
        $sale_update = $auth->createPermission('sale/update');
        $auth->add($sale_update);
        $sale_delete = $auth->createPermission('sale/delete');
        $auth->add($sale_delete);
        $sale_view = $auth->createPermission('sale/view');
        $auth->add($sale_view);
        $sale_create = $auth->createPermission('sale/create');
        $auth->add($sale_create);
        $sale_customer = $auth->createPermission('sale/getcustomer');
        $auth->add($sale_customer);
        $sale_print = $auth->createPermission('sale/print');
        $auth->add($sale_print);
        $sale_print_new = $auth->createPermission('sale/printnew');
        $auth->add($sale_print_new);

        $sale_permission = $auth->createPermission('salemodule');
        $sale_permission->description = "สิทธิ์ใช้งานโมดูล sale";
        $auth->add($sale_permission);

        $auth->addChild($sale_permission, $sale_index);
        $auth->addChild($sale_permission, $sale_view);
        $auth->addChild($sale_permission, $sale_update);
        $auth->addChild($sale_permission, $sale_delete);
        $auth->addChild($sale_permission, $sale_create);
        $auth->addChild($sale_permission, $sale_customer);
        $auth->addChild($sale_permission, $sale_print);
        $auth->addChild($sale_permission, $sale_print_new);

        $manage_sale = $auth->createRole('Manage sale');
        $manage_sale->description = "Manage sale";
        $auth->add($manage_sale);
        $auth->addChild($manage_sale, $sale_permission);

        $fn_index = $auth->createPermission('fn/index');
        $auth->add($fn_index);
        $fn_update = $auth->createPermission('fn/update');
        $auth->add($fn_update);
        $fn_delete = $auth->createPermission('fn/delete');
        $auth->add($fn_delete);
        $fn_view = $auth->createPermission('fn/view');
        $auth->add($fn_view);
        $fn_create = $auth->createPermission('fn/create');
        $auth->add($fn_create);

        $fn_permission = $auth->createPermission('fnmodule');
        $fn_permission->description = "สิทธิ์ใช้งานโมดูล fn";
        $auth->add($fn_permission);

        $auth->addChild($fn_permission, $fn_index);
        $auth->addChild($fn_permission, $fn_view);
        $auth->addChild($fn_permission, $fn_update);
        $auth->addChild($fn_permission, $fn_delete);
        $auth->addChild($fn_permission, $fn_create);

        $manage_fn = $auth->createRole('Manage fn');
        $manage_fn->description = "Manage fn";
        $auth->add($manage_fn);
        $auth->addChild($manage_fn, $fn_permission);

        $owe_index = $auth->createPermission('owe/index');
        $auth->add($owe_index);
        $owe_update = $auth->createPermission('owe/update');
        $auth->add($owe_update);
        $owe_delete = $auth->createPermission('owe/delete');
        $auth->add($owe_delete);
        $owe_view = $auth->createPermission('owe/view');
        $auth->add($owe_view);
        $owe_create = $auth->createPermission('owe/create');
        $auth->add($owe_create);

        $owe_permission = $auth->createPermission('owemodule');
        $owe_permission->description = "สิทธิ์ใช้งานโมดูล owe";
        $auth->add($owe_permission);

        $auth->addChild($owe_permission, $owe_index);
        $auth->addChild($owe_permission, $owe_view);
        $auth->addChild($owe_permission, $owe_update);
        $auth->addChild($owe_permission, $owe_delete);
        $auth->addChild($owe_permission, $owe_create);

        $manage_owe = $auth->createRole('Manage owe');
        $manage_owe->description = "Manage owe";
        $auth->add($manage_owe);
        $auth->addChild($manage_owe, $owe_permission);

        $authitem_index = $auth->createPermission('authitem/index');
        $auth->add($authitem_index);
        $authitem_update = $auth->createPermission('authitem/update');
        $auth->add($authitem_update);
        $authitem_delete = $auth->createPermission('authitem/delete');
        $auth->add($authitem_delete);
        $authitem_view = $auth->createPermission('authitem/view');
        $auth->add($authitem_view);
        $authitem_create = $auth->createPermission('authitem/create');
        $auth->add($authitem_create);

        $authitem_permission = $auth->createPermission('authitemmodule');
        $authitem_permission->description = "สิทธิ์ใช้งานโมดูล authitem";
        $auth->add($authitem_permission);

        $auth->addChild($authitem_permission, $authitem_index);
        $auth->addChild($authitem_permission, $authitem_view);
        $auth->addChild($authitem_permission, $authitem_update);
        $auth->addChild($authitem_permission, $authitem_delete);
        $auth->addChild($authitem_permission, $authitem_create);

        $manage_authitem = $auth->createRole('Manage authitem');
        $manage_authitem->description = "Manage authitem";
        $auth->add($manage_authitem);
        $auth->addChild($manage_authitem, $authitem_permission);

        $plate_index = $auth->createPermission('plate/index');
        $auth->add($plate_index);
        $plate_update = $auth->createPermission('plate/update');
        $auth->add($plate_update);
        $plate_delete = $auth->createPermission('plate/delete');
        $auth->add($plate_delete);
        $plate_view = $auth->createPermission('plate/view');
        $auth->add($plate_view);
        $plate_create = $auth->createPermission('plate/create');
        $auth->add($plate_create);

        $plate_permission = $auth->createPermission('platemodule');
        $plate_permission->description = "สิทธิ์ใช้งานโมดูล plate";
        $auth->add($plate_permission);

        $auth->addChild($plate_permission, $plate_index);
        $auth->addChild($plate_permission, $plate_view);
        $auth->addChild($plate_permission, $plate_update);
        $auth->addChild($plate_permission, $plate_delete);
        $auth->addChild($plate_permission, $plate_create);

        $manage_plate = $auth->createRole('Manage plate');
        $manage_plate->description = "Manage plate";
        $auth->add($manage_plate);
        $auth->addChild($manage_plate, $plate_permission);

        $report_index = $auth->createPermission('report/index');
        $auth->add($report_index);
        $report_update = $auth->createPermission('report/update');
        $auth->add($report_update);
        $report_delete = $auth->createPermission('report/delete');
        $auth->add($report_delete);
        $report_view = $auth->createPermission('report/view');
        $auth->add($report_view);
        $report_create = $auth->createPermission('report/create');
        $auth->add($report_create);
        $report_sale = $auth->createPermission('report/salereport');
        $auth->add($report_sale);
        $report_print = $auth->createPermission('report/printmanual');
        $auth->add($report_print);

        $report_permission = $auth->createPermission('reportmodule');
        $report_permission->description = "สิทธิ์ใช้งานโมดูล report";
        $auth->add($report_permission);

        $auth->addChild($report_permission, $report_index);
        $auth->addChild($report_permission, $report_view);
        $auth->addChild($report_permission, $report_update);
        $auth->addChild($report_permission, $report_delete);
        $auth->addChild($report_permission, $report_create);

        $manage_report = $auth->createRole('Manage report');
        $manage_report->description = "Manage report";
        $auth->add($manage_report);
        $auth->addChild($manage_report, $report_permission);

        $site_index = $auth->createPermission('site/index');
        $auth->add($site_index);
        $site_update = $auth->createPermission('site/update');
        $auth->add($site_update);
        $site_delete = $auth->createPermission('site/delete');
        $auth->add($site_delete);
        $site_view = $auth->createPermission('site/view');
        $auth->add($site_view);
        $site_create = $auth->createPermission('site/create');
        $auth->add($site_create);
        $site_login = $auth->createPermission('site/login');
        $auth->add($site_login);
        $site_logout = $auth->createPermission('site/logout');
        $auth->add($site_logout);

        $site_permission = $auth->createPermission('sitemodule');
        $site_permission->description = "สิทธิ์ใช้งานโมดูล site";
        $auth->add($site_permission);

        $auth->addChild($site_permission, $site_index);
        $auth->addChild($site_permission, $site_view);
        $auth->addChild($site_permission, $site_update);
        $auth->addChild($site_permission, $site_delete);
        $auth->addChild($site_permission, $site_create);
        $auth->addChild($site_permission, $site_logout);
        $auth->addChild($site_permission, $site_login);

        $manage_site = $auth->createRole('Manage site');
        $manage_site->description = "Manage site";
        $auth->add($manage_site);
        $auth->addChild($manage_site, $site_permission);

        $user_index = $auth->createPermission('user/index');
        $auth->add($user_index);
        $user_update = $auth->createPermission('user/update');
        $auth->add($user_update);
        $user_delete = $auth->createPermission('user/delete');
        $auth->add($user_delete);
        $user_view = $auth->createPermission('user/view');
        $auth->add($user_view);
        $user_create = $auth->createPermission('user/create');
        $auth->add($user_create);

        $user_permission = $auth->createPermission('usermodule');
        $user_permission->description = "สิทธิ์ใช้งานโมดูล user";
        $auth->add($user_permission);

        $auth->addChild($user_permission, $user_index);
        $auth->addChild($user_permission, $user_view);
        $auth->addChild($user_permission, $user_update);
        $auth->addChild($user_permission, $user_delete);
        $auth->addChild($user_permission, $user_create);


        $manage_user = $auth->createRole('Manage user');
        $manage_user->description = "Manage user";
        $auth->add($manage_user);
        $auth->addChild($manage_user, $user_permission);


        $usergroup_index = $auth->createPermission('usergroup/index');
        $auth->add($usergroup_index);
        $usergroup_update = $auth->createPermission('usergroup/update');
        $auth->add($usergroup_update);
        $usergroup_delete = $auth->createPermission('usergroup/delete');
        $auth->add($usergroup_delete);
        $usergroup_view = $auth->createPermission('usergroup/view');
        $auth->add($usergroup_view);
        $usergroup_create = $auth->createPermission('usergroup/create');
        $auth->add($usergroup_create);

        $usergroup_permission = $auth->createPermission('usergroupmodule');
        $usergroup_permission->description = "สิทธิ์ใช้งานโมดูล usergroup";
        $auth->add($usergroup_permission);

        $auth->addChild($usergroup_permission, $usergroup_index);
        $auth->addChild($usergroup_permission, $usergroup_view);
        $auth->addChild($usergroup_permission, $usergroup_update);
        $auth->addChild($usergroup_permission, $usergroup_delete);
        $auth->addChild($usergroup_permission, $usergroup_create);


        $manage_usergroup = $auth->createRole('Manage usergroup');
        $manage_usergroup->description = "Manage usergroup";
        $auth->add($manage_usergroup);
        $auth->addChild($manage_usergroup, $usergroup_permission);


        $admin_role = $auth->createRole('แอดมินร้าน');
        $admin_role->description = "ผู้ดูแลระบบ";
        $auth->add($admin_role);

        $auth->addChild($admin_role, $manage_plant);
        $auth->addChild($admin_role, $manage_bike);
        $auth->addChild($admin_role, $manage_brand);
        $auth->addChild($admin_role, $manage_fn);
        // $auth->addChild($admin_role,$manage_site);
        $auth->addChild($admin_role, $manage_plate);
        $auth->addChild($admin_role, $manage_customer);
        $auth->addChild($admin_role, $manage_customergroup);
        $auth->addChild($admin_role, $manage_contactform);
        $auth->addChild($admin_role, $manage_report);
        $auth->addChild($admin_role, $manage_authitem);
        $auth->addChild($admin_role, $manage_sale);
        $auth->addChild($admin_role, $manage_owe);
        $auth->addChild($admin_role, $manage_user);
        $auth->addChild($admin_role, $manage_usergroup);

        $user_role = $auth->createRole('พนักงานร้าน');
        $user_role->description = "ผู้ใช้งานทั่วไป";
        $auth->add($user_role);


        $auth->assign($admin_role, 1);
        $auth->assign($user_role, 1);


    }

    public function actionTest()
    {
        $auth = Yii::$app->authManager;
//        $user_index = $auth->createPermission('user/index');
//        $auth->add($user_index);
//        $user_update = $auth->createPermission('user/update');
//        $auth->add($user_update);
//        $user_delete = $auth->createPermission('user/delete');
//        $auth->add($user_delete);
//        $user_view = $auth->createPermission('user/view');
//        $auth->add($user_view);
//        $user_create = $auth->createPermission('user/create');
//        $auth->add($user_create);
//
//
//        $usergroup_index = $auth->createPermission('usergroup/index');
//        $auth->add($usergroup_index);
//        $usergroup_update = $auth->createPermission('usergroup/update');
//        $auth->add($usergroup_update);
//        $usergroup_delete = $auth->createPermission('usergroup/delete');
//        $auth->add($usergroup_delete);
//        $usergroup_view = $auth->createPermission('usergroup/view');
//        $auth->add($usergroup_view);
//        $usergroup_create = $auth->createPermission('usergroup/create');
//        $auth->add($usergroup_create);
        $site_login = $auth->createPermission('site/login');
        $auth->add($site_login);
        $site_logout = $auth->createPermission('site/logout');
        $auth->add($site_logout);
    }

    public function actionManagerule()
    {

        $auth = Yii::$app->authManager;
        $auth->removeAll();

        $rule = new \common\rbac\DeleteRecordRule(); // rule ที่สร้างไว้
        $auth->add($rule);

        // site module

        $site_index = $auth->createPermission('site/index');
        $auth->add($site_index);
        $site_logout = $auth->createPermission('site/logout');
        $auth->add($site_logout);
        $site_login = $auth->createPermission('site/login');
        $auth->add($site_login);
//
        $site_permission = $auth->createPermission('sitemodule');
        $site_permission->description = "หน้าหลัก";
        $auth->add($site_permission);
        $auth->addChild($site_permission, $site_index);
        $auth->addChild($site_permission, $site_logout);

        $suplier = $auth->createRole('Suplier');
        $suplier->description = "Suplier";
        $auth->add($suplier);
        $auth->addChild($suplier, $site_permission);

        // plan_module
        $plant_index = $auth->createPermission('plant/index');
        $auth->add($plant_index);
        $plant_update = $auth->createPermission('plant/update');
        $auth->add($plant_update);
        $plant_delete = $auth->createPermission('plant/delete');
        $auth->add($plant_delete);
        $plant_view = $auth->createPermission('plant/view');
        $auth->add($plant_view);
        $plant_create = $auth->createPermission('plant/create');
        $auth->add($plant_create);
        $plant_deletelogo = $auth->createPermission('plant/deletelogo');
        $auth->add($plant_deletelogo);

        $plant_permission = $auth->createPermission('plantmodule');
        $plant_permission->description = "สิทธิ์ใช้งานโมดูล Plant";
        $auth->add($plant_permission);

        $auth->addChild($plant_permission, $plant_index);
        $auth->addChild($plant_permission, $plant_view);
        $auth->addChild($plant_permission, $plant_update);
        $auth->addChild($plant_permission, $plant_delete);
        $auth->addChild($plant_permission, $plant_create);
        $auth->addChild($plant_permission, $plant_deletelogo);

        $manage_plant = $auth->createRole('Manage Plant');
        $manage_plant->description = "Manage plant";
        $auth->add($manage_plant);
        $auth->addChild($manage_plant, $plant_permission);

        // product module
//        $product_index = $auth->createPermission('product/index');
//        $auth->add($product_index);
//        $product_update = $auth->createPermission('product/update');
//        $auth->add($product_update);
//        $product_delete = $auth->createPermission('product/delete');
//        $auth->add($product_delete);
//        $product_view = $auth->createPermission('product/view');
//        $auth->add($product_view);
//        $product_create = $auth->createPermission('product/create');
//        $auth->add($product_create);
//        $product_photo_del = $auth->createPermission('product/deletephoto');
//        $auth->add($product_photo_del);
//        $product_del_all = $auth->createPermission('product/delete-all');
//        $auth->add($product_del_all);
//
//
//        $product_import = $auth->createPermission('product/importproduct');
//        $auth->add($product_import);
//        $product_import_update = $auth->createPermission('product/importupdate');
//        $auth->add($product_import_update);
//
//        $product_permission = $auth->createPermission('productmodule');
//        $product_permission->description = "สิทธิ์ใช้งานโมดูล product";
//        $auth->add($product_permission);
//
//        $auth->addChild($product_permission,$product_index);
//        $auth->addChild($product_permission,$product_view);
//        $auth->addChild($product_permission,$product_update);
//        $auth->addChild($product_permission,$product_delete);
//        $auth->addChild($product_permission,$product_create);
//        $auth->addChild($product_permission,$product_import);
//        $auth->addChild($product_permission,$product_import_update);
//        $auth->addChild($product_permission,$product_photo_del);
//        $auth->addChild($product_permission,$product_del_all);
//
//        $manage_product = $auth->createRole('Manage product');
//        $manage_product->description = "Manage Product";
//        $auth->add($manage_product);
//        $auth->addChild($manage_product,$product_permission);

        //appointment module
        $appointment_index = $auth->createPermission('appointment/index');
        $auth->add($appointment_index);
        $appointment_update = $auth->createPermission('appointment/update');
        $auth->add($appointment_update);
        $appointment_delete = $auth->createPermission('appointment/delete');
        $auth->add($appointment_delete);
        $appointment_view = $auth->createPermission('appointment/view');
        $auth->add($appointment_view);
        $appointment_create = $auth->createPermission('appointment/create');
        $auth->add($appointment_create);

        $appointment_permission = $auth->createPermission('appointmentmodule');
        $appointment_permission->description = "สิทธิ์ใช้งานโมดูล appointment";
        $auth->add($appointment_permission);

        $auth->addChild($appointment_permission, $appointment_index);
        $auth->addChild($appointment_permission, $appointment_view);
        $auth->addChild($appointment_permission, $appointment_update);
        $auth->addChild($appointment_permission, $appointment_delete);
        $auth->addChild($appointment_permission, $appointment_create);

        $manage_appointment = $auth->createRole('Manage appointment');
        $manage_appointment->description = "Manage appointment";
        $auth->add($manage_appointment);
        $auth->addChild($manage_appointment, $appointment_permission);

        //doctor module
        $doctor_index = $auth->createPermission('doctor/index');
        $auth->add($doctor_index);
        $doctor_update = $auth->createPermission('doctor/update');
        $auth->add($doctor_update);
        $doctor_delete = $auth->createPermission('doctor/delete');
        $auth->add($doctor_delete);
        $doctor_view = $auth->createPermission('doctor/view');
        $auth->add($doctor_view);
        $doctor_create = $auth->createPermission('doctor/create');
        $auth->add($doctor_create);

        $doctor_permission = $auth->createPermission('doctormodule');
        $doctor_permission->description = "สิทธิ์ใช้งานโมดูล doctor";
        $auth->add($doctor_permission);

        $auth->addChild($doctor_permission, $doctor_index);
        $auth->addChild($doctor_permission, $doctor_view);
        $auth->addChild($doctor_permission, $doctor_update);
        $auth->addChild($doctor_permission, $doctor_delete);
        $auth->addChild($doctor_permission, $doctor_create);

        $manage_doctor = $auth->createRole('Manage doctor');
        $manage_doctor->description = "Manage doctors";
        $auth->add($manage_doctor);
        $auth->addChild($manage_doctor, $doctor_permission);

        //medicine group module
        $medicinegroup_index = $auth->createPermission('medicinegroup/index');
        $auth->add($medicinegroup_index);
        $medicinegroup_update = $auth->createPermission('medicinegroup/update');
        $auth->add($medicinegroup_update);
        $medicinegroup_delete = $auth->createPermission('medicinegroup/delete');
        $auth->add($medicinegroup_delete);
        $medicinegroup_view = $auth->createPermission('medicinegroup/view');
        $auth->add($medicinegroup_view);
        $medicinegroup_create = $auth->createPermission('medicinegroup/create');
        $auth->add($medicinegroup_create);

        $medicinegroup_permission = $auth->createPermission('medicinegroupmodule');
        $medicinegroup_permission->description = "สิทธิ์ใช้งานโมดูล medicinegroup";
        $auth->add($medicinegroup_permission);

        $auth->addChild($medicinegroup_permission, $medicinegroup_index);
        $auth->addChild($medicinegroup_permission, $medicinegroup_view);
        $auth->addChild($medicinegroup_permission, $medicinegroup_update);
        $auth->addChild($medicinegroup_permission, $medicinegroup_delete);
        $auth->addChild($medicinegroup_permission, $medicinegroup_create);

        $manage_medicinegroup = $auth->createRole('Manage medicinegroup');
        $manage_medicinegroup->description = "Manage medicine group";
        $auth->add($manage_medicinegroup);
        $auth->addChild($manage_medicinegroup, $medicinegroup_permission);

        //medicine module
        $medicine_index = $auth->createPermission('medicine/index');
        $auth->add($medicine_index);
        $medicine_update = $auth->createPermission('medicine/update');
        $auth->add($medicine_update);
        $medicine_delete = $auth->createPermission('medicine/delete');
        $auth->add($medicine_delete);
        $medicine_view = $auth->createPermission('medicine/view');
        $auth->add($medicine_view);
        $medicine_create = $auth->createPermission('medicine/create');
        $auth->add($medicine_create);

        $medicine_permission = $auth->createPermission('medicinemodule');
        $medicine_permission->description = "สิทธิ์ใช้งานโมดูล medicine";
        $auth->add($medicine_permission);

        $auth->addChild($medicine_permission, $medicine_index);
        $auth->addChild($medicine_permission, $medicine_view);
        $auth->addChild($medicine_permission, $medicine_update);
        $auth->addChild($medicine_permission, $medicine_delete);
        $auth->addChild($medicine_permission, $medicine_create);

        $manage_medicine = $auth->createRole('Manage medicine');
        $manage_medicine->description = "Manage medicine";
        $auth->add($manage_medicine);
        $auth->addChild($manage_medicine, $medicine_permission);

        //course group module
        $coursegroup_index = $auth->createPermission('coursegroup/index');
        $auth->add($coursegroup_index);
        $coursegroup_update = $auth->createPermission('coursegroup/update');
        $auth->add($coursegroup_update);
        $coursegroup_delete = $auth->createPermission('coursegroup/delete');
        $auth->add($coursegroup_delete);
        $coursegroup_view = $auth->createPermission('coursegroup/view');
        $auth->add($coursegroup_view);
        $coursegroup_create = $auth->createPermission('coursegroup/create');
        $auth->add($coursegroup_create);

        $coursegroup_permission = $auth->createPermission('coursegroupmodule');
        $coursegroup_permission->description = "สิทธิ์ใช้งานโมดูล coursegroup";
        $auth->add($coursegroup_permission);

        $auth->addChild($coursegroup_permission, $coursegroup_index);
        $auth->addChild($coursegroup_permission, $coursegroup_view);
        $auth->addChild($coursegroup_permission, $coursegroup_update);
        $auth->addChild($coursegroup_permission, $coursegroup_delete);
        $auth->addChild($coursegroup_permission, $coursegroup_create);

        $manage_coursegroup = $auth->createRole('Manage coursegroup');
        $manage_coursegroup->description = "Manage course group";
        $auth->add($manage_coursegroup);
        $auth->addChild($manage_coursegroup, $coursegroup_permission);

        //course module
        $course_index = $auth->createPermission('course/index');
        $auth->add($course_index);
        $course_update = $auth->createPermission('course/update');
        $auth->add($course_update);
        $course_delete = $auth->createPermission('course/delete');
        $auth->add($course_delete);
        $course_view = $auth->createPermission('course/view');
        $auth->add($course_view);
        $course_create = $auth->createPermission('course/create');
        $auth->add($course_create);

        $course_permission = $auth->createPermission('coursemodule');
        $course_permission->description = "สิทธิ์ใช้งานโมดูล course";
        $auth->add($course_permission);

        $auth->addChild($course_permission, $course_index);
        $auth->addChild($course_permission, $course_view);
        $auth->addChild($course_permission, $course_update);
        $auth->addChild($course_permission, $course_delete);
        $auth->addChild($course_permission, $course_create);

        $manage_course = $auth->createRole('Manage course');
        $manage_course->description = "Manage course";
        $auth->add($manage_course);
        $auth->addChild($manage_course, $course_permission);

        //customergroup module
        $customergroup_index = $auth->createPermission('customergroup/index');
        $auth->add($customergroup_index);
        $customergroup_update = $auth->createPermission('customergroup/update');
        $auth->add($customergroup_update);
        $customergroup_delete = $auth->createPermission('customergroup/delete');
        $auth->add($customergroup_delete);
        $customergroup_view = $auth->createPermission('customergroup/view');
        $auth->add($customergroup_view);
        $customergroup_create = $auth->createPermission('customergroup/create');
        $auth->add($customergroup_create);

        $customergroup_permission = $auth->createPermission('customergroupmodule');
        $customergroup_permission->description = "สิทธิ์ใช้งานโมดูล customergroup";
        $auth->add($customergroup_permission);

        $auth->addChild($customergroup_permission, $customergroup_index);
        $auth->addChild($customergroup_permission, $customergroup_view);
        $auth->addChild($customergroup_permission, $customergroup_update);
        $auth->addChild($customergroup_permission, $customergroup_delete);
        $auth->addChild($customergroup_permission, $customergroup_create);

        $manage_customergroup = $auth->createRole('Manage customergroup');
        $manage_customergroup->description = "Manage customer group";
        $auth->add($manage_customergroup);
        $auth->addChild($manage_customergroup, $customergroup_permission);

        //customer module
        $customer_index = $auth->createPermission('customer/index');
        $auth->add($customer_index);
        $customer_update = $auth->createPermission('customer/update');
        $auth->add($customer_update);
        $customer_delete = $auth->createPermission('customer/delete');
        $auth->add($customer_delete);
        $customer_view = $auth->createPermission('customer/view');
        $auth->add($customer_view);
        $customer_create = $auth->createPermission('customer/create');
        $auth->add($customer_create);

        $customer_permission = $auth->createPermission('customermodule');
        $customer_permission->description = "สิทธิ์ใช้งานโมดูล customer";
        $auth->add($customer_permission);

        $auth->addChild($customer_permission, $customer_index);
        $auth->addChild($customer_permission, $customer_view);
        $auth->addChild($customer_permission, $customer_update);
        $auth->addChild($customer_permission, $customer_delete);
        $auth->addChild($customer_permission, $customer_create);

        $manage_customer = $auth->createRole('Manage customer');
        $manage_customer->description = "Manage customer";
        $auth->add($manage_customer);
        $auth->addChild($manage_customer, $customer_permission);

        //treat module
        $treat_index = $auth->createPermission('treat/index');
        $auth->add($treat_index);
        $treat_update = $auth->createPermission('treat/update');
        $auth->add($treat_update);
        $treat_delete = $auth->createPermission('treat/delete');
        $auth->add($treat_delete);
        $treat_view = $auth->createPermission('treat/view');
        $auth->add($treat_view);
        $treat_create = $auth->createPermission('treat/create');
        $auth->add($treat_create);

        $treat_permission = $auth->createPermission('treatmodule');
        $treat_permission->description = "สิทธิ์ใช้งานโมดูล treat";
        $auth->add($treat_permission);

        $auth->addChild($treat_permission, $treat_index);
        $auth->addChild($treat_permission, $treat_view);
        $auth->addChild($treat_permission, $treat_update);
        $auth->addChild($treat_permission, $treat_delete);
        $auth->addChild($treat_permission, $treat_create);

        $manage_treat = $auth->createRole('Manage treat');
        $manage_treat->description = "Manage treat";
        $auth->add($manage_treat);
        $auth->addChild($manage_treat, $treat_permission);

        //usergroup module
        $usergroup_index = $auth->createPermission('usergroup/index');
        $auth->add($usergroup_index);
        $usergroup_update = $auth->createPermission('usergroup/update');
        $auth->add($usergroup_update);
        $usergroup_delete = $auth->createPermission('usergroup/delete');
        $auth->add($usergroup_delete);
        $usergroup_view = $auth->createPermission('usergroup/view');
        $auth->add($usergroup_view);
        $usergroup_create = $auth->createPermission('usergroup/create');
        $auth->add($usergroup_create);


        $usergroup_permission = $auth->createPermission('usergroupmodule');
        $usergroup_permission->description = "สิทธิ์ใช้งานโมดูล usergroup";
        $auth->add($usergroup_permission);

        $auth->addChild($usergroup_permission, $usergroup_index);
        $auth->addChild($usergroup_permission, $usergroup_view);
        $auth->addChild($usergroup_permission, $usergroup_update);
        $auth->addChild($usergroup_permission, $usergroup_delete);
        $auth->addChild($usergroup_permission, $usergroup_create);

        $manage_usergroup = $auth->createRole('Manage usergroup');
        $manage_usergroup->description = "Manage user groups";
        $auth->add($manage_usergroup);
        $auth->addChild($manage_usergroup, $usergroup_permission);

        //user module
        $user_index = $auth->createPermission('user/index');
        $auth->add($user_index);
        $user_update = $auth->createPermission('user/update');
        $auth->add($user_update);
        $user_delete = $auth->createPermission('user/delete');
        $auth->add($user_delete);
        $user_view = $auth->createPermission('user/view');
        $auth->add($user_view);
        $user_create = $auth->createPermission('user/create');
        $auth->add($user_create);
        $user_login = $auth->createPermission('user/login');
        $auth->add($user_login);
        $user_logout = $auth->createPermission('user/logout');
        $auth->add($user_logout);
        $user_reset = $auth->createPermission('user/resetpassword');
        $auth->add($user_reset);

        $user_permission = $auth->createPermission('usermodule');
        $user_permission->description = "สิทธิ์ใช้งานโมดูล user";
        $auth->add($user_permission);

        $auth->addChild($user_permission, $user_index);
        $auth->addChild($user_permission, $user_view);
        $auth->addChild($user_permission, $user_update);
        $auth->addChild($user_permission, $user_delete);
        $auth->addChild($user_permission, $user_create);
        $auth->addChild($user_permission, $user_reset);
        $auth->addChild($user_permission, $user_login);
        $auth->addChild($user_permission, $user_logout);

        $manage_user = $auth->createRole('Manage user');
        $manage_user->description = "Manage users";
        $auth->add($manage_user);
        $auth->addChild($manage_user, $user_permission);

        //employee module
        $employee_index = $auth->createPermission('employee/index');
        $auth->add($employee_index);
        $employee_update = $auth->createPermission('employee/update');
        $auth->add($employee_update);
        $employee_delete = $auth->createPermission('employee/delete');
        $auth->add($employee_delete);
        $employee_view = $auth->createPermission('employee/view');
        $auth->add($employee_view);
        $employee_create = $auth->createPermission('employee/create');
        $auth->add($employee_create);

        $employee_permission = $auth->createPermission('employeemodule');
        $employee_permission->description = "สิทธิ์ใช้งานโมดูล employee";
        $auth->add($employee_permission);

        $auth->addChild($employee_permission, $employee_index);
        $auth->addChild($employee_permission, $employee_view);
        $auth->addChild($employee_permission, $employee_update);
        $auth->addChild($employee_permission, $employee_delete);
        $auth->addChild($employee_permission, $employee_create);

        $manage_employee = $auth->createRole('Manage employee');
        $manage_employee->description = "Manage invoice";
        $auth->add($manage_employee);
        $auth->addChild($manage_employee, $employee_permission);

        //message module
        $message_index = $auth->createPermission('message/index');
        $auth->add($message_index);
        $message_update = $auth->createPermission('message/update');
        $auth->add($message_update);
        $message_delete = $auth->createPermission('message/delete');
        $auth->add($message_delete);
        $message_view = $auth->createPermission('message/view');
        $auth->add($message_view);
        $message_create = $auth->createPermission('message/create');
        $auth->add($message_create);

        $message_permission = $auth->createPermission('messagemodule');
        $message_permission->description = "สิทธิ์ใช้งานโมดูล message";
        $auth->add($message_permission);

        $auth->addChild($message_permission, $message_index);
        $auth->addChild($message_permission, $message_view);
        $auth->addChild($message_permission, $message_update);
        $auth->addChild($message_permission, $message_delete);
        $auth->addChild($message_permission, $message_create);

        $manage_message = $auth->createRole('Manage message');
        $manage_message->description = "Manage message";
        $auth->add($manage_message);
        $auth->addChild($manage_message, $message_permission);

        $admin_role = $auth->createRole('แอดมินร้าน');
        $admin_role->description = "ผู้ดูแลระบบ";
        $auth->add($admin_role);

        $auth->addChild($admin_role, $manage_plant);
        //    $auth->addChild($admin_role,$manage_product);
        //    $auth->addChild($admin_role,$manage_prodrec);
        //    $auth->addChild($admin_role,$manage_invoice);
        $auth->addChild($admin_role, $manage_appointment);
        $auth->addChild($admin_role, $manage_doctor);
        $auth->addChild($admin_role, $manage_medicinegroup);
        $auth->addChild($admin_role, $manage_medicine);
        $auth->addChild($admin_role, $manage_coursegroup);
        $auth->addChild($admin_role, $manage_course);
        $auth->addChild($admin_role, $manage_customergroup);
        $auth->addChild($admin_role, $manage_customer);
        $auth->addChild($admin_role, $manage_employee);
        $auth->addChild($admin_role, $manage_message);
        //   $auth->addChild($admin_role,$manage_warehouse);
        $auth->addChild($admin_role, $manage_treat);
        $auth->addChild($admin_role, $manage_user);
        $auth->addChild($admin_role, $manage_usergroup);

        $user_role = $auth->createRole('พนักงานร้าน');
        $user_role->description = "ผู้ใช้งานทั่วไป";
        $auth->add($user_role);


        //  $auth->addChild($user_role,$manage_product);
        //  $auth->addChild($user_role,$manage_prodrec);


        $auth->assign($admin_role, 1);
        $auth->assign($user_role, 1);


    }
}
/*
 *
 public function init()
    {
      $auth = Yii::$app->authManager;
      $auth->removeAll();
      Console::output('Removing All! RBAC.....');

      $createPost = $auth->createPermission('createBlog');
      $createPost->description = 'สร้าง blog';
      $auth->add($createPost);

      $updatePost = $auth->createPermission('updateBlog');
      $updatePost->description = 'แก้ไข blog';
      $auth->add($updatePost);

      // เพิ่ม permission loginToBackend <<<------------------------
      $loginToBackend = $auth->createPermission('loginToBackend');
      $loginToBackend->description = 'ล็อกอินเข้าใช้งานส่วน backend';
      $auth->add($loginToBackend);

      $manageUser = $auth->createRole('ManageUser');
      $manageUser->description = 'จัดการข้อมูลผู้ใช้งาน';
      $auth->add($manageUser);

      $author = $auth->createRole('Author');
      $author->description = 'การเขียนบทความ';
      $auth->add($author);

      $management = $auth->createRole('Management');
      $management->description = 'จัดการข้อมูลผู้ใช้งานและบทความ';
      $auth->add($management);

      $admin = $auth->createRole('Admin');
      $admin->description = 'สำหรับการดูแลระบบ';
      $auth->add($admin);

      $rule = new \common\rbac\AuthorRule;
      $auth->add($rule);

      $updateOwnPost = $auth->createPermission('updateOwnPost');
      $updateOwnPost->description = 'แก้ไขบทความตัวเอง';
      $updateOwnPost->ruleName = $rule->name;
      $auth->add($updateOwnPost);

      $auth->addChild($author,$createPost);
      $auth->addChild($updateOwnPost, $updatePost);
      $auth->addChild($author, $updateOwnPost);

      // addChild role ManageUser <<<------------------------
      $auth->addChild($manageUser, $loginToBackend);

      $auth->addChild($management, $manageUser);
      $auth->addChild($management, $author);

      $auth->addChild($admin, $management);

      $auth->assign($admin, 1);
      $auth->assign($management, 2);
      $auth->assign($author, 3);
      $auth->assign($author, 4);

      Console::output('Success! RBAC roles has been added.');
    } */
