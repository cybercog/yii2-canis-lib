<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\web\assetBundles;

/**
 * AutoNavAsset [[@doctodo class_description:teal\web\assetBundles\AutoNavAsset]].
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
    public $sourcePath = '@teal/assets/autoNav';
    /**
     * @inheritdoc
     */
    public $css = ['css/teal.autoNav.css'];
    /**
     * @inheritdoc
     */
    public $js = [
        'js/teal.autoNav.js',
    ];
    /**
     * @inheritdoc
     */
    public $depends = [
        'teal\web\assetBundles\TealAsset',
    ];
}
