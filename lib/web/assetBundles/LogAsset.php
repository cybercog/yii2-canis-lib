<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\web\assetBundles;

/**
 * LogAsset [[@doctodo class_description:teal\web\assetBundles\LogAsset]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 2.0
 */
class LogAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@teal/assets/log';

    // public $basePath = '@webroot';
    // public $baseUrl = '@web';
    /**
     * @inheritdoc
     */
    public $css = [
        'css/teal.log.css',
    ];
    /**
     * @inheritdoc
     */
    public $js = [
        'js/teal.log.js',
    ];
    /**
     * @inheritdoc
     */
    public $depends = [
        'teal\web\assetBundles\TealAsset',
    ];
}
