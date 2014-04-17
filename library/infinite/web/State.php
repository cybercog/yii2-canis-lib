<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web;

use Yii;

/**
 * State [@doctodo write class description for State]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class State extends \infinite\base\Object
{
    const SESSION_STATE_KEY = '_s';
    /**
     * @var __var__temporaryState_type__ __var__temporaryState_description__
     */
    protected $_temporaryState = [];

    /**
     * Get
     * @param __param_key_type__ $key __param_key_description__
     * @param __param_default_type__ $default __param_default_description__ [optional]
     * @return __return_get_type__ __return_get_description__
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
     * Set
     * @param __param_key_type__ $key __param_key_description__
     * @param __param_value_type__ $value __param_value_description__
     * @return __return_set_type__ __return_set_description__
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
     * __method_isTemporary_description__
     * @param __param_key_type__ $key __param_key_description__
     * @return __return_isTemporary_type__ __return_isTemporary_description__
     */
    public function isTemporary($key)
    {
        if (!isset(Yii::$app->session)) { return true; }
        $parts = explode('.', $key);

        return substr(array_pop($parts), 0, 1) === '_';
    }
}
