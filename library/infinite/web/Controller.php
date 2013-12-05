<?php
/**
 * library/web/Controller.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\web;

use Yii;

class Controller extends \yii\web\Controller
{
	public $params = [];

	protected static $_response = [];


	/**
	 * @inheritdoc
	 */
	public function runAction($route, $params = [])
	{
		$result = parent::runAction($route, $params);
		if (is_null($result)) {
			return $this->response;
		}
		return $result;
	}

	public function getResponse() {
		$responseKey = self::className();
		if (!isset(self::$_response[$responseKey])) {
			self::$_response[$responseKey] = Yii::createObject(['class' => 'infinite\web\Response']);
		}
		self::$_response[$responseKey]->controller = $this;
		return self::$_response[$responseKey];
	}

	/**
	 * @inheritdoc
	 */
	public function render($view, $params = [])
	{
		return parent::render($view, array_merge($params, $this->params));
	}

	/**
	 * @inheritdoc
	 */
	public function renderPartial($view, $params = [])
	{
		return parent::renderPartial($view, array_merge($params, $this->params));
	}
}
