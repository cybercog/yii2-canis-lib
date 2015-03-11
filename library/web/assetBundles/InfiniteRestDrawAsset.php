<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\web\assetBundles;

/**
 * TealRestDrawAsset [[@doctodo class_description:teal\web\assetBundles\TealRestDrawAsset]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 2.0
 */
class TealRestDrawAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@teal/assets/restDraw';
    /**
     * @inheritdoc
     */
    public $css = [
        'css/teal.restDraw.css',
    ];
    /**
     * @inheritdoc
     */
    public $js = [
        'js/teal.restDraw.js',
    ];
    /**
     * @inheritdoc
     */
    public $depends = [
        'teal\web\assetBundles\TealAsset',
    ];
}
