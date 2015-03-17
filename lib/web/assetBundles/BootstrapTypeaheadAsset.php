<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\web\assetBundles;

/**
 * BootstrapTypeaheadAsset [[@doctodo class_description:canis\web\assetBundles\BootstrapTypeaheadAsset]].
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
