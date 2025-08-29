<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use kartik\date\DatePicker;

$this->title = 'จัดการค่าใช้จ่าย';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="expense-index">
    <div class="row">
        <div class="col-md-6">
            <p>
                <?= Html::a('เพิ่มค่าใช้จ่าย', ['create'], ['class' => 'btn btn-success']) ?>
                <?= Html::a('รายงานสรุป', ['report'], ['class' => 'btn btn-info']) ?>
            </p>
        </div>
        <div class="col-md-6 text-right">
            <?= Html::a('Export Excel', ['export-excel'], [
                'class' => 'btn btn-success',
                'data-method' => 'post',
            ]) ?>
            <?= Html::a('Export PDF', ['export-pdf'], [
                'class' => 'btn btn-danger',
                'target' => '_blank',
            ]) ?>
        </div>
    </div>

    <?php Pjax::begin(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            [
                'attribute' => 'expense_date',
                'format' => 'date',
                'filter' => DatePicker::widget([
                    'model' => $searchModel,
                    'attribute' => 'expense_date',
                    'options' => ['placeholder' => 'เลือกวันที่...'],
                    'pluginOptions' => [
                        'format' => 'yyyy-mm-dd',
                        'todayHighlight' => true
                    ]
                ]),
                'headerOptions' => ['width' => '150']
            ],
            [
                'attribute' => 'category',
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'category',
                    ['' => 'ทั้งหมด'] + (new \backend\models\Expense())->getCategoryOptions(),
                    ['class' => 'form-control']
                ),
                'headerOptions' => ['width' => '150']
            ],
            [
                'attribute' => 'description',
                'value' => function($model) {
                    return Html::encode(mb_substr($model->description, 0, 50)) . (mb_strlen($model->description) > 50 ? '...' : '');
                },
                'format' => 'raw'
            ],
            [
                'attribute' => 'amount',
                'value' => function($model) {
                    return number_format($model->amount, 2) . ' บาท';
                },
                'headerOptions' => ['width' => '120'],
                'contentOptions' => ['class' => 'text-right']
            ],
            [
                'attribute' => 'receipt_file',
                'format' => 'raw',
                'value' => function($model) {
                    if ($model->receipt_file) {
                        return Html::a('ดูไฟล์', $model->getReceiptUrl(), [
                            'target' => '_blank',
                            'class' => 'btn btn-xs btn-info'
                        ]);
                    }
                    return '<span class="text-muted">ไม่มีไฟล์</span>';
                },
                'headerOptions' => ['width' => '80']
            ],

            [
                'class' => 'yii\grid\ActionColumn',
                'headerOptions' => ['width' => '120'],
                'template' => '{view} {update} {delete}',
                'buttons' => [
                    'view' => function ($url, $model, $key) {
                        return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', $url, [
                            'title' => Yii::t('app', 'View'),
                            'class' => 'btn btn-xs btn-primary'
                        ]);
                    },
                    'update' => function ($url, $model, $key) {
                        return Html::a('<span class="glyphicon glyphicon-pencil"></span>', $url, [
                            'title' => Yii::t('app', 'Update'),
                            'class' => 'btn btn-xs btn-info'
                        ]);
                    },
                    'delete' => function ($url, $model, $key) {
                        return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, [
                            'title' => Yii::t('app', 'Delete'),
                            'class' => 'btn btn-xs btn-danger',
                            'data-confirm' => 'คุณแน่ใจหรือไม่ที่จะลบรายการนี้?',
                            'data-method' => 'post',
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>
