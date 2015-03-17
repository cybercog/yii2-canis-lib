<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\web\assetBundles;

/**
 * AutoNavAsset [[@doctodo class_description:canis\web\assetBundles\AutoNavAsset]].
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
    public $sourcePath = '@canis/assets/autoNav';
    /**
     * @inheritdoc
     */
    public $css = ['css/canis.autoNav.css'];
    /**
     * @inheritdoc
     */
    public $js = [
        'js/canis.autoNav.js',
    ];
    /**
     * @inheritdoc
     */
    public $depends = [
        'canis\web\assetBundles\CanisAsset',
    ];
}
