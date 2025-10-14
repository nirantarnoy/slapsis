<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\ShopeeTransaction */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'รายการธุรกรรม Shopee';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="shopee-transaction-index">

    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">
                <i class="fas fa-shopping-bag"></i> <?= Html::encode($this->title) ?>
            </h4>
        </div>

        <div class="card-body">
            <!-- ฟอร์มค้นหา -->
            <div class="search-form mb-4">
                <?php $form = \yii\bootstrap4\ActiveForm::begin([
                    'action' => ['index'],
                    'method' => 'get',
                    'options' => ['class' => 'needs-validation'],
                ]); ?>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Transaction ID</label>
                            <?= Html::textInput('transaction_id', Yii::$app->request->get('transaction_id'), [
                                'class' => 'form-control',
                                'placeholder' => 'ค้นหา Transaction ID'
                            ]) ?>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Order SN</label>
                            <?= Html::textInput('order_sn', Yii::$app->request->get('order_sn'), [
                                'class' => 'form-control',
                                'placeholder' => 'ค้นหา Order SN'
                            ]) ?>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>ประเภทธุรกรรม</label>
                            <?= Html::dropDownList('transaction_type', Yii::$app->request->get('transaction_type'), [
                                '' => 'ทั้งหมด',
                                'Payment' => 'Payment',
                                'Refund' => 'Refund',
                                'Fee' => 'Fee',
                                'Commission' => 'Commission',
                                'Adjustment' => 'Adjustment',
                            ], ['class' => 'form-control']) ?>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>สถานะ</label>
                            <?= Html::dropDownList('status', Yii::$app->request->get('status'), [
                                '' => 'ทั้งหมด',
                                'Completed' => 'Completed',
                                'Pending' => 'Pending',
                                'Failed' => 'Failed',
                                'Cancelled' => 'Cancelled',
                            ], ['class' => 'form-control']) ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>หมวดค่าธรรมเนียม</label>
                            <?= Html::dropDownList('fee_category', Yii::$app->request->get('fee_category'), [
                                '' => 'ทั้งหมด',
                                'Commission Fee' => 'Commission Fee',
                                'Service Fee' => 'Service Fee',
                                'Payment Fee' => 'Payment Fee',
                                'Shipping Fee' => 'Shipping Fee',
                                'Transaction Fee' => 'Transaction Fee',
                            ], ['class' => 'form-control']) ?>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>วันที่เริ่มต้น</label>
                            <?= Html::textInput('date_from', Yii::$app->request->get('date_from'), [
                                'class' => 'form-control',
                                'type' => 'date'
                            ]) ?>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>วันที่สิ้นสุด</label>
                            <?= Html::textInput('date_to', Yii::$app->request->get('date_to'), [
                                'class' => 'form-control',
                                'type' => 'date'
                            ]) ?>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <?= Html::submitButton('<i class="fas fa-search"></i> ค้นหา', ['class' => 'btn btn-primary']) ?>
                                <?= Html::a('<i class="fas fa-redo"></i> รีเซ็ต', ['index'], ['class' => 'btn btn-secondary']) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php \yii\bootstrap4\ActiveForm::end(); ?>
            </div>

            <!-- ตารางแสดงข้อมูล -->
            <?php Pjax::begin(['id' => 'shopee-transaction-pjax']); ?>

            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-striped table-bordered table-hover'],
                'layout' => "{items}\n<div class='d-flex justify-content-between align-items-center mt-3'><div>{summary}</div><div>{pager}</div></div>",
                'columns' => [
                    [
                        'class' => 'yii\grid\SerialColumn',
                        'headerOptions' => ['style' => 'width: 50px'],
                    ],

                    [
                        'attribute' => 'transaction_id',
                        'label' => 'Transaction ID',
                        'format' => 'text',
                        'headerOptions' => ['style' => 'width: 150px'],
                    ],

                    [
                        'attribute' => 'order_sn',
                        'label' => 'Order SN',
                        'format' => 'text',
                        'headerOptions' => ['style' => 'width: 150px'],
                    ],

                    [
                        'attribute' => 'transaction_type',
                        'label' => 'ประเภท',
                        'format' => 'html',
                        'value' => function ($model) {
                            $badges = [
                                'Payment' => 'success',
                                'Refund' => 'warning',
                                'Fee' => 'danger',
                                'Commission' => 'info',
                                'Adjustment' => 'secondary',
                            ];
                            $badge = $badges[$model->transaction_type] ?? 'secondary';
                            return '<span class="badge badge-' . $badge . '">' . $model->transaction_type . '</span>';
                        },
                        'headerOptions' => ['style' => 'width: 120px'],
                    ],

                    [
                        'attribute' => 'status',
                        'label' => 'สถานะ',
                        'format' => 'html',
                        'value' => function ($model) {
                            $badges = [
                                'Completed' => 'success',
                                'Pending' => 'warning',
                                'Failed' => 'danger',
                                'Cancelled' => 'secondary',
                            ];
                            $badge = $badges[$model->status] ?? 'secondary';
                            return '<span class="badge badge-' . $badge . '">' . $model->status . '</span>';
                        },
                        'headerOptions' => ['style' => 'width: 100px'],
                    ],

                    [
                        'attribute' => 'fee_category',
                        'label' => 'หมวดค่าธรรมเนียม',
                        'format' => 'text',
                        'headerOptions' => ['style' => 'width: 150px'],
                    ],

                    [
                        'attribute' => 'amount',
                        'label' => 'จำนวนเงิน',
                        'format' => 'html',
                        'value' => function ($model) {
                            $class = $model->amount < 0 ? 'text-danger' : 'text-success';
                            return '<span class="' . $class . '">' . number_format($model->amount, 2) . '</span>';
                        },
                        'headerOptions' => ['style' => 'width: 120px; text-align: right'],
                        'contentOptions' => ['style' => 'text-align: right'],
                    ],

                    [
                        'attribute' => 'current_balance',
                        'label' => 'ยอดคงเหลือ',
                        'format' => 'html',
                        'value' => function ($model) {
                            return '<strong>' . number_format($model->current_balance, 2) . '</strong>';
                        },
                        'headerOptions' => ['style' => 'width: 120px; text-align: right'],
                        'contentOptions' => ['style' => 'text-align: right'],
                    ],

                    [
                        'attribute' => 'transaction_date',
                        'label' => 'วันที่ทำรายการ',
                        'format' => 'datetime',
                        'headerOptions' => ['style' => 'width: 150px'],
                    ],

                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => 'จัดการ',
                        'template' => '{view}',
                        'buttons' => [
                            'view' => function ($url, $model, $key) {
                                return Html::a('<i class="fas fa-eye"></i>', $url, [
                                    'class' => 'btn btn-sm btn-info',
                                    'title' => 'ดูรายละเอียด',
                                ]);
                            },
                        ],
                        'headerOptions' => ['style' => 'width: 80px; text-align: center'],
                        'contentOptions' => ['style' => 'text-align: center'],
                    ],
                ],
            ]); ?>

            <?php Pjax::end(); ?>
        </div>
    </div>

</div>

<style>
    .card {
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        border: none;
        border-radius: 8px;
    }

    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 8px 8px 0 0 !important;
        padding: 1rem 1.25rem;
    }

    .search-form {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }

    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #495057;
    }

    .badge {
        padding: 5px 12px;
        font-size: 0.85rem;
    }
</style>