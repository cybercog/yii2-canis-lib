<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace infinite\web\assetBundles;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class InfiniteAsset extends AssetBundle
{
    public $sourcePath = '@infinite/assets/core';
    public $css = ['css/infinite.bootstrap.css'];
    public $js = [
        'js/infinite.utils.js',
        'js/infinite.core.js',
        'js/infinite.bootstrap.js',
        'js/infinite.ajax.instructions.js',
        'js/infinite.ajax.js',
        'js/infinite.smart.js',
        'js/infinite.expandable.js'
    ];
    public $depends = [
        'yii\bootstrap\BootstrapAsset',
        'yii\bootstrap\BootstrapThemeAsset',
        'yii\web\JqueryAsset',
        'yii\jui\CoreAsset',
        'infinite\web\assetBundles\UnderscoreAsset',
        'infinite\web\assetBundles\FontAwesomeAsset',
        'infinite\web\assetBundles\AjaxFormAsset'
    ];
}
