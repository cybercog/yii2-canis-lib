<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base\collector;

use infinite\base\exceptions\Exception;
use Yii;

/**
 * Item [@doctodo write class description for Item].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Item extends \infinite\base\Object
{
    //public $name;
    /**
     */
    protected $_owner;
    /**
     */
    protected $_collector;
    /**
     */
    protected $_settings;
    /**
     */
    protected $_object;
    /**
     */
    protected $_systemId;

    /**
     * Set system.
     */
    public function setSystemId($id)
    {
        $this->_systemId = $id;
    }

    /**
     * Get system.
     */
    public function getSystemId()
    {
        if (is_null($this->_systemId) && !is_null($this->object) && isset($this->object->systemId)) {
            $this->_systemId = $this->object->systemId;
        }

        return $this->_systemId;
    }

    /**
     * Set collector.
     */
    public function setCollector($collector)
    {
        $this->_collector = $collector;
    }

    /**
     *
     */
    public function hasObject()
    {
        return $this->_object !== null;
    }

    /**
     * Get object.
     */
    public function getObject()
    {
        return $this->_object;
    }

    /**
     * Set object.
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
     * Set owner.
     */
    public function setOwner($owner)
    {
        $this->_owner = $owner;
    }

    /**
     * Get owner.
     */
    public function getOwner()
    {
        return $this->_owner;
    }

    /**
     * Get collector.
     */
    public function getCollector()
    {
        return $this->_collector;
    }
}
