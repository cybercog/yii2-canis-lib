<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\web\assetBundles;

/**
 * TealBrowserAsset [[@doctodo class_description:teal\web\assetBundles\TealBrowserAsset]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 2.0
 */
class TealBrowserAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@teal/assets/browser';
    /**
     * @inheritdoc
     */
    public $css = ['css/teal.browser.css'];
    /**
     * @inheritdoc
     */
    public $js = [
        'js/teal.browser.js',
    ];
    /**
     * @inheritdoc
     */
    public $depends = [
        'teal\web\assetBundles\TealAsset',
    ];
}
