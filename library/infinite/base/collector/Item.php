<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base\collector;

use Yii;
use infinite\base\exceptions\Exception;

/**
 * Item [@doctodo write class description for Item]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class Item extends \infinite\base\Object
{
    //public $name;
    /**
     * @var __var__owner_type__ __var__owner_description__
     */
    protected $_owner;
    /**
     * @var __var__collector_type__ __var__collector_description__
     */
    protected $_collector;
    /**
     * @var __var__settings_type__ __var__settings_description__
     */
    protected $_settings;
    /**
     * @var __var__object_type__ __var__object_description__
     */
    protected $_object;
    /**
     * @var __var__systemId_type__ __var__systemId_description__
     */
    protected $_systemId;

    /**
     * Set system
     * @param __param_id_type__ $id __param_id_description__
     */
    public function setSystemId($id)
    {
        $this->_systemId = $id;
    }

    /**
     * Get system
     * @return __return_getSystemId_type__ __return_getSystemId_description__
     */
    public function getSystemId()
    {
        if (is_null($this->_systemId) && !is_null($this->object) && isset($this->object->systemId)) {
            $this->_systemId = $this->object->systemId;
        }

        return $this->_systemId;
    }

    /**
     * Set collector
     * @param __param_collector_type__ $collector __param_collector_description__
     */
    public function setCollector($collector)
    {
        $this->_collector = $collector;
    }

    /**
     * __method_hasObject_description__
     * @return __return_hasObject_type__ __return_hasObject_description__
     */
    public function hasObject()
    {
        return $this->_object !== null;
    }

    /**
     * Get object
     * @return __return_getObject_type__ __return_getObject_description__
     */
    public function getObject()
    {
        return $this->_object;
    }

    /**
     * Set object
     * @param __param_object_type__ $object __param_object_description__
     * @throws Exception __exception_Exception_description__
     */
    public function setObject($object)
    {
        if (is_array($object)) {
            $object['collectorItem'] = $this;
            $object = Yii::createObject($object);
        }
        if (!($object instanceof CollectedObjectInterface)) {
            throw new Exception("Bad object passed to collector!");
        }
        $this->_object = $object;
        $object->collectorItem = $this;
    }

    /**
     * Set owner
     * @param __param_owner_type__ $owner __param_owner_description__
     */
    public function setOwner($owner)
    {
        $this->_owner = $owner;
    }

    /**
     * Get owner
     * @return __return_getOwner_type__ __return_getOwner_description__
     */
    public function getOwner()
    {
        return $this->_owner;
    }

    /**
     * Get collector
     * @return __return_getCollector_type__ __return_getCollector_description__
     */
    public function getCollector()
    {
        return $this->_collector;
    }
}
