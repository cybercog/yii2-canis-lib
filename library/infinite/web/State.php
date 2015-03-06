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
 * State [[@doctodo class_description:infinite\web\State]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class State extends \infinite\base\Object
{
    const SESSION_STATE_KEY = '_s';
    /**
     * @var [[@doctodo var_type:_temporaryState]] [[@doctodo var_description:_temporaryState]]
     */
    protected $_temporaryState = [];

    /**
     * Get.
     *
     * @param [[@doctodo param_type:key]]     $key     [[@doctodo param_description:key]]
     * @param [[@doctodo param_type:default]] $default [[@doctodo param_description:default]] [optional]
     *
     * @return [[@doctodo return_type:get]] [[@doctodo return_description:get]]
     */
    public function get($key, $default = null)
    {
        if ($this->isTemporary($key)) {
            $state = $this->_temporaryState;
        } else {
            $state = Yii::$app->session[self::SESSION_STATE_KEY];
        }

        if (isset($state[$key])) {
            return $state[$key];
        }

        return $default;
    }

    /**
     * Set.
     *
     * @param [[@doctodo param_type:key]]   $key   [[@doctodo param_description:key]]
     * @param [[@doctodo param_type:value]] $value [[@doctodo param_description:value]]
     *
     * @return [[@doctodo return_type:set]] [[@doctodo return_description:set]]
     */
    public function set($key, $value)
    {
        if ($this->isTemporary($key)) {
            $this->_temporaryState[$key] = $value;
        } else {
            $state = Yii::$app->session[self::SESSION_STATE_KEY];
            if (empty($state)) {
                $state = [];
            }
            $state[$key] = $value;
            Yii::$app->session[self::SESSION_STATE_KEY] = $state;

            return true;
        }

        return false;
    }

    /**
     * [[@doctodo method_description:isTemporary]].
     *
     * @param [[@doctodo param_type:key]] $key [[@doctodo param_description:key]]
     *
     * @return [[@doctodo return_type:isTemporary]] [[@doctodo return_description:isTemporary]]
     */
    public function isTemporary($key)
    {
        if (!isset(Yii::$app->session)) {
            return true;
        }
        $parts = explode('.', $key);

        return substr(array_pop($parts), 0, 1) === '_';
    }
}
