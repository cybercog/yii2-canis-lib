<?php
/**
 * library/base/Engine.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\base;
use Yii;
use \yii\base\Application;

abstract class Engine extends \infinite\base\Object
{
    /**
     *
     */
    public function init()
    {
        Yii::$app->on(Application::EVENT_BEFORE_REQUEST, array($this, 'beforeRequest'));
    }

    public function beforeRequest()
    {
        return true;
    }
}
