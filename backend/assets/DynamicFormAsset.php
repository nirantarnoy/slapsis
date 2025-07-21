<?php
namespace backend\assets;

use yii\web\AssetBundle;

class DynamicFormAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        // Font Awesome
        '//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
        // Toastr
        '//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css',
    ];

    public $js = [
        // Toastr
        '//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js',
    ];

    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
        'yii\web\JqueryAsset',
        'kartik\select2\Select2Asset',
        'wbraganca\dynamicform\DynamicFormAsset',
    ];
}