<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\Url;
use lavrentiev\widgets\toastr\Notification;
use backend\assets\ICheckAsset;
use yii\bootstrap4\LinkPager;

ICheckAsset::register($this);
/* @var $this yii\web\View */
/* @var $searchModel backend\models\AuthitemSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'สิทธิ์ใช้งาน');
$this->params['breadcrumbs'][] = $this->title;

$this->registerJsFile(
    '@web/js/stockbalancejs.js?V=001',
    ['depends' => [\yii\web\JqueryAsset::className()]],
    static::POS_END
);

?>
    <div class="authitem-index">

        <?php $session = Yii::$app->session; ?>
        <?php Pjax::begin(); ?>
        <?php // echo $this->render('_search', ['model' => $searchModel]); ?>
        <div class="panel panel-headline">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-lg-10">
                        <p>
                            <?= Html::a(Yii::t('app', '<i class="fa fa-plus"></i> สร้างใหม่'), ['create'], ['class' => 'btn btn-success']) ?>
                        </p>
                    </div>
                    <div class="col-lg-2" style="text-align: right">
                        <form id="form-perpage" class="form-inline" action="<?= Url::to(['authen/index'], true) ?>"
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
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-lg-9">
                        <div class="form-inline">
                            <?php echo $this->render('_search', ['model' => $searchModel]); ?>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <div class="table-grid">
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
                            //'tableOptions' => ['class' => 'table table-hover'],
                            'emptyText' => '<div style="color: red;text-align: center;"> <b>ไม่พบรายการไดๆ</b> <span> เพิ่มรายการโดยการคลิกที่ปุ่ม </span><span class="text-success">"สร้างใหม่"</span></div>',
                            'columns' => [
                                ['class' => 'yii\grid\SerialColumn', 'contentOptions' => ['style' => 'vertical-align: middle;text-align: center']],
                                ['class' => 'yii\grid\CheckboxColumn', 'headerOptions' => ['style' => 'text-align: center'], 'contentOptions' => ['style' => 'vertical-align: middle;text-align: center;']],
                                // 'id',
                                [
                                    'attribute' => 'name',
                                    'contentOptions' => ['style' => 'vertical-align: middle'],
                                ],
                                [
                                    'attribute' => 'description',
                                    'contentOptions' => ['style' => 'vertical-align: middle'],
                                ],
                                [
                                    'attribute' => 'rule_name',
                                    'contentOptions' => ['style' => 'vertical-align: middle'],
                                ],
                                [
                                    'attribute' => 'type',
                                    'contentOptions' => ['style' => 'vertical-align: middle'],
                                    'value' => function ($data) {
                                        if ($data->type == 1) {
                                            return 'Role';
                                        } else {
                                            return 'Permission';
                                        }
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
                                                'onclick' => 'recDelete($(this));'
                                            ]);
                                            return Html::a('<span class="fas fa-trash-alt btn btn-xs btn-default"></span>', 'javascript:void(0)', $options);
                                        }
                                    ]
                                ],
                            ],
                            'pager' => ['class' => LinkPager::className()],
                        ]); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php Pjax::end(); ?>
    </div>
<?php
$this->registerJsFile('@web/js/sweetalert.min.js', ['depends' => [\yii\web\JqueryAsset::className()]], static::POS_END);
$this->registerCssFile('@web/css/sweetalert.css');
//$url_to_delete =  Url::to(['product/bulkdelete'],true);
$this->registerJs('
    $(function(){
        $("#perpage").change(function(){
            $("#form-perpage").submit();
        });
    });

   function recDelete(e){
        //e.preventDefault();
        var url = e.attr("data-url");
        swal({
              title: "ต้องการลบรายการนี้ใช่หรือไม่",
              text: "",
              type: "error",
              showCancelButton: true,
              closeOnConfirm: false,
              showLoaderOnConfirm: true
            }, function () {
              e.attr("href",url); 
              e.trigger("click");        
        });
    }

    ', static::POS_END);
?>