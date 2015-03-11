<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\base;

use Yii;

/**
 * Callback [[@doctodo class_description:teal\base\Callback]].
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
     * @param [[@doctodo param_type:callbackId]] $callbackId [[@doctodo param_description:callbackId]]
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
     * @param [[@doctodo param_type:callbackParams]] $callbackParams [[@doctodo param_description:callbackParams]]
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
