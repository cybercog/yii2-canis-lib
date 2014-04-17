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
    protected $_temporaryState = [];

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

    public function isTemporary($key)
    {
        if (!isset(Yii::$app->session)) { return true; }
        $parts = explode('.', $key);

        return substr(array_pop($parts), 0, 1) === '_';
    }
}
