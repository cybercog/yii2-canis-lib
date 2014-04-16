<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\assetBundles;

class AjaxFormAsset extends AssetBundle
{
    public $sourcePath = '@vendor/malsup/form';
    public $js = ['jquery.form.js'];
    public $depends = ['yii\web\JqueryAsset'];
}
