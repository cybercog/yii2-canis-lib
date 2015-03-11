<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\web\assetBundles;

/**
 * BootstrapSelectAsset [[@doctodo class_description:teal\web\assetBundles\BootstrapSelectAsset]].
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
