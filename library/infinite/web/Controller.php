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
 * Controller [[@doctodo class_description:infinite\web\Controller]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Controller extends \yii\web\Controller
{
    /**
     * @var [[@doctodo var_type:params]] [[@doctodo var_description:params]]
     */
    public $params = [];

    /**
     * @var [[@doctodo var_type:_response]] [[@doctodo var_description:_response]]
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
     * [[@doctodo method_description:beforeActionResponse]].
     *
     * @param [[@doctodo param_type:actionEvent]] $actionEvent [[@doctodo param_description:actionEvent]]
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
