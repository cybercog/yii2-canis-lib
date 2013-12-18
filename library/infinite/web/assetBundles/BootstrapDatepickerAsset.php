<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace infinite\web\assetBundles;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class BootstrapDatepickerAsset extends AssetBundle
{
    public $sourcePath = '@vendor/eternicode/bootstrap-datepicker';
    public $css = ['css/datepicker.css'];
    public $js = ['js/bootstrap-datepicker.js'];
    public $depends = ['yii\web\JqueryAsset', 'yii\bootstrap\BootstrapAsset'];

	public function init() {
		$this->publishOptions['beforeCopy'] = function($from, $to) {
			$acceptable = ['css', 'js'];
			return in_array(basename($from), $acceptable) || in_array(basename(dirname($from)), $acceptable);
		};
		parent::init();
	}
}
