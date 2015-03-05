<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\assetBundles;

use yii\web\AssetBundle;

/**
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
    public $sourcePath = '@infinite/assets/log';

    // public $basePath = '@webroot';
    // public $baseUrl = '@web';
    /**
     * @inheritdoc
     */
    public $css = [
        'css/infinite.log.css',
    ];
    /**
     * @inheritdoc
     */
    public $js = [
        'js/infinite.log.js',
    ];
    /**
     * @inheritdoc
     */
    public $depends = [
        'infinite\web\assetBundles\InfiniteAsset',
    ];
}
