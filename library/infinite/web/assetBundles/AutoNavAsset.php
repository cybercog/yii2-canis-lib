<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\assetBundles;

/**
 * AutoNavAsset [[@doctodo class_description:infinite\web\assetBundles\AutoNavAsset]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 2.0
 */
class AutoNavAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@infinite/assets/autoNav';
    /**
     * @inheritdoc
     */
    public $css = ['css/infinite.autoNav.css'];
    /**
     * @inheritdoc
     */
    public $js = [
        'js/infinite.autoNav.js',
    ];
    /**
     * @inheritdoc
     */
    public $depends = [
        'infinite\web\assetBundles\InfiniteAsset',
    ];
}
