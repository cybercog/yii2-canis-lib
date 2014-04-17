<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\assetBundles;

/**
 * BootstrapSelectAsset [@doctodo write class description for BootstrapSelectAsset]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class BootstrapSelectAsset extends AssetBundle
{
    public $sourcePath = '@vendor/bootstrap-select/bootstrap-select';
    public $css = [
        'bootstrap-select.min.css',
    ];
    public $js = [
        'bootstrap-select.min.js',
    ];
}
