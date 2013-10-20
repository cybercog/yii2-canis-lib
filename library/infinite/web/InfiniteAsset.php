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
	public $css = ['css/site.css'];
	public $js = ['js/jquery/jquery.min.js', 'js/jquery-ui/jquery-ui-build.js', 'js/underscore/underscore-min.js'];
	public $depends = [];
}
