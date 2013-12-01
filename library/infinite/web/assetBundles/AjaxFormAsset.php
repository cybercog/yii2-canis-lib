<?php
namespace infinite\web\assetBundles;

class AjaxFormAsset extends AssetBundle
{
    public $sourcePath = '@vendor/malsup/form';
    public $js = ['jquery.form.js'];
    public $depends = ['yii\web\JqueryAsset'];
}
