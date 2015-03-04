<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base;

use Yii;

class Callback extends Object
{
    protected $_id;
    protected static $_callbacks = [];
    public $callback;
    public $params = [];

    public function call()
    {
        if (empty($this->callback) || !is_callable($this->callback)) {
            throw new \Exception("Callback was not callable!");

            return false;
        }
        $params = array_merge($this->params, func_get_args());

        return call_user_func_array($this->callback, $params);
    }

    public function getId()
    {
        if (!isset($this->_id)) {
            $this->_id = md5(uniqid(rand(), true));
        }

        return $this->_id;
    }

    public static function get($callbackId)
    {
        if (isset(static::$_callbacks[$callbackId])) {
            return static::$_callbacks[$callbackId];
        }

        return false;
    }

    public static function set($callbackParams)
    {
        $callback = new static();
        Yii::configure($callback, $callbackParams);
        static::$_callbacks[$callback->id] = $callback;

        return $callback->id;
    }
}
