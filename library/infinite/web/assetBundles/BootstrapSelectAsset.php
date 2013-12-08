<?php
namespace infinite\web\assetBundles;

class BootstrapSelectAsset extends AssetBundle
{
	public $sourcePath = '@vendor/bootstrap-select/bootstrap-select';
	public $css = [
		'bootstrap-select.min.css',
	];
	public $js = [
		'bootstrap-select.min.js',
	];
}
