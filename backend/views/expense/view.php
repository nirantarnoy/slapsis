<?php
use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = 'รายละเอียดค่าใช้จ่าย: ' . $model->category;
$this->params['breadcrumbs'][] = ['label' => 'จัดการค่าใช้จ่าย', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="expense-view">

    <p>
        <?= Html::a('แก้ไข', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('ลบ', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'คุณแน่ใจหรือไม่ที่จะลบรายการนี้?',
                'method' => 'post',
            ],
        ]) ?>
        <?= Html::a('กลับไปยังรายการ', ['index'], ['class' => 'btn btn-default']) ?>
    </p>

    <div class="row">
        <div class="col-md-8">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                 //   'id',
                    'category',
                    'description:ntext',
                    'expense_date:date',
                    [
                        'attribute' => 'amount',
                        'value' => number_format($model->amount, 2) . ' บาท',
                    ],
                    [
                        'attribute' => 'status',
                        'value' => $model->getStatusOptions()[$model->status] ?? $model->status,
                    ],
                    'created_at:datetime',
                    'updated_at:datetime',
                ],
            ]) ?>
        </div>
        <div class="col-md-4">
            <?php if ($model->receipt_file): ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">ไฟล์ใบเสร็จ</h3>
                    </div>
                    <div class="panel-body text-center">
                        <?php
                        $fileExtension = pathinfo($model->receipt_file, PATHINFO_EXTENSION);
                        if (in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif'])):
                            ?>
                            <img src="<?= $model->getReceiptUrl() ?>" class="img-responsive" style="max-height: 300px;" />
                        <?php else: ?>
                            <i class="fa fa-file-pdf-o" style="font-size: 48px; color: #d9534f;"></i>
                            <p class="text-muted"><?= $model->receipt_file ?></p>
                        <?php endif; ?>
                        <br>
                        <?= Html::a('ดาวน์โหลด', $model->getReceiptUrl(), [
                            'class' => 'btn btn-success btn-sm',
                            'target' => '_blank'
                        ]) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>