<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base;

use infinite\base\exceptions\Exception;

/**
 * Module [@doctodo write class description for Module].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class Module extends \yii\base\Module
{
    use ObjectTrait;

    /**
     * @var __var__systemId_type__ __var__systemId_description__
     */
    protected $_systemId;

    /**
     * Get module type.
     */
    abstract public function getModuleType();

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
     * @throws Exception __exception_Exception_description__
     *
     * @return unknown
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
