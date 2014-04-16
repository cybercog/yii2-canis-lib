<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
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
