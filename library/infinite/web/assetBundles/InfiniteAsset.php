<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\assetBundles;

/**
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 2.0
 */
class InfiniteAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@infinite/assets/core';
    /**
     * @inheritdoc
     */
    public $css = ['css/infinite.bootstrap.css'];
    /**
     * @inheritdoc
     */
    public $js = [
        'js/infinite.component.js',
        'js/infinite.utils.js',
        'js/infinite.core.js',
        'js/infinite.bootstrap.js',
        'js/infinite.ajax.instructions.js',
        'js/infinite.ajax.js',
        'js/infinite.smart.js',
        'js/infinite.smartLine.js',
        'js/infinite.expandable.js',
        'js/infinite.timing.js',
        'js/infinite.selector.js',
        'js/infinite.search.js',
    ];
    /**
     * @inheritdoc
     */
    public $depends = [
        'yii\bootstrap\BootstrapThemeAsset',
        'yii\web\JqueryAsset',
        'yii\jui\JuiAsset',
        'infinite\web\assetBundles\UnderscoreAsset',
        'infinite\web\assetBundles\FontAwesomeAsset',
        'infinite\web\assetBundles\AjaxFormAsset',
        'infinite\web\assetBundles\BootstrapTypeaheadAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
