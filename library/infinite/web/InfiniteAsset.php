<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace infinite\web;

use yii\web\AssetBundle;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class InfiniteAsset extends AssetBundle
{
	public $sourcePath = '@infinite/assets';
	public $css = ['css/site.css', 'css/infinite.bootstrap.css'];
	public $js = ['js/jquery/jquery.js', 'js/jquery-ui/ui/jquery-ui.js', 'js/underscore/underscore-min.js', 'js/infinite.bootstrap.js'];
	public $depends = ['yii\bootstrap\BootstrapAsset'];
}
