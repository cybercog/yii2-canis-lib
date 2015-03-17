<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\base;

use Yii;
use canis\base\exceptions\Exception;

/**
 * Module [[@doctodo class_description:canis\base\Module]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class Module extends \yii\base\Module
{
    use ObjectTrait;

    /**
     * @var [[@doctodo var_type:_systemId]] [[@doctodo var_description:_systemId]]
     */
    protected $_systemId;

    /**
     * Get module type.
     */
    abstract public function getModuleType();

    public function init()
    {
        parent::init();
        $this->always();
    }
    /**
     * Prepares object for serialization.
     *
     * @throws Exception when the module isn't the app
     * @return array keys to sleep
     *
     */
    public function __sleep()
    {
        $keys = array_keys((array) $this);
        // if ($this->module === Yii::$app) {
        //     throw new Exception(get_class($this->module));
        // }
        $this->module = null;

        return $keys;
    }

    /**
     * Actions to take on object wakeup.
     */
    public function __wakeup()
    {
        $this->module = Yii::$app;
        $this->always();
    }

    /**
     * Prepare the collected module on wakeup and init.
     *
     * @return bool ran successfully
     */
    public function always()
    {
        return true;
    }

    /**
     * Set system.
     *
     * @param unknown $value
     */
    public function setSystemId($value)
    {
        $this->_systemId = $value;
    }

    /**
     * Get system.
     *
     * @throws Exception [[@doctodo exception_description:Exception]]
     * @return unknown
     *
     */
    public function getSystemId()
    {
        if (!is_null($this->_systemId)) {
            return $this->_systemId;
        }
        preg_match('/' . ucfirst($this->moduleType) . '([A-Za-z]+)\\\Module/', get_class($this), $matches);
        if (!isset($matches[1])) {
            throw new Exception(get_class($this) . " is not set up correctly!");
        }

        return $this->_systemId = $matches[1];
    }
}
