<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\Employee */

$license_data = \common\models\DriverLicense::find()->where(['emp_id'=>$model->id])->all();

$this->title = $model->code;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'พนักงาน'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="employee-view">
    <p>
        <?= Html::a(Yii::t('app', 'แก้ไข'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'ลบ'), ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <div class="row">
        <div class="col-lg-3"></div>
        <div class="col-lg-3"></div>
        <div class="col-lg-3"></div>
        <div class="col-lg-3">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'photo'
                ],
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'code'
                ],
            ]) ?>
        </div>
        <div class="col-lg-4">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'fname'
                ],
            ]) ?>
        </div>
        <div class="col-lg-4">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'lname'
                ],
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    [
                        'attribute' => 'gender',
                        'value' => function ($data) {
                            return \backend\helpers\GenderType::getTypeById($data->gender);
                        }
                    ],
                ],
            ]) ?>
        </div>
        <div class="col-lg-3">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    [
                        'attribute' => 'position',
                        'value' => function ($data) {
                            return \backend\models\Position::findName($data->position);
                        }
                    ],
                ],
            ]) ?>
        </div>
        <div class="col-lg-3">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    [
                        'attribute' => 'salary_type',
                        'value' => function ($data) {
                            return \backend\helpers\SalaryType::getTypeById($data->salary_type);
                        }
                    ],
                ],
            ]) ?>
        </div>
        <div class="col-lg-3">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    [
                        'attribute' => 'emp_start',
                        'value' => function ($data) {
                            return date('d-m-Y', strtotime($data->emp_start));
                        }
                    ],
                ],
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'description'
                ],
            ]) ?>
        </div>
        <div class="col-lg-3">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'company_id'
                ],
            ]) ?>
        </div>
        <div class="col-lg-3">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'branch_id'
                ],
            ]) ?>
        </div>
        <div class="col-lg-3">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => function ($data) {
                            if ($data->status == 1) {
                                return '<div class="badge badge-success">ใช้งาน</div>';
                            } else {
                                return '<div class="badge badge-secondary">ไม่ใช้งาน</div>';
                            }
                        }
                    ],
                ],
            ]) ?>
        </div>
    </div>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            // 'id',
//            'code',
//            'fname',
//            'lname',
//            [
//                'attribute' => 'gender',
//                'value' => function ($data) {
//                    return \backend\helpers\GenderType::getTypeById($data->gender);
//                }
//            ],
//            [
//                'attribute' => 'position',
//                'value' => function ($data) {
//                    return \backend\models\Position::findName($data->position);
//                }
//            ],
//            [
//                'attribute' => 'salary_type',
//                'value' => function ($data) {
//                    return \backend\helpers\SalaryType::getTypeById($data->salary_type);
//                }
//            ],
//            'emp_start',
//            'description',
////            'photo',
//            [
//                'attribute' => 'status',
//                'format' => 'raw',
//                'value' => function ($data) {
//                    if ($data->status == 1) {
//                        return '<div class="badge badge-success">ใช้งาน</div>';
//                    } else {
//                        return '<div class="badge badge-secondary">ไม่ใช้งาน</div>';
//                    }
//                }
//            ],
//
//            'company_id',
//            'branch_id',
//            'created_at',
//            'updated_at',
//            'created_by',
//            'updated_by',
        ],
    ]) ?>

    <?php if (count($license_data)): ?>
        <h4>ข้อมูลผู้ขับ</h4>
        <div class="row">
            <div class="col-lg-12">
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr>
                        <th></th>
                        <th>ประเภท</th>
                        <th>หมายเลข</th>
                        <th>วันเริ่มต้น</th>
                        <th>วันสิ้นสุด</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $i = 0; ?>
                    <?php foreach ($license_data as $value): ?>
                        <?php $i++; ?>
                        <tr>
                            <td style="text-align: center"><?= $i; ?></td>
                            <td><?= \backend\helpers\DrivingcardType::getTypeById($value->license_type_id);  ?></td>
                            <td><?= $value->license_no ?></td>
                            <td><?= date('d-m-Y',strtotime($value->issue_date)) ; ?></td>
                            <td><?= date('d-m-Y',strtotime($value->expired_date)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>
