<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\web\assetBundles;

/**
 * TealListerAsset [[@doctodo class_description:teal\web\assetBundles\TealListerAsset]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 2.0
 */
class TealListerAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@teal/assets/lister';
    /**
     * @inheritdoc
     */
    public $css = ['css/teal.lister.css'];
    /**
     * @inheritdoc
     */
    public $js = [
        'js/teal.lister.js',
    ];
    /**
     * @inheritdoc
     */
    public $depends = [
        'teal\web\assetBundles\TealAsset',
    ];
}
