<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace infinite\web;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class InfiniteAsset extends AssetBundle
{
    public $sourcePath = '@infinite/assets';
    public $css = ['css/infinite.bootstrap.css'];
    public $js = ['js/infinite.bootstrap.js', 'js/infinite.utils.js', 'js/infinite.ajax.js'];
    public $depends = ['yii\bootstrap\BootstrapAsset', 'yii\web\JqueryAsset', 'yii\jui\CoreAsset', 'infinite\web\UnderscoreAsset', 'infinite\web\FontAwesomeAsset'];
}
