<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\assetBundles;

/**
 * AjaxFormAsset [@doctodo write class description for AjaxFormAsset]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
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
