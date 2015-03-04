<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web;

use Yii;

/**
 * Controller [@doctodo write class description for Controller].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Controller extends \yii\web\Controller
{
    /**
     */
    public $params = [];

    /**
     */
    protected static $_response = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->on(self::EVENT_BEFORE_ACTION, [$this, 'beforeActionResponse']);
    }

    /**
     *
     */
    public function beforeActionResponse($actionEvent)
    {
        Yii::$app->response->action = $actionEvent->action;
        Yii::$app->response->controller = $this;
    }

    // public function getResponse() {
    // 	$responseKey = self::className();
    // 	if (!isset(self::$_response[$responseKey])) {
    // 		self::$_response[$responseKey] = Yii::createObject(['class' => 'infinite\web\Response']);
    // 	}
    // 	self::$_response[$responseKey]->controller = $this;
    // 	return self::$_response[$responseKey];
    // }

    /**
     * @inheritdoc
     */
    public function render($view, $params = [])
    {
        Yii::trace('Called render: ' . $view);

        return parent::render($view, array_merge($params, $this->params));
    }

    /**
     * @inheritdoc
     */
    public function renderPartial($view, $params = [])
    {
        Yii::trace('Called renderPartial: ' . $view);

        return parent::renderPartial($view, array_merge($params, $this->params));
    }
}
