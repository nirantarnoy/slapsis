<?php
//print_r($model_min_max);
use kartik\daterange\DateRangePicker;

$company_id = 0;
$branch_id = 0;
if (!empty(\Yii::$app->user->identity->company_id)) {
    $company_id = \Yii::$app->user->identity->company_id;
}
if (!empty(\Yii::$app->user->identity->branch_id)) {
    $branch_id = \Yii::$app->user->identity->branch_id;
}

$from_date = date('Y-m-d');
$to_date = date('Y-m-d');

if ($find_from_date != null) {
    $from_date = date('Y-m-d H:i', strtotime($find_from_date));
    $to_date = date('Y-m-d H:i', strtotime($find_to_date));
}


?>
<html>
<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="utf-8" http-equiv="encoding">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการเข้าระบบ</title>
    <link href="https://fonts.googleapis.com/css?family=Sarabun&display=swap" rel="stylesheet">
    <style>
        /*body {*/
        /*    font-family: sarabun;*/
        /*    !*font-family: garuda;*!*/
        /*    font-size: 18px;*/
        /*}*/
        #div1 {
            font-family: sarabun;
            /*font-family: garuda;*/
            font-size: 18px;
        }

        table.table-header {
            border: 0px;
            border-spacing: 1px;
        }

        table.table-footer {
            border: 0px;
            border-spacing: 0px;
        }

        table.table-header td, th {
            border: 0px solid #dddddd;
            text-align: left;
            padding-top: 2px;
            padding-bottom: 2px;
        }

        table.table-title {
            border: 0px;
            border-spacing: 0px;
        }

        table.table-title td, th {
            border: 0px solid #dddddd;
            text-align: left;
            padding-top: 2px;
            padding-bottom: 2px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        td, th {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
        }

        tr:nth-child(even) {
            /*background-color: #dddddd;*/
        }

        table.table-detail {
            border-collapse: collapse;
            width: 100%;
        }

        table.table-detail td, th {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 2px;
        }

    </style>
</head>
<body>
<form action="<?= \yii\helpers\Url::to(['user/printlogindaily'], true) ?>" method="post">
    <div class="row">

        <div class="col-lg-3">
            <label for="">ตั้งแต่วันที่</label>
            <?php
            echo DateRangePicker::widget([
                'name' => 'from_date',
                // 'value'=>'2015-10-19 12:00 AM',
                'value' => $from_date != null ? date('Y-m-d H:i', strtotime($from_date)) : date('Y-m-d H:i'),
                //    'useWithAddon'=>true,
                'convertFormat' => true,
                'options' => [
                    'class' => 'form-control',
                    'placeholder' => 'ตั้งแต่วันที่',
                    //  'onchange' => 'this.form.submit();',
                    'autocomplete' => 'off',
                ],
                'pluginOptions' => [
                    'timePicker' => true,
                    'timePickerIncrement' => 1,
                    'locale' => ['format' => 'Y-m-d H:i'],
                    'singleDatePicker' => true,
                    'showDropdowns' => true,
                    'timePicker24Hour' => true
                ]
            ]);
            ?>
        </div>
        <div class="col-lg-3">
            <label for="">ตั้งแต่วันที่</label>
            <?php
            echo DateRangePicker::widget([
                'name' => 'to_date',
                'value' => $to_date != null ? date('Y-m-d H:i', strtotime($to_date)) : date('Y-m-d H:i'),
                //    'useWithAddon'=>true,
                'convertFormat' => true,
                'options' => [
                    'class' => 'form-control',
                    'placeholder' => 'ถึงวันที่',
                    //  'onchange' => 'this.form.submit();',
                    'autocomplete' => 'off',
                ],
                'pluginOptions' => [
                    'timePicker' => true,
                    'timePickerIncrement' => 1,
                    'locale' => ['format' => 'Y-m-d H:i'],
                    'singleDatePicker' => true,
                    'showDropdowns' => true,
                    'timePicker24Hour' => true
                ]
            ]);
            ?>
        </div>
        <div class="col-lg-3">
            <label for="">พนักงาน</label>
            <?php
            echo \kartik\select2\Select2::widget([
                'name' => 'find_customer_id',
                'data' => \yii\helpers\ArrayHelper::map(\backend\models\User::find()->where(['company_id' => $company_id, 'branch_id' => $branch_id, 'status' => 1])->all(), 'id', function ($data) {
                    return $data->username;
                }),
                'value' => $find_customer_id,
                'options' => [
                    'placeholder' => '--เลือกพนักงาน--'
                ],
                'pluginOptions' => [
                    'allowClear' => true,
                    'multiple' => false,
                ]
            ]);
            ?>
        </div>
        <div class="col-lg-3">
            <div style="height: 30px;"></div>
            <button class="btn btn-info">ค้นหา</button>
        </div>

    </div>
</form>
<br/>
<div id="div1">
    <table style="width: 100%;border: 0px;">
        <tr>
            <td style="text-align: center;border: none" colspan="2"><h3>ตรวจสอบพนักงานคู่กะ</h3></td>
        </tr>
        <tr>
            <td style="text-align: center;border: none">ชื่อ
                <b><?= \backend\models\Employee::findNameFromUserId($find_customer_id) ?></b></td>

        </tr>

        <tr>
            <td colspan="2" style="text-align: center;border: none">วันที่เริ่ม
                <span><b><?= date('d-m-Y', strtotime($from_date)) ?></b></span> ถึงวันที่
                <span><b><?= date('d-m-Y', strtotime($to_date)) ?></b></td>

        </tr>
    </table>
    <br/>
    <?php
    $modelx = null;
    if ($find_customer_id != null || $find_customer_id != '') {
        $modelx = \common\models\QueryUserDailyLogin::find()->select(['login_date', 'user_id', 'second_user_id'])->where(['user_id' => $find_customer_id])
            ->andFilterWhere(['BETWEEN', 'login_date', $from_date, $to_date])
            ->groupBy(['login_date', 'user_id'])->orderBy(['login_date' => SORT_ASC])->all();
    }else{
        $modelx = \common\models\QueryUserDailyLogin::find()->select(['login_date', 'user_id', 'second_user_id'])->where(['BETWEEN', 'login_date', $from_date, $to_date])
            ->groupBy(['login_date', 'user_id'])->orderBy(['login_date' => SORT_ASC])->all();
    }
    ?>
    <table style="width: 100%" id="table-data">
        <tr>
            <td style="text-align: center;padding: 0px;border: 1px solid grey;width: 5%">ลำดับ</td>
            <td style="text-align: center;padding: 0px;border: 1px solid grey;width: 10%">วันที่</td>
            <td style="text-align: center;padding: 0px;border: 1px solid grey;width: 20%">พนักงานขาย</td>
            <td style="text-align: center;padding: 0px;border: 1px solid grey;width: 20%">พนักงานคู่กะ</td>

        </tr>
        <?php if($modelx != null):?>
        <?php $x = 0;?>
            <?php foreach ($modelx as $value):?>
                <?php $x +=1;?>
            <tr>
                <td style="text-align: center"><?=$x?></td>
                <td style="text-align: center;"><?=date('d-m-Y', strtotime($value->login_date))?></td>
                <td style="text-align: left;"><?=\backend\models\Employee::findNameFromUserId($value->user_id)?></td>
                <td style="text-align: left;"><?=\backend\models\Employee::findNameFromUserId($value->second_user_id)?></td>
            </tr>
            <?php endforeach; ?>


        <?php endif; ?>
    </table>
    <br/>
    <br/>


</div>
<br/>

<table width="100%" class="table-title">
    <!--    <td>-->
    <!--        <button class="btn btn-info" onclick="printContent('div1')">พิมพ์ใบวางบิล</button>-->
    <!--    </td>-->
    <td style="text-align: right">
        <button id="btn-export-excel-top" class="btn btn-secondary">Export Excel</button>
        <!--            <button id="btn-print" class="btn btn-warning" onclick="printContent('div1')">Print</button>-->
    </td>
</table>
</body>
</html>


<?php
$this->registerJsFile(\Yii::$app->request->baseUrl . '/js/jquery.table2excel.js', ['depends' => [\yii\web\JqueryAsset::className()]]);
$js = <<<JS
 $("#btn-export-excel").click(function(){
  $("#table-data-2").table2excel({
    // exclude CSS class
    exclude: ".noExl",
    name: "Excel Document Name"
  });
});
$("#btn-export-excel-top").click(function(){
  $("#table-data").table2excel({
    // exclude CSS class
    exclude: ".noExl",
    name: "Excel Document Name"
  });
});
function printContent(el)
      {
         var restorepage = document.body.innerHTML;
         var printcontent = document.getElementById(el).innerHTML;
         document.body.innerHTML = printcontent;
         window.print();
         document.body.innerHTML = restorepage;
     }
JS;
$this->registerJs($js, static::POS_END);
?>

