<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base;

use Yii;

/**
 * Callback [[@doctodo class_description:infinite\base\Callback]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Callback extends Object
{
    /**
     * @var [[@doctodo var_type:_id]] [[@doctodo var_description:_id]]
     */
    protected $_id;
    /**
     * @var [[@doctodo var_type:_callbacks]] [[@doctodo var_description:_callbacks]]
     */
    protected static $_callbacks = [];
    /**
     * @var [[@doctodo var_type:callback]] [[@doctodo var_description:callback]]
     */
    public $callback;
    /**
     * @var [[@doctodo var_type:params]] [[@doctodo var_description:params]]
     */
    public $params = [];

    /**
     * [[@doctodo method_description:call]].
     *
     * @throws \ [[@doctodo exception_description:\]]
     * @return [[@doctodo return_type:call]] [[@doctodo return_description:call]]
     *
     */
    public function call()
    {
        if (empty($this->callback) || !is_callable($this->callback)) {
            throw new \Exception("Callback was not callable!");

            return false;
        }
        $params = array_merge($this->params, func_get_args());

        return call_user_func_array($this->callback, $params);
    }

    /**
     * Get id.
     *
     * @return [[@doctodo return_type:getId]] [[@doctodo return_description:getId]]
     */
    public function getId()
    {
        if (!isset($this->_id)) {
            $this->_id = md5(uniqid(rand(), true));
        }

        return $this->_id;
    }

    /**
     * Get.
     *
     * @return [[@doctodo return_type:get]] [[@doctodo return_description:get]]
     */
    public static function get($callbackId)
    {
        if (isset(static::$_callbacks[$callbackId])) {
            return static::$_callbacks[$callbackId];
        }

        return false;
    }

    /**
     * Set.
     *
     * @return [[@doctodo return_type:set]] [[@doctodo return_description:set]]
     */
    public static function set($callbackParams)
    {
        $callback = new static();
        Yii::configure($callback, $callbackParams);
        static::$_callbacks[$callback->id] = $callback;

        return $callback->id;
    }
}
