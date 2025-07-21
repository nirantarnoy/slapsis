<?php

use backend\models\Product;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use kartik\grid\GridView;
use yii\widgets\Pjax;

//use yii\widgets\LinkPager;
use yii\bootstrap4\LinkPager;

/** @var yii\web\View $this */
/** @var backend\models\ProductSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'สินค้า';
$this->params['breadcrumbs'][] = $this->title;
?>
    <div id="loading"
         style="display:none; position: fixed; top: 0; left: 0; z-index: 9999; width: 100%; height: 100%; background-color: rgba(255,255,255,0.7); text-align: center; padding-top: 20%;">
        <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><br>กำลังโหลดข้อมูล...
    </div>
    <div class="employee-index">
        <?php Pjax::begin(); ?>
        <div class="row">
            <div class="col-lg-10">
                <p>
                    <?= Html::a(Yii::t('app', '<i class="fa fa-plus"></i> สร้างใหม่'), ['create'], ['class' => 'btn btn-success']) ?>
                </p>
            </div>
            <div class="col-lg-2" style="text-align: right">
                <form id="form-perpage" class="form-inline" action="<?= Url::to(['product/index'], true) ?>"
                      method="post">
                    <div class="form-group">
                        <label>แสดง </label>
                        <select class="form-control" name="perpage" id="perpage">
                            <option value="20" <?= $perpage == '20' ? 'selected' : '' ?>>20</option>
                            <option value="50" <?= $perpage == '50' ? 'selected' : '' ?> >50</option>
                            <option value="100" <?= $perpage == '100' ? 'selected' : '' ?>>100</option>
                        </select>
                        <label> รายการ</label>
                    </div>
                </form>
            </div>
        </div>
        <?php echo $this->render('_search', ['model' => $searchModel, 'viewstatus' => $viewstatus]); ?>

        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            // 'filterModel' => $searchModel,
            'emptyCell' => '-',
            'layout' => "{items}\n{summary}\n<div class='text-center'>{pager}</div>",
            'summary' => "แสดง {begin} - {end} ของทั้งหมด {totalCount} รายการ",
            'showOnEmpty' => false,
            //    'bordered' => true,
            //     'striped' => false,
            //    'hover' => true,
            'id' => 'product-grid',
            'pjax' => true,
            'pjaxSettings' => ['neverTimeout' => true],
            //'tableOptions' => ['class' => 'table table-hover'],
            'emptyText' => '<div style="color: red;text-align: center;"> <b>ไม่พบรายการไดๆ</b> <span> เพิ่มรายการโดยการคลิกที่ปุ่ม </span><span class="text-success">"สร้างใหม่"</span></div>',
            'columns' => [
                [
                    'class' => 'yii\grid\SerialColumn',
                    'headerOptions' => ['style' => 'text-align:center;'],
                    'contentOptions' => ['style' => 'text-align: center'],
                ],
                [
                    'attribute' => 'photo',
                    'headerOptions' => ['style' => 'text-align: center'],
                    'contentOptions' => ['style' => 'text-align: center'],
                    'format' => 'raw',
                    'value' => function ($data) {
                        $url = Yii::$app->request->hostInfo . Yii::$app->request->baseUrl . '/uploads/product_photo/' . $data->photo;
                        return Html::a(
                            Html::img($url, ['style' => 'max-width:50px']),
                            $url,
                            ['target' => '_blank']
                        );
                    }
                ],
                'name',
                'description',
                // 'product_type_id',
                [
                    'attribute' => 'product_group_id',
                    'value' => function ($data) {
                        return \backend\models\Productgroup::findName($data->product_group_id);
                    }
                ],
                [
                    'attribute' => 'brand_id',
                    'value' => function ($data) {
                        return \backend\models\Productbrand::findName($data->brand_id);
                    }
                ],
                [
                    'attribute' => 'product_type_id',
                    'headerOptions' => ['style' => 'text-align: center'],
                    'contentOptions' => ['style' => 'text-align: center'],
                    'value' => function ($data) {
                        return \backend\helpers\ProductType::getTypeById($data->product_type_id);
                    }
                ],
                [
                    'attribute' => 'type_id',
                    'headerOptions' => ['style' => 'text-align: center'],
                    'contentOptions' => ['style' => 'text-align: center'],
                    'value' => function ($data) {
                        return \backend\helpers\CatType::getTypeById($data->type_id);
                    }
                ],
                //'status',
                //'last_price',
                //'std_price',
                //'company_id',
                [
                    'attribute' => 'status',
                    'format' => 'raw',
                    'headerOptions' => ['style' => 'text-align: center'],
                    'contentOptions' => ['style' => 'text-align: center'],
                    'value' => function ($data) {
                        if ($data->status == 1) {
                            return '<div class="badge badge-pill badge-success" style="padding: 10px;">ใช้งาน</div>';
                        } else {
                            return '<div class="badge badge-pill badge-secondary" style="padding: 10px;">ไม่ใช้งาน</div>';
                        }
                    }
                ],
                [
                    'attribute' => 'stock_qty',
                    'label' => 'คงเหลือ',
                    'headerOptions' => ['style' => 'text-align: right'],
                    'contentOptions' => ['style' => 'text-align: right'],
                    'value' => function ($data) {
                        // $qty = \backend\models\Product::getTotalQty($data->id);
                        return number_format($data->stock_qty, 0);
                    }
                ],

                [

                    'header' => 'ตัวเลือก',
                    'headerOptions' => ['style' => 'text-align:center;', 'class' => 'activity-view-link',],
                    'class' => 'yii\grid\ActionColumn',
                    'contentOptions' => ['style' => 'text-align: center'],
                    'template' => '{view} {update}{delete}',
                    'buttons' => [
                        'view' => function ($url, $data, $index) {
                            $options = [
                                'title' => Yii::t('yii', 'View'),
                                'aria-label' => Yii::t('yii', 'View'),
                                'data-pjax' => '0',
                            ];
                            return Html::a(
                                '<span class="fas fa-eye btn btn-xs btn-default"></span>', $url, $options);
                        },
                        'update' => function ($url, $data, $index) {
                            $options = array_merge([
                                'title' => Yii::t('yii', 'Update'),
                                'aria-label' => Yii::t('yii', 'Update'),
                                'data-pjax' => '0',
                                'id' => 'modaledit',
                            ]);
                            return Html::a(
                                '<span class="fas fa-edit btn btn-xs btn-default"></span>', $url, [
                                'id' => 'activity-view-link',
                                //'data-toggle' => 'modal',
                                // 'data-target' => '#modal',
                                'data-id' => $index,
                                'data-pjax' => '0',
                                // 'style'=>['float'=>'rigth'],
                            ]);
                        },
                        'delete' => function ($url, $data, $index) {
                            $options = array_merge([
                                'title' => Yii::t('yii', 'Delete'),
                                'aria-label' => Yii::t('yii', 'Delete'),
                                //'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                                //'data-method' => 'post',
                                //'data-pjax' => '0',
                                'data-url' => $url,
                                'data-var' => $data->id,
                                'onclick' => 'recDelete($(this));'
                            ]);
                            return Html::a('<span class="fas fa-trash-alt btn btn-xs btn-default"></span>', 'javascript:void(0)', $options);
                        }
                    ]
                ],
            ],
            'pager' => ['class' => LinkPager::className()],
        ]); ?>

        <?php Pjax::end(); ?>

    </div>
<?php
$this->registerJs(<<<JS
        $(document).on('pjax:start', function() {
            $('#loading').fadeIn();
        });
        $(document).on('pjax:end', function() {
           $('#loading').fadeOut();
        });
        JS
);
?>