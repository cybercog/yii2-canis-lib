<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\assetBundles;

/**
 * BootstrapTypeaheadAsset [[@doctodo class_description:infinite\web\assetBundles\BootstrapTypeaheadAsset]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class BootstrapTypeaheadAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@vendor/twitter/typeahead.js/dist';
    /**
     * @inheritdoc
     */
    public $css = [];
    /**
     * @inheritdoc
     */
    public $js = [
        'typeahead.bundle.min.js',
    ];
    /**
     * @inheritdoc
     */
    public $depends = ['yii\web\JqueryAsset', 'yii\bootstrap\BootstrapAsset'];
}
