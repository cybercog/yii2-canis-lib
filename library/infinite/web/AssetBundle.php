<?php
namespace infinite\web;

class AssetBundle extends \yii\web\AssetBundle
{
	public function init() {
		parent::init();
		if (defined('YII_ENV') && YII_ENV === 'dev') {
			$this->publishOptions['forceCopy'] = true;
		}
	}
}