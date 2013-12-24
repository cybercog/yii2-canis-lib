<?php
namespace infinite\web\bootstrap;

use infinite\helpers\Html;

/**
 * SubNavBar renders a sub-navbar HTML component.
 *
 * Any content enclosed between the [[begin()]] and [[end()]] calls of NavBar
 * is treated as the content of the navbar. You may use widgets such as [[Nav]]
 * or [[\yii\widgets\Menu]] to build up such content. For example,
 *
 * @see http://twitter.github.io/bootstrap/components.html#navbar
 * @author Antonio Ramirez <amigo.cobos@gmail.com>; Jacob Morrison <jacob@infinitecascade.org>
 */

class SubNavBar extends \yii\bootstrap\NavBar {
	/**
	 * {@inheritdoc}
	 */
	public function init()
	{
		Html::addCssClass($this->options, 'sub-navbar');
		parent::init();
	}

}