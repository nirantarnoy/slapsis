<?php

namespace backend\assets;

use yii\web\AssetBundle;

/**
 * Main frontend application asset bundle.
 */
class ICheckAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/iCheck/custom.css',
        'css/iCheck/red.css'
    ];
    public $js = [
        'js/iCheck/icheck.min.js'
    ];
    public $depends = [
        'yii\web\JqueryAsset'
    ];
}
