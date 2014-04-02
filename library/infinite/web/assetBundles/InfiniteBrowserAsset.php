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
class InfiniteBrowserAsset extends AssetBundle
{
    public $sourcePath = '@infinite/assets/browser';
    public $css = ['css/infinite.browser.css'];
    public $js = [
    	'js/infinite.browser.js'
    ];
    public $depends = [
		'infinite\web\assetBundles\InfiniteAsset'
    ];
}
