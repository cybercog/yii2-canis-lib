<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\web\assetBundles;

/**
 * AjaxFormAsset [[@doctodo class_description:canis\web\assetBundles\AjaxFormAsset]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class AjaxFormAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@vendor/malsup/form';
    /**
     * @inheritdoc
     */
    public $js = ['jquery.form.js'];
    /**
     * @inheritdoc
     */
    public $depends = ['yii\web\JqueryAsset'];
}
