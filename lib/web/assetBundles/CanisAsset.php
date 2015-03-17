<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\web\assetBundles;

/**
 * CanisAsset [[@doctodo class_description:canis\web\assetBundles\CanisAsset]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 2.0
 */
class CanisAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@canis/assets/core';
    /**
     * @inheritdoc
     */
    public $css = ['css/canis.bootstrap.css'];
    /**
     * @inheritdoc
     */
    public $js = [
        'js/canis.component.js',
        'js/canis.utils.js',
        'js/canis.core.js',
        'js/canis.bootstrap.js',
        'js/canis.ajax.instructions.js',
        'js/canis.ajax.js',
        'js/canis.smart.js',
        'js/canis.smartLine.js',
        'js/canis.expandable.js',
        'js/canis.timing.js',
        'js/canis.selector.js',
        'js/canis.search.js',
    ];
    /**
     * @inheritdoc
     */
    public $depends = [
        'yii\bootstrap\BootstrapThemeAsset',
        'yii\web\JqueryAsset',
        'yii\jui\JuiAsset',
        'canis\web\assetBundles\UnderscoreAsset',
        'canis\web\assetBundles\FontAwesomeAsset',
        'canis\web\assetBundles\AjaxFormAsset',
        'canis\web\assetBundles\BootstrapTypeaheadAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
