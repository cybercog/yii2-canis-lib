<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\web\assetBundles;

/**
 * AjaxFormAsset [[@doctodo class_description:teal\web\assetBundles\AjaxFormAsset]].
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
