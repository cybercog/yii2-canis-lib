<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\assetBundles;

/**
 * InfiniteBrowserAsset [@doctodo write class description for InfiniteBrowserAsset].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 2.0
 */
class InfiniteRestDrawAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@infinite/assets/restDraw';
    /**
     * @inheritdoc
     */
    public $css = [
        'css/infinite.restDraw.css',
    ];
    /**
     * @inheritdoc
     */
    public $js = [
        'js/infinite.restDraw.js',
    ];
    /**
     * @inheritdoc
     */
    public $depends = [
        'infinite\web\assetBundles\InfiniteAsset',
    ];
}
