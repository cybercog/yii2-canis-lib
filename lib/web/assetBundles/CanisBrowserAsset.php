<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\web\assetBundles;

/**
 * CanisBrowserAsset [[@doctodo class_description:canis\web\assetBundles\CanisBrowserAsset]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 2.0
 */
class CanisBrowserAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@canis/assets/browser';
    /**
     * @inheritdoc
     */
    public $css = ['css/canis.browser.css'];
    /**
     * @inheritdoc
     */
    public $js = [
        'js/canis.browser.js',
    ];
    /**
     * @inheritdoc
     */
    public $depends = [
        'canis\web\assetBundles\CanisAsset',
    ];
}
