<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base;

/**
 * Module [@doctodo write class description for Module]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
abstract class Module extends \yii\base\Module
{
    use ObjectTrait;

    /**
     * @var __var__systemId_type__ __var__systemId_description__
     */
    protected $_systemId;

    /**
     * __method_getModuleType_description__
     */
    abstract public function getModuleType();

    /**
     * __method_setSystemId_description__
     * @param unknown $value
     */
    public function setSystemId($value)
    {
        $this->_systemId = $value;
    }

    /**
     * __method_getSystemId_description__
     * @return unknown
     * @throws Exception __exception_Exception_description__
     */
    public function getSystemId()
    {
        if (!is_null($this->_systemId)) {
            return $this->_systemId;
        }
        preg_match('/'.ucfirst($this->moduleType).'([A-Za-z]+)\\\Module/', get_class($this), $matches);
        if (!isset($matches[1])) {
            throw new Exception(get_class($this). " is not set up correctly!");
        }

        return $this->_systemId = $matches[1];
    }
}
