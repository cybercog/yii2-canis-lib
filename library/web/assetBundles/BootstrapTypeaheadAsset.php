<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\web\assetBundles;

/**
 * BootstrapTypeaheadAsset [[@doctodo class_description:teal\web\assetBundles\BootstrapTypeaheadAsset]].
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
