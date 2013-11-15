<?php
namespace infinite\web;

class FontAwesomeAsset extends AssetBundle
{
	public $sourcePath = '@vendor/fortawesome/font-awesome';
	public $css = [
		'css/font-awesome.min.css',
	];

	public function init() {
		$this->publishOptions['beforeCopy'] = function($from, $to) {
			$acceptable = ['css', 'fonts'];
			return in_array(basename($from), $acceptable) || in_array(basename(dirname($from)), $acceptable);
		};
		parent::init();
	}
}
