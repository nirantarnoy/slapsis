<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;

/* @var $this yii\web\View */
/* @var $model app\models\JournalTrans */
/* @var $lines app\models\JournalTransLine[] */

$this->title = $model->journal_no;
$this->params['breadcrumbs'][] = ['label' => 'รายการ Stock Transaction', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

\yii\web\YiiAsset::register($this);

$product_type = \backend\helpers\ProductType::asArrayObject();
?>
    <div class="journal-trans-view">

        <p>
            <?= Html::a('แก้ไข', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= $model->status != 3? Html::a('ลบ', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data' => [
                    'confirm' => 'คุณแน่ใจหรือไม่ที่จะลบรายการนี้?',
                    'method' => 'post',
                ],
            ]):'' ?>
            <?= Html::a('สร้างรายการใหม่', ['create'], ['class' => 'btn btn-success']) ?>
        </p>

        <div class="row">
            <div class="col-md-6">
                <?= DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        'journal_no',
                        [
                            'attribute' => 'trans_date',
                            'format' => ['datetime', 'php:d/m/Y H:i:s'],
                        ],
                        [
                            'attribute' => 'trans_type_id',
                            'value' => $model->getTransactionTypeName(),
                        ],
                        [
                            'attribute' => 'stock_type_id',
                            'format' => 'raw',
                            'value' => function ($model) {
                                $type_name = \backend\helpers\StockType::getTypeById($model->stock_type_id);
                                $stock_type = '';
                                if ($model->stock_type_id == 1) {
                                    $stock_type = '<div class="badge badge-pill badge-success">' . $type_name . '</div>';
                                } else if ($model->stock_type_id == 2) {
                                    $stock_type = '<div class="badge badge-pill badge-danger">' . $type_name . '</div>';
                                }
                                return $stock_type;
                            },
                        ],
                        [
                            'attribute' => 'warehouse_id',
                            'value' => function ($model) {
                                return \backend\models\Warehouse::findName($model->warehouse_id);
                            },
                        ],
                    ],
                ]) ?>
            </div>
            <div class="col-md-6">
                <?= DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        //'customer_id',
                        'customer_name',
                        [
                            'attribute' => 'qty',
                            'format' => ['decimal', 2],
                        ],
                        'remark',
                        [
                            'attribute' => 'status',
                            'headerOptions' => ['style' => 'text-align:center'],
                            'contentOptions' => ['style' => 'text-align:left'],
                            'format' => 'raw',
                            'value' => function($model) {
                                $status_name = \backend\helpers\TransStatusType::getTypeById($model->status);
                                $htmel_status = getBadgeStatus($model->status,$status_name);
                                return $htmel_status;
                            },
                            // 'format' => 'raw',
                            // 'filter' => JournalTrans::getStatusList(),
                            //'headerOptions' => ['style' => 'width:100px'],
                        ],
                    ],
                ]) ?>
            </div>
        </div>

        <h3>รายการสินค้า</h3>

        <?php
        $dataProvider = new ArrayDataProvider([
            'allModels' => $lines,
            'pagination' => false,
        ]);
        ?>

        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                [
                    'attribute' => 'product_id',
                    'value' => function ($model) {
                        return \backend\models\Product::findName($model->product_id);
                    },
                ],
                [
                    'attribute' => 'warehouse_id',
                    'value' => function ($model) {
                        return \backend\models\Warehouse::findName($model->warehouse_id);
                    },
                ],
                [
                    'attribute' => 'qty',
                    'format' => ['decimal', 2],
                    'headerOptions' => ['style' => 'text-align:right'],
                    'contentOptions' => ['style' => 'text-align:right'],
                ],
                'remark',
//                [
//                    'attribute' => 'status',
//                    'value' => function ($model) {
//                        return $model->getStatusName();
//                    },
//                ],
            ],
        ]); ?>

        <div class="row" style="margin-top: 20px;">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <p><strong>สร้างโดย:</strong> <?= \backend\models\User::findName($model->created_by) ?>
                            เมื่อ <?= Yii::$app->formatter->asDatetime($model->created_at) ?></p>
                        <?php if ($model->updated_at): ?>
                            <p><strong>แก้ไขโดย:</strong> <?= $model->updated_by ?>
                                เมื่อ <?= Yii::$app->formatter->asDatetime($model->updated_at) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <br/>
        <?php if ($model->trans_type_id == 7 && $model->status != 3): ?>
            <div class="row">
                <div class="col-lg-12">
                    <h4>รับสินค้าคืนช่าง</h4>
                </div>
            </div>
            <form action="<?= \yii\helpers\Url::to(['journaltrans/addreturnproduct'], true) ?>" method="post">
                <input type="hidden" name="journal_trans_id" value="<?= $model->id ?>">
                <input type="hidden" name="trans_type_id" value="8">
                <div class="row" style="margin-top: 10px">
                    <div class="col-lg-3">
                        <label for="">สินค้า</label>
                    </div>
                    <div class="col-lg-2">
                        <label for="">จำนวนเบิก</label>
                    </div>
                    <div class="col-lg-2">
                        <label for="">จำนวนคืน</label>
                    </div>
                    <div class="col-lg-2">
                        <label for="">คืนเป็นสินค้า</label>
                    </div>
                    <div class="col-lg-2">
                        <label for="">หมายเหตุ</label>
                    </div>
                </div>
                <?php foreach ($lines as $value): ?>
                    <?php
                    $check_return_qty = getReturnProduct($model->id, $value->product_id, $value->qty,$model->trans_type_id);
                    // echo $check_return_qty;
                    if ($check_return_qty == 0) continue;
                    ?>
                    <div class="row" style="margin-top: 10px">
                        <div class="col-lg-3">
                            <input type="hidden" name="product_id[]" value="<?= $value->product_id ?>">
                            <input type="text" class="form-control" readonly
                                   value="<?= \backend\models\Product::findName($value->product_id) ?>">
                        </div>
                        <div class="col-lg-2">
                            <input type="text" class="form-control" readonly value="<?= $value->qty ?>">
                        </div>
                        <div class="col-lg-2">
                            <input type="number" name="return_qty[]" class="form-control"
                                   value="<?= $check_return_qty ?>">
                        </div>
                        <div class="col-lg-2">
                            <select name="return_to_type[]" class="form-control">
                                <?php for ($i = 0; $i <= count($product_type) - 1; $i++): ?>
                                    <option value="<?= $product_type[$i]['id'] ?>"><?= $product_type[$i]['name'] ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <input type="text" name="return_remark[]" class="form-control">
                        </div>
                    </div>

                <?php endforeach; ?>
                <br/>
                <div class="row">
                    <div class="col-lg-3">
                        <button class="btn btn-success">บันทึกรายการ</button>
                    </div>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($model->trans_type_id == 5 && $model->status != 3): ?> <!-- คืนยืม -->
            <div class="row">
                <div class="col-lg-12">
                    <h4>รับคืนยืมสินค้า</h4>
                </div>
            </div>
            <form action="<?= \yii\helpers\Url::to(['journaltrans/addreturnproduct'], true) ?>" method="post">
                <input type="hidden" name="journal_trans_id" value="<?= $model->id ?>">
                <input type="hidden" name="trans_type_id" value="6">
                <div class="row" style="margin-top: 10px">
                    <div class="col-lg-3">
                        <label for="">สินค้า</label>
                    </div>
                    <div class="col-lg-2">
                        <label for="">จำนวนเบิก</label>
                    </div>
                    <div class="col-lg-2">
                        <label for="">จำนวนคืน</label>
                    </div>
                    <div class="col-lg-4">
                        <label for="">หมายเหตุ</label>
                    </div>
                </div>
                <?php foreach ($lines as $value): ?>
                    <?php
                    $check_return_qty = getReturnProduct($model->id, $value->product_id, $value->qty,$model->trans_type_id);
                    // echo $check_return_qty;
                    if ($check_return_qty == 0) continue;
                    ?>
                    <div class="row" style="margin-top: 10px">
                        <div class="col-lg-3">
                            <input type="hidden" name="product_id[]" value="<?= $value->product_id ?>">
                            <input type="text" class="form-control" readonly
                                   value="<?= \backend\models\Product::findName($value->product_id) ?>">
                        </div>
                        <div class="col-lg-2">
                            <input type="text" class="form-control" readonly value="<?= $value->qty ?>">
                        </div>
                        <div class="col-lg-2">
                            <input type="number" name="return_qty[]" class="form-control"
                                   value="<?= $check_return_qty ?>">
                        </div>
                        <div class="col-lg-4">
                            <input type="text" name="return_remark[]" class="form-control">
                        </div>
                    </div>

                <?php endforeach; ?>
                <br/>
                <div class="row">
                    <div class="col-lg-3">
                        <button class="btn btn-success">บันทึกรายการ</button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
        <br/>

    </div>
<?php
function getReturnProduct($journal_trans_id, $product_id, $original_qty,$trans_type_id)
{
    $qty = 0;
    if ($product_id && $original_qty) {
        $model_ref = \backend\models\Journaltrans::find()->where(['trans_ref_id' => $journal_trans_id])->one();
        if($model_ref){
            $model = \backend\models\Stocktrans::find()->where(['journal_trans_id' => $model_ref->id, 'product_id' => $product_id, 'trans_type_id' => $trans_type_id])->one();
            if ($model) {
                $qty = $original_qty - $model->qty;
            } else {
                $qty = $original_qty;
            }
        }else{
            $qty = $original_qty;
        }


    }
    return $qty;
}

function getBadgeType($status,$status_name) {
    if ($status == 1) {
        return '<span class="badge badge-pill badge-success" style="padding: 10px;">' . $status_name . '</span>';
    } else if($status == 2) {
        return '<span class="badge badge-pill badge-warning" style="padding: 10px;">' . $status_name . '</span>';
    } else if($status == 3) {
        return '<span class="badge badge-pill badge-success" style="padding: 10px;">' . $status_name . '</span>';
    } else if($status == 4) {
        return '<span class="badge badge-pill badge-info" style="padding: 10px;">' . $status_name . '</span>';
    } else if ($status == 5) {
        return '<span class="badge badge-pill badge-info" style="padding: 10px;">' . $status_name . '</span>';
    } else if ($status == 6) {
        return '<span class="badge badge-pill badge-info" style="padding: 10px;">' . $status_name . '</span>';
    } else if ($status == 7) {
        return '<span class="badge badge-pill badge-primary" style="padding: 10px;">' . $status_name . '</span>';
    } else if($status == 8) {
        return '<span class="badge badge-pill badge-primary" style="padding: 10px;">' . $status_name . '</span>';
    } else if($status == 9) {
        return '<span class="badge badge-pill badge-secondary" style="padding: 10px;">' . $status_name . '</span>';
    }else {
        return '<span class="badge badge-pill badge-secondary" style="padding: 10px;">' . $status_name . '</span>';
    }
}

function getBadgeStatus($status,$status_name) {
    if ($status == 1) {
        return '<span class="badge badge-pill badge-info" style="padding: 10px;">' . $status_name . '</span>';
    } else if($status == 2) {
        return '<span class="badge badge-pill badge-warning" style="padding: 10px;">' . $status_name . '</span>';
    } else if($status == 3) {
        return '<span class="badge badge-pill badge-success" style="padding: 10px;">' . $status_name . '</span>';
    } else if($status == 4) {
        return '<span class="badge badge-pill badge-secondary" style="padding: 10px;">' . $status_name . '</span>';
    }
}
?>
