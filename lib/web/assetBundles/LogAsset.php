<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\web\assetBundles;

/**
 * LogAsset [[@doctodo class_description:canis\web\assetBundles\LogAsset]].
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
    public $sourcePath = '@canis/assets/log';

    // public $basePath = '@webroot';
    // public $baseUrl = '@web';
    /**
     * @inheritdoc
     */
    public $css = [
        'css/canis.log.css',
    ];
    /**
     * @inheritdoc
     */
    public $js = [
        'js/canis.log.js',
    ];
    /**
     * @inheritdoc
     */
    public $depends = [
        'canis\web\assetBundles\CanisAsset',
    ];
}
