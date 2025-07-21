<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $fromDate string */
/* @var $toDate string */
/* @var $salesByProduct array */
/* @var $priceComparisonData array */
/* @var $topProducts array */

$this->title = 'ภาพรวมระบบ';
$this->params['breadcrumbs'][] = $this->title;

// Register Highcharts
$this->registerJsFile('https://code.highcharts.com/highcharts.js', ['depends' => [\yii\web\JqueryAsset::className()]]);
$this->registerJsFile('https://code.highcharts.com/modules/exporting.js', ['depends' => [\yii\web\JqueryAsset::className()]]);
?>

<?php
// ใน views/site/index.php
if (Yii::$app->session->hasFlash('success')) {
    echo '<div class="alert alert-success">'.Yii::$app->session->getFlash('success').'</div>';
}
if (Yii::$app->session->hasFlash('error')) {
    echo '<div class="alert alert-danger">'.Yii::$app->session->getFlash('error').'</div>';
}

echo Html::a('🔗 เชื่อมต่อ TikTok Shop', Url::to(['site/connect-tiktok']), [
    'class' => 'btn btn-secondary',
]);
echo '<br /><br />';
echo Html::a('🔗 เชื่อมต่อ Shopee Shop', Url::to(['site/connect-tiktok']), [
    'class' => 'btn btn-danger',
]);
?>
