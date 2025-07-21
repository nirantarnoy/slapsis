<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\date\DatePicker;
use wbraganca\dynamicform\DynamicFormWidget;
use common\models\JournalTrans;
use yii\web\JsExpression;
use kartik\select2\Select2;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\JournalTrans */
/* @var $form yii\widgets\ActiveForm */
/* @var $modelLines app\models\JournalTransLine[] */

// CSS สำหรับซ่อนปุ่มและจัดรูปแบบ
$css = '
.dynamicform_wrapper .panel-heading {
    display: none;
}
.dynamicform_wrapper .item {
    border: none;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}
.dynamicform_wrapper .item:last-child {
    border-bottom: none;
}
.form-buttons-container {
    margin-bottom: 15px;
    padding: 10px;
    background-color: #f5f5f5;
    border-radius: 4px;
}
.select2-container {
    width: 100% !important;
}
';

$this->registerCss($css);

// Fix Select2 styling
$select2FixCss = <<<CSS
/* Reset and fix Select2 styling */
.select2-container--default .select2-selection--single {
    background-color: #fff;
    border: 1px solid #ccc;
    border-radius: 4px;
    height: 34px !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #555;
    line-height: 34px;
    padding-left: 12px;
    padding-right: 20px;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 32px;
    position: absolute;
    top: 1px;
    right: 1px;
    width: 20px;
}

.select2-container--default .select2-selection--single .select2-selection__arrow b {
    border-color: #888 transparent transparent transparent;
    border-style: solid;
    border-width: 5px 4px 0 4px;
    height: 0;
    left: 50%;
    margin-left: -4px;
    margin-top: -2px;
    position: absolute;
    top: 50%;
    width: 0;
}

.select2-container {
    width: 100% !important;
}

/* Fix for dynamic form */
.dynamicform_wrapper .select2-container {
    display: block !important;
}

.dynamicform_wrapper .form-control.select2-hidden-accessible {
    position: absolute !important;
    left: -9999px !important;
}

/* Fix dropdown */
.select2-dropdown {
    background-color: white;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
    display: block;
    position: absolute;
    width: 100%;
    z-index: 1051;
}

/* Fix focus state */
.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: #66afe9;
    outline: 0;
    box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(102, 175, 233, .6);
}
CSS;

$this->registerCss($select2FixCss);

// Register Font Awesome
$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');

// Define initSelect2Loading and initSelect2DropStyle functions BEFORE any other scripts
$initFunctionsJs = <<<JS
// Define global functions for Select2 initialization
window.initSelect2Loading = function(id, placeholder) {
    // Function to initialize Select2 with loading
    var selector = '#' + id;
    if ($(selector).length && $.fn.select2) {
        $(selector).select2({
            theme: 'krajee',
            placeholder: placeholder || '-- เลือก --',
            allowClear: true,
            width: '100%'
        });
    }
};

window.initSelect2DropStyle = function(id, placeholder) {
    // Function to initialize Select2 with custom style
    var selector = '#' + id;
    if ($(selector).length && $.fn.select2) {
        $(selector).select2({
            theme: 'krajee',
            placeholder: placeholder || '-- เลือก --',
            allowClear: true,
            width: '100%'
        });
    }
};

// Define other initialization functions that might be needed
window.initS2Loading = function(id, placeholder) {
    return window.initSelect2Loading(id, placeholder);
};
JS;

// Register these functions FIRST, before any other scripts
$this->registerJs($initFunctionsJs, \yii\web\View::POS_HEAD);

$is_disabled_maker = true;
if ($create_type == 7) {
    $is_disabled_maker = false;
}
?>

    <div class="journal-trans-form">

        <?php $form = ActiveForm::begin(['id' => 'dynamic-form']); ?>
        <?php $model->trans_type_id = $model->isNewRecord ? $create_type : $model->trans_type_id ?>
        <?= $form->field($model, 'trans_type_id')->hiddenInput()->label(false) ?>
        <div class="row">
            <div class="col-md-3">
                <!--                --><?php //= $form->field($model, 'trans_type_id')->widget(Select2::className(),[
                //                    'data' => ArrayHelper::map(\backend\helpers\TransType::asArrayObject(), 'id', 'name'),
                //                    'options' => ['placeholder' => '-- เลือกประเภทรายการ --','disabled' => true],
                //                    'pluginOptions' => [
                //                        'allowClear' => true,
                //                        'theme' => 'krajee',
                //                    ],
                //                ]) ?>
                <label for="">ประเภทรายการ</label>
                <input type="text" class="form-control"
                       value="<?= \backend\helpers\TransType::getTypeById($model->trans_type_id) ?>"
                       readonly="readonly">
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'trans_date')->widget(DatePicker::classname(), [
                    'options' => ['placeholder' => 'วันที่ทำรายการ ...'],
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'yyyy-mm-dd',
                        'todayHighlight' => true,
                    ]
                ]) ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'warehouse_id')->widget(Select2::className(), [
                    'data' => ArrayHelper::map(\backend\models\Warehouse::find()->all(), 'id', 'name'),
                    'options' => ['placeholder' => '-- เลือกคลัง --','onchange'=>'getWarehouseproduct(this.value)'],
                    'pluginOptions' => [
                        'allowClear' => true,
                        'theme' => 'krajee',
                    ],
                ]) ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'party_id')->widget(Select2::className(), [
                    'data' => ArrayHelper::map(\backend\models\Watchmaker::find()->all(), 'id', 'name'),
                    'options' => ['class' => 'form-control party-id', 'placeholder' => '-- เลือกช่าง --', 'disabled' => $is_disabled_maker],
                    'pluginOptions' => [
                        'allowClear' => true,
                        'theme' => 'krajee',
                    ],
                ]) ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <?= $form->field($model, 'customer_name')->textInput(['maxlength' => true]) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'remark')->textInput(['maxlength' => true]) ?>
            </div>
        </div>
        <br/>
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4><i class="fa fa-list"></i> รายการสินค้า</h4>
            </div>
            <div class="panel-body">
                <!-- Container สำหรับปุ่มเพิ่มรายการ -->
                <div class="form-buttons-container">
                    <!-- ปุ่มเพิ่มจะถูกย้ายมาที่นี่ด้วย JavaScript -->
                </div>

                <?php DynamicFormWidget::begin([
                    'widgetContainer' => 'dynamicform_wrapper',
                    'widgetBody' => '.container-items',
                    'widgetItem' => '.item',
                    'limit' => 50,
                    'min' => 1,
                    'insertButton' => '.add-item',
                    'deleteButton' => '.remove-item',
                    'model' => $modelLines[0],
                    'formId' => 'dynamic-form',
                    'formFields' => [
                        'product_id',
                        'qty',
                        'remark',
                    ],
                ]); ?>

                <div class="container-items">
                    <?php foreach ($modelLines as $i => $modelLine): ?>
                        <div class="item panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title pull-left panel-title-address">รายการที่: <?= ($i + 1) ?></h3>
                                <div class="pull-right">
                                    <button type="button" class="add-item btn btn-success btn-xs"><i
                                                class="glyphicon glyphicon-plus"></i></button>
                                    <button type="button" class="remove-item btn btn-danger btn-xs"><i
                                                class="glyphicon glyphicon-minus"></i></button>
                                </div>
                                <div class="clearfix"></div>
                            </div>
                            <div class="panel-body">
                                <?php
                                // necessary for update action.
                                if (!$modelLine->isNewRecord) {
                                    echo Html::activeHiddenInput($modelLine, "[{$i}]id");
                                }
                                ?>
                                <div class="row">
                                    <div class="col-sm-3">
                                        <!--                                        --><?php //= $form->field($modelLine, "[{$i}]product_id")->widget(Select2::className(),[
                                        //                                            'data' => ArrayHelper::map(\backend\models\Product::find()->all(), 'id', 'name'),
                                        //                                            'options' => [
                                        //                                                'placeholder' => '-- เลือกสินค้า --',
                                        //                                                'class' => 'form-control product-select'
                                        //                                            ],
                                        //                                            'pluginOptions' => [
                                        //                                                'allowClear' => true,
                                        //                                                'theme' => 'krajee',
                                        //                                            ],
                                        //                                        ]) ?>
                                        <?= $form->field($modelLine, "[{$i}]product_id")->dropDownList(
                                            ArrayHelper::map(\backend\models\Product::find()->all(), 'id', 'name'),
                                            ['prompt' => '-- เลือกสินค้า --', 'class' => 'form-control product-select']
                                        ) ?>
                                    </div>
                                    <div class="col-sm-2">
                                        <?= $form->field($modelLine, "[{$i}]qty")->textInput(['type' => 'number', 'step' => '0.01']) ?>
                                    </div>
                                    <div class="col-sm-5">
                                        <?= $form->field($modelLine, "[{$i}]remark")->textInput(['maxlength' => true]) ?>
                                    </div>
                                    <div class="col-sm-1 text-right" style="padding-top: 25px;">
                                        <button type="button" class="remove-item btn btn-danger btn-sm"><i
                                                    class="fa fa-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php DynamicFormWidget::end(); ?>
            </div>
        </div>

        <?php if ($model->status != 3): ?>
            <div class="form-group">
                <?= Html::submitButton($model->isNewRecord ? 'บันทึก' : 'บันทึกการแก้ไข', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
                <?= Html::a('ยกเลิก', ['index'], ['class' => 'btn btn-default']) ?>
            </div>
        <?php endif; ?>

        <?php ActiveForm::end(); ?>

    </div>

<?php

// Main JavaScript for dynamic form handling
$mainJs = <<<JS
$(document).ready(function() {
    // Initialize the form
    initializeDynamicForm();

    // Override the afterInsert event to properly initialize Select2
    $('.dynamicform_wrapper').on('afterInsert', function(e, item) {
        onAfterInsert(e, item);
    });

    // Override the afterDelete event
    $('.dynamicform_wrapper').on('afterDelete', function(e) {
        onAfterDelete(e);
    });

     $('.product-select').select2({
        theme: 'default',
        placeholder: '-- เลือกสินค้า --',
        allowClear: true,
        width: '100%'
    });
     
      // Initialize all existing select2
    // setupSelect2('.product-select');
    // setupSelect2('.select2-single');
});

// function setupSelect2(selector) {
//         $(selector).each(function() {
//             var \$element = $(this);
//            
//             // Skip if already initialized
//             if (\$element.hasClass('select2-hidden-accessible')) {
//                 return;
//             }
//            
//             // Get placeholder text
//             var placeholder = \$element.find('option:first').text() || '-- เลือก --';
//            
//             // Initialize Select2
//             \$element.select2({
//                 theme: 'default',
//                 width: '100%',
//                 placeholder: placeholder,
//                 allowClear: true,
//                 minimumResultsForSearch: 5
//             });
//         });
// }
    
   

function initializeDynamicForm() {
    // Create main add button
    if ($('.main-add-button').length === 0) {
        var addButton = $('<button>')
            .attr('type', 'button')
            .addClass('btn btn-sm btn-success main-add-button')
            .html('<i class="fa fa-plus"></i> เพิ่มรายการ');

        $('.form-buttons-container').append(addButton);

        // Add click handler
        $(document).on('click', '.main-add-button', function(e) {
            e.preventDefault();
            $('.dynamicform_wrapper .add-item:last').trigger('click');
        });
    }

    // Hide all item add buttons
    $('.item .add-item').hide();

    // Update remove buttons
    $('.remove-item').each(function() {
        $(this).html('<i class="fa fa-trash"></i>');
    });

    // Update row numbers
    updateRowNumbers();

    // Handle warehouse change
    $('#journaltrans-warehouse_id').on('change', function() {
        var warehouseId = $(this).val();
        $('.container-items input[name*="[warehouse_id]"]').val(warehouseId);
    });
}

function onAfterInsert(e, item) {
    // Hide add button in new item
    $(item).find('.add-item').hide();

    // Update remove button
    $(item).find('.remove-item').html('<i class="fa fa-trash"></i>');

    // Copy warehouse value
    var warehouseId = $('#journaltrans-warehouse_id').val();
    if (warehouseId) {
        $(item).find('input[name*="[warehouse_id]"]').val(warehouseId);
    }

    // Update row numbers
    updateRowNumbers();

    // Re-initialize Select2 for new items
    $(item).find('.select2-container').remove(); // Remove old select2 containers
    $(item).find('select.product-select').each(function() {
        var elementId = $(this).attr('id');
        if (elementId && window.initSelect2Loading) {
            window.initSelect2Loading(elementId, '-- เลือกสินค้า --');
            $(this).select2({
               // theme: 'default',
                placeholder: '-- เลือกสินค้า --',
                allowClear: true,
                width: '100%'
            });
        } else {
            // Fallback initialization
            $(this).select2({
               // theme: 'default',
                placeholder: '-- เลือกสินค้า --',
                allowClear: true,
                width: '100%'
            });
        }
    });
}

function onAfterDelete(e) {
    var itemCount = $('.dynamicform_wrapper .item').length;
    if (itemCount === 0) {
        // Add new item if none left
        setTimeout(function() {
            $('.main-add-button').trigger('click');
        }, 100);
    }
    updateRowNumbers();
}

function updateRowNumbers() {
    $('.dynamicform_wrapper .item').each(function(index) {
        $(this).find('.panel-title-address').text('รายการที่: ' + (index + 1));
    });
}
JS;

// Register main JavaScript
$this->registerJs($mainJs, \yii\web\View::POS_READY);

$select2FixJs = <<<JS
// Wait for all assets to load
$(window).on('load', function() {
    // Function to properly initialize Select2
    function setupSelect2(selector) {
        $(selector).each(function() {
            var \$element = $(this);
            
            // Skip if already initialized
            if (\$element.hasClass('select2-hidden-accessible')) {
                return;
            }
            
            // Get placeholder text
            var placeholder = \$element.find('option:first').text() || '-- เลือก --';
            
            // Initialize Select2
            \$element.select2({
                theme: 'default',
                width: '100%',
                placeholder: placeholder,
                allowClear: true,
                minimumResultsForSearch: 5
            });
        });
    }
    
    // Initialize all existing select2
    //setupSelect2('.product-select');
    //setupSelect2('.select2-single');
    
    // // Fix for dynamic form
    // var dynamicFormReady = false;
    //
    // $('.dynamicform_wrapper').on('afterInsert', function(e, item) {
    //     if (!dynamicFormReady) {
    //         dynamicFormReady = true;
    //        
    //         setTimeout(function() {
    //             // Find new select elements
    //             var \$newSelects = $(item).find('select[name*="product_id"]');
    //            
    //             // Remove any existing Select2
    //             \$newSelects.each(function() {
    //                 if ($(this).hasClass('select2-hidden-accessible')) {
    //                     $(this).select2('destroy');
    //                 }
    //             });
    //            
    //             // Remove old containers
    //             $(item).find('.select2-container').remove();
    //            
    //             // Initialize Select2
    //             setupSelect2(\$newSelects);
    //            
    //             dynamicFormReady = false;
    //         }, 200);
    //     }
    //    
    //     // Update other fields
    //     updateItemNumbers();
    //     updateWarehouseValue(item);
    // });
    
    // Function to update item numbers
    function updateItemNumbers() {
        $('.dynamicform_wrapper .item').each(function(index) {
            $(this).find('.item-number').text('รายการที่ ' + (index + 1));
        });
    }
    
    // Function to update warehouse value
    function updateWarehouseValue(item) {
        var warehouseId = $('#journaltrans-warehouse_id').val();
        var warehouseName = $('#journaltrans-warehouse_id option:selected').text();
        
        if (warehouseId) {
            $(item).find('.warehouse-line-input').val(warehouseId);
            $(item).find('.warehouse-display').val(warehouseName);
        }
    }
    
    function getWarehouseproduct(id){
        if(id){
            $.ajax({
                url: '/journaltrans/getwarehouseproduct',
                type: 'POST',
                data: {id: id},
                dataType: 'html',
                success: function(data) {
                    if(data!='' || data!=null){
                        $('.product-select').html(data);
                    }
                }
            });
        }
    }
});
JS;

//$this->registerJs($select2FixJs, \yii\web\View::POS_END);

?>