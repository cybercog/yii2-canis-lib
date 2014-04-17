<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\assetBundles;

/**
 * UnderscoreAsset [@doctodo write class description for UnderscoreAsset]
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class UnderscoreAsset extends AssetBundle
{
    public $sourcePath = '@vendor/components/underscore';
    public $css = [];
    public $js = ['underscore-min.js'];
    public $depends = [];
}
