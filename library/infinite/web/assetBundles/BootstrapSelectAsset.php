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
 */
class BootstrapSelectAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@vendor/bootstrap-select/bootstrap-select/dist';
    /**
     * @inheritdoc
     */
    public $css = [
        'css/bootstrap-select.min.css',
    ];
    /**
     * @inheritdoc
     */
    public $js = [
        'js/bootstrap-select.min.js',
    ];
}
