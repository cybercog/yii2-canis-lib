<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\web\assetBundles;

/**
 * BootstrapSelectAsset [[@doctodo class_description:canis\web\assetBundles\BootstrapSelectAsset]].
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
