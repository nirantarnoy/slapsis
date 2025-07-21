<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var backend\models\Journalreceive $model */
/** @var yii\widgets\ActiveForm $form */

$warehouse_data = \backend\models\Warehouse::find()->where(['status' => 1])->all();
$product_data = \backend\models\Product::find()->all();
?>

<div class="journalreceive-form">

    <?php $form = ActiveForm::begin(['id'=>'form-journalrec']); ?>

    <input type="hidden" name="removelist" class="remove-list" value="">
    <div class="row">
        <div class="col-lg-4">
            <?= $form->field($model, 'journal_no')->textInput(['maxlength' => true, 'readonly' => 'readonly']) ?>
        </div>
        <div class="col-lg-4">
            <?php $model->trans_date = $model->isNewRecord ? date('d-m-Y') : date('d-m-Y', strtotime($model->trans_date)) ?>
            <?= $form->field($model, 'trans_date')->widget(\kartik\date\DatePicker::className(), [
                'value' => date('d-m-Y'),
                'pluginOptions' => [
                    'format' => 'dd-mm-yyyy'
                ]
            ]) ?>
        </div>
        <div class="col-lg-4">
            <?= $form->field($model, 'remark')->textInput(['maxlength' => true]) ?>
        </div>
    </div>
    <div class="row">

        <div class="col-lg-4">
            <?php //echo $form->field($model, 'status')->textInput() ?>
        </div>
    </div>
    <br/>

    <div class="row">
        <div class="col-lg-12">
            <table class="table table-bordered" id="table-list">
                <thead>
                <tr>
                    <th style="width: 5%">#</th>
                    <th style="width: 15%">รหัสสินค้า</th>
                    <th>รายละเอียด</th>
                    <th style="width: 10%;text-align: left;">คลังสินค้า</th>

                    <th style="width: 10%;text-align: right;">จำนวนรับ</th>
                    <th style="width: 15%;text-align: left;">หมายเหตุ</th>
                    <th style="width: 5%;text-align: center;">-</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($model->isNewRecord): ?>
                    <tr>
                        <td></td>
                        <td>
                            <input type="hidden" class="line-product-id" name="line_product_id[]" value="">
                            <input type="text" class="form-control line-product-code" name="line_product_code[]"
                                   value="" readonly>
                        </td>
                        <td>
                            <input type="text" class="form-control line-product-name" name="line_product_name[]"
                                   value="" readonly>
                        </td>
                        <td>

                            <select class="form-control line-product-warehouse-id" name="line_product_warehouse_id[]"
                                    id="" onchange="">
                                <option value="-1">--เลือกคลัง--</option>
                                <?php foreach ($warehouse_data as $value_wh): ?>

                                    <option value="<?= $value_wh->id ?>"><?= $value_wh->name ?></option>
                                <?php endforeach; ?>
                            </select>

                        </td>

                        <td>
                            <input type="number" class="form-control line-qty" name="line_qty[]" min="0" value=""
                                   onchange="linecal($(this))">
                        </td>
                        <td>
                            <input type="text" class="form-control line-remark" name="line_remark[]" value="">
                        </td>
                        <td>
                            <div class="btn btn-sm btn-danger" onclick="removeline($(this))"><i
                                        class="fa fa-trash"></i>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php if ($model_line != null): ?>
                        <?php $line_num = 0; ?>
                        <?php foreach ($model_line as $value): ?>
                            <?php $line_num += 1; ?>
                            <tr data-var="<?= $value->id ?>">
                                <td style="text-align: center;"><?= $line_num; ?></td>
                                <td>
                                    <input type="hidden" name="line_rec_id[]" value="<?= $value->id ?>">
                                    <input type="hidden" class="line-product-id" name="line_product_id[]"
                                           value="<?= $value->product_id ?>">
                                    <input type="text" class="form-control line-product-code"
                                           name="line_product_code[]"
                                           value="<?= \backend\models\Product::findCode($value->product_id) ?>"
                                           readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control line-product-name"
                                           name="line_product_name[]"
                                           value="<?= \backend\models\Product::findName($value->product_id) ?>"
                                    >
                                </td>
                                <td>
                                    <select class="form-control" name="line_product_warehouse_id[]"
                                            id="line-product-warehouse-id" onchange="pullstocksum($(this))">
                                        <option value="-1">--เลือกคลัง--</option>
                                        <?php foreach ($warehouse_data as $value_wh): ?>
                                            <?php $selected = ($value_wh->id == $value->warehouse_id) ? 'selected' : ''; ?>
                                            <option value="<?= $value_wh->id ?>" <?= $selected ?>><?= $value_wh->name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" class="form-control line-qty" name="line_qty[]"
                                           value="<?= $value->qty ?>"
                                           onchange="linecal($(this))">
                                </td>
                                <td>
                                    <input type="text" class="form-control line-remark" name="line_remark[]"
                                           value="<?= $value->remark ?>">
                                </td>
                                <td>
                                    <?php if ($model->isNewRecord): ?>
                                        <div class="btn btn-sm btn-danger" onclick="removeline($(this))"><i
                                                    class="fa fa-trash"></i>
                                        </div>
                                    <?php elseif (!$model->isNewRecord && $value->status != 3): ?>
                                        <div class="btn btn-sm btn-secondary" onclick="cancelline($(this))">ยกเลิก
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td></td>
                            <td>
                                <input type="hidden" class="line-product-id" name="line_product_id[]" value="">
                                <input type="text" class="form-control line-product-code" name="line_product_code[]"
                                       value="" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control line-product-name" name="line_product_name[]"
                                       value="" readonly>
                            </td>
                            <td>

                                <select class="form-control" name="line_product_warehouse_id[]"
                                        id="line-product-warehouse-id" onchange="">
                                    <option value="-1">--เลือกคลัง--</option>
                                    <?php foreach ($warehouse_data as $value_wh): ?>

                                        <option value="<?= $value_wh->id ?>"><?= $value_wh->name ?></option>
                                    <?php endforeach; ?>
                                </select>

                            </td>

                            <td>
                                <input type="number" class="form-control line-qty" name="line_qty[]" min="0" value=""
                                       onchange="linecal($(this))">
                            </td>
                            <td>
                                <input type="text" class="form-control line-remark" name="line_remark[]" value="">
                            </td>
                            <td>
                                <div class="btn btn-sm btn-danger" onclick="removeline($(this))"><i
                                            class="fa fa-trash"></i>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endif; ?>

                </tbody>
                <tfoot>
                <tr>
                    <td style="text-align: center;">
                        <div class="btn btn-sm btn-primary" onclick="finditem();"><i class="fa fa-plus"></i></div>
                    </td>
                    <td colspan="3" style="text-align: right">รวม</td>
                    <td>
                        <input type="text" class="form-control qty-all-total" value="0"
                               readonly>
                    </td>
                    <td></td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>


    <div class="form-group">
        <?php if($model->isNewRecord):?>
        <?php //echo Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
            <div class="btn btn-success" onclick="submitForm()">Save</div>
        <?php endif;?>
    </div>

    <?php ActiveForm::end(); ?>

</div>


<div id="findModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-xl">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <h3>รายการสินค้า</h3>
            </div>
            <!--            <div class="modal-body" style="white-space:nowrap;overflow-y: auto">-->
            <!--            <div class="modal-body" style="white-space:nowrap;overflow-y: auto;scrollbar-x-position: top">-->

            <div class="modal-body">
                <input type="hidden" name="line_qc_product" class="line_qc_product" value="">
                <table class="table table-bordered table-striped table-find-list" width="100%">
                    <thead>
                    <tr>
                        <th style="width:10%;text-align: center">เลือก</th>
                        <th style="width: 20%;text-align: center">รหัส</th>
                        <th style="width: 20%;text-align: center">รายละเอียด</th>
                        <th>คลังสินค้า</th>
                        <th>คงเหลือ</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>

            </div>

            <div class="modal-footer">
                <button class="btn btn-outline-success btn-emp-selected" data-dismiss="modalx" disabled><i
                            class="fa fa-check"></i> ตกลง
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><i
                            class="fa fa-close text-danger"></i> ปิดหน้าต่าง
                </button>
            </div>
        </div>

    </div>
</div>

<?php
$url_to_find_item = \yii\helpers\Url::to(['product/finditem'], true);
$js=<<<JS
var selecteditem = [];
var selectedorderlineid = [];
var selecteditemgroup = [];
var customer_id = 0;
var removelist = [];
$(function(){
   calall();
});
function submitForm(){
    var check_data = 0;
    $("#table-list tbody tr").each(function(){
          if($(this).closest("tr").find(".line-product-id").val() == ''){
            check_data +=1;
          } 
       });
    if(check_data > 0){
         alert('กรุณาเลือกรายละเอียดก่อนทำรายการ');
              return false;
    }else{
        $("form#form-journalrec").submit();
    }
}
function finditem(){
     //   alert(customer_id);
        $.ajax({
          type: 'post',
          dataType: 'html',
          url:'$url_to_find_item',
          async: false,
          data: {},
          success: function(data){
             // alert(data);
              $(".table-find-list tbody").html(data);
              $("#findModal").modal("show");
          },
          error: function(err){
              alert(err);
          }
        });
}

function addline(e){
    var tr = $("#table-list tbody tr:last");
                    var clone = tr.clone();
                    //clone.find(":text").val("");
                    // clone.find("td:eq(1)").text("");
                   clone.find(".line-text").val("0");
                   clone.find(".line-order-no").val("");
                   clone.find(".line-qty").val("0");
                   clone.find(".line-price").val("0");
                   clone.find(".line-total").val("0");
                   
                    clone.attr("data-var", "");
                    clone.find('.rec-id').val("0");
                   
                    tr.after(clone);
    
}
function removeline(e) {
       
                if (confirm("ต้องการลบรายการนี้ใช่หรือไม่?")) {
                if (e.parent().parent().attr("data-var") != '') {
                    removelist.push(e.parent().parent().attr("data-var"));
                    $(".remove-list").val(removelist);
                }
                // alert(removelist);
                // alert(e.parent().parent().attr("data-var"));
    
                if ($("#table-list tbody tr").length == 1) {
                    $("#table-list tbody tr").each(function () {
                        $(this).find(":text").val("");
                       // $(this).find(".line-prod-photo").attr('src', '');
                        $(this).find(".line-item-qty").val('');
                        $(this).find(".line-item-price").val('');
                        $(this).find(".line-item-total").val('');
                        // cal_num();
                    });
                } else {
                    e.parent().parent().remove();
                }
                // cal_linenum();
                // cal_all();
                calall();
            }
        
        
}
function cancelline(e) {
       
                if (confirm("ต้องการยกเลิกรายการนี้ใช่หรือไม่?")) {
                if (e.parent().parent().attr("data-var") != '') {
                    removelist.push(e.parent().parent().attr("data-var"));
                    $(".remove-list").val(removelist);
                }
                if(e.hasClass('btn-secondary')){
                    e.removeClass('btn-secondary');
                    e.addClass('btn-success');
                }else{
                    e.addClass('btn-secondary');
                    e.removeClass('btn-success');
                }
            }
        
        
}

function addselecteditem(e) {
        var id = e.attr('data-var');
        var item_id = e.closest('tr').find('.line-find-item-id').val();

        ///// add new 
         var item_code = e.closest('tr').find('.line-find-item-code').val();
         var item_name = e.closest('tr').find('.line-find-item-name').val();
         var onhand = e.closest('tr').find('.line-find-onhand-qty').val();
         var warehouse_id = e.closest('tr').find('.line-find-warehouse-id').val();
         var warehouse_name = e.closest('tr').find('.line-find-warehouse-name').val();
        ///////
        if (id) {
            // if(checkhasempdaily(id)){
            //     alert("คุณได้ทำการจัดรถให้พนักงานคนนี้ไปแล้ว");
            //     return false;
            // }
            if (e.hasClass('btn-outline-success')) {
                var obj = {};
                obj['id'] = id;
                obj['item_id'] = item_id;
                obj['item_code'] = item_code;
                obj['item_name'] = item_name;
                obj['qty'] = onhand;
                obj['warehouse_id'] = warehouse_id;
                obj['warehouse_name'] = warehouse_name;
                selecteditem.push(obj);
                selectedorderlineid.push(obj['id']);
                    // var obj_after = {};
                    // obj_after['qty'] = order_line_qty;
                    // obj_after['price'] = order_line_price;
                    // obj_after['discount'] = 0;
                    // obj_after['total'] = (order_line_qty * order_line_price);
                    //
                    // alert(obj_after['product_group_id']);
                    // alert(obj_after['product_group_name']);
                    // alert(obj_after['qty']);
                    
            
                e.removeClass('btn-outline-success');
                e.addClass('btn-success');
                disableselectitem();
                console.log(selecteditem);
            } else {
                //selecteditem.pop(id);
                $.each(selecteditem, function (i, el) {
                    if (this.id == id) {
                        var qty = this.qty;
                        selecteditem.splice(i, 1);
                        selectedorderlineid.splice(i,1);
                      //  deleteorderlineselected(product_group_id, qty); // update data in selected list
                        console.log(selecteditemgroup);
                      //  caltablecontent(); // refresh table below
                    }
                });
                e.removeClass('btn-success');
                e.addClass('btn-outline-success');
                
                disableselectitem();
                console.log(selecteditem);
                console.log(selectedorderlineid);
                console.log(selecteditemgroup);
            }
        }
        $(".orderline-id-list").val(selectedorderlineid);
}

function disableselectitem() {
        if (selecteditem.length > 0) {
            $(".btn-emp-selected").prop("disabled", "");
            $(".btn-emp-selected").removeClass('btn-outline-success');
            $(".btn-emp-selected").addClass('btn-success');
        } else {
            $(".btn-emp-selected").prop("disabled", "disabled");
            $(".btn-emp-selected").removeClass('btn-success');
            $(".btn-emp-selected").addClass('btn-outline-success');
        }
}

$(".btn-emp-selected").click(function () {
        var linenum = 0;
        var line_count = 0;
      
        if(selecteditem.length >0){
             var tr = $("#table-list tbody tr:last");
             
             for(var i=0;i<=selecteditem.length-1;i++){
               //  var new_text = selecteditem[i]['line_work_type_name'] + "\\n" + "Order No."+selecteditem[i]['line_order_no'];
                   if (tr.closest("tr").find(".line-product-id").val() == "") {
                  //  alert(line_prod_code);
            
                    tr.closest("tr").find(".line-product-id").val(selecteditem[i]['item_id']);
                    tr.closest("tr").find(".line-product-code").val(selecteditem[i]['item_code']);
                    tr.closest("tr").find(".line-product-name").val(selecteditem[i]['item_name']);
                    tr.closest("tr").find(".line-product-onhand").val(selecteditem[i]['qty']);
                    tr.closest("tr").find(".line-product-warehouse-id").val(selecteditem[i]['warehouse_id']);
                    tr.closest("tr").find(".line-product-warehouse-name").val(selecteditem[i]['warehouse_name']);
                    //console.log(line_prod_code);
                    } else {
                       
                        var clone = tr.clone();
                        clone.closest("tr").find(".line-rec-id").val('0');
                        clone.closest("tr").find(".line-product-id").val(selecteditem[i]['item_id']);
                        clone.closest("tr").find(".line-product-code").val(selecteditem[i]['item_code']);
                        clone.closest("tr").find(".line-product-name").val(selecteditem[i]['item_name']);
                        clone.closest("tr").find(".line-product-onhand").val(selecteditem[i]['qty']);
                        clone.closest("tr").find(".line-product-warehouse-id").val(selecteditem[i]['warehouse_id']);
                        clone.closest("tr").find(".line-product-warehouse-name").val(selecteditem[i]['warehouse_name']);
                      
                        tr.after(clone);
                    } 
             }
                
          
        }
        
        $("#table-list tbody tr").each(function () {
           linenum += 1;
            $(this).closest("tr").find("td:eq(0)").text(linenum);
            // $(this).closest("tr").find(".line-prod-code").val(line_prod_code);
        });
        
        selecteditem = [];
        selectedorderlineid = [];
        selecteditemgroup = [];

        $("#table-find-list tbody tr").each(function () {
            $(this).closest("tr").find(".btn-line-select").removeClass('btn-success');
            $(this).closest("tr").find(".btn-line-select").addClass('btn-outline-success');
        });
        
        $(".btn-emp-selected").removeClass('btn-success');
        $(".btn-emp-selected").addClass('btn-outline-success');
        $("#findModal").modal('hide'); 
});


function linecal(e){
    calall();
    
}
function calall(){
    
    var total_qty = 0;
  
      $("#table-list tbody tr").each(function () {
           var line_qty = $(this).find('.line-qty').val();
         //  alert(line_amt);
           if(line_qty != null){
               total_qty = parseFloat(total_qty) + parseFloat(line_qty);
           }
          
      });
      
    $(".qty-all-total").val(parseFloat(total_qty).toFixed(0));
   
}
JS;
$this->registerJs($js,static::POS_END);
?>
