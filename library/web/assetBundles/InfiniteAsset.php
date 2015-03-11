<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\web\assetBundles;

/**
 * TealAsset [[@doctodo class_description:teal\web\assetBundles\TealAsset]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 2.0
 */
class TealAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@teal/assets/core';
    /**
     * @inheritdoc
     */
    public $css = ['css/teal.bootstrap.css'];
    /**
     * @inheritdoc
     */
    public $js = [
        'js/teal.component.js',
        'js/teal.utils.js',
        'js/teal.core.js',
        'js/teal.bootstrap.js',
        'js/teal.ajax.instructions.js',
        'js/teal.ajax.js',
        'js/teal.smart.js',
        'js/teal.smartLine.js',
        'js/teal.expandable.js',
        'js/teal.timing.js',
        'js/teal.selector.js',
        'js/teal.search.js',
    ];
    /**
     * @inheritdoc
     */
    public $depends = [
        'yii\bootstrap\BootstrapThemeAsset',
        'yii\web\JqueryAsset',
        'yii\jui\JuiAsset',
        'teal\web\assetBundles\UnderscoreAsset',
        'teal\web\assetBundles\FontAwesomeAsset',
        'teal\web\assetBundles\AjaxFormAsset',
        'teal\web\assetBundles\BootstrapTypeaheadAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
