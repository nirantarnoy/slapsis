<?php

use backend\models\Stocksum;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use kartik\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var backend\models\StocksumSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'สินค้าคงเหลือ';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="stocksum-index">

    <?php Pjax::begin(); ?>
    <?php echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        // 'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            // 'id',
            // 'company_id',
            [
                'attribute' => 'warehouse_id',
                'value' => function ($data) {
                    return \backend\models\Warehouse::findName($data->warehouse_id);
                }
            ],

            [
                'attribute' => 'product_id',
                'label' => 'ชื่อสินค้า',
                'format' => 'html',
                'value' => function ($data) {
                    return '<a href="' . Url::to(['product/view', 'id' => $data->product_id]) . '">' . \backend\models\Product::findName($data->product_id) . '</a>';
                }
            ],
            'qty',

        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>
