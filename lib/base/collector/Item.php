<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\base\collector;

use canis\base\exceptions\Exception;
use Yii;

/**
 * Item [[@doctodo class_description:canis\base\collector\Item]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Item extends \canis\base\Object
{
    //public $name;
    /**
     * @var [[@doctodo var_type:_owner]] [[@doctodo var_description:_owner]]
     */
    protected $_owner;
    /**
     * @var [[@doctodo var_type:_collector]] [[@doctodo var_description:_collector]]
     */
    protected $_collector;
    /**
     * @var [[@doctodo var_type:_settings]] [[@doctodo var_description:_settings]]
     */
    protected $_settings;
    /**
     * @var [[@doctodo var_type:_object]] [[@doctodo var_description:_object]]
     */
    protected $_object;
    /**
     * @var [[@doctodo var_type:_systemId]] [[@doctodo var_description:_systemId]]
     */
    protected $_systemId;

    /**
     * Set system.
     *
     * @param [[@doctodo param_type:id]] $id [[@doctodo param_description:id]]
     */
    public function setSystemId($id)
    {
        $this->_systemId = $id;
    }

    /**
     * Get system.
     *
     * @return [[@doctodo return_type:getSystemId]] [[@doctodo return_description:getSystemId]]
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
     *
     * @param [[@doctodo param_type:collector]] $collector [[@doctodo param_description:collector]]
     */
    public function setCollector($collector)
    {
        $this->_collector = $collector;
    }

    /**
     * [[@doctodo method_description:hasObject]].
     *
     * @return [[@doctodo return_type:hasObject]] [[@doctodo return_description:hasObject]]
     */
    public function hasObject()
    {
        return $this->_object !== null;
    }

    /**
     * Get object.
     *
     * @return [[@doctodo return_type:getObject]] [[@doctodo return_description:getObject]]
     */
    public function getObject()
    {
        return $this->_object;
    }

    /**
     * Set object.
     *
     * @param [[@doctodo param_type:object]] $object [[@doctodo param_description:object]]
     *
     * @throws Exception [[@doctodo exception_description:Exception]]
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
     *
     * @param [[@doctodo param_type:owner]] $owner [[@doctodo param_description:owner]]
     */
    public function setOwner($owner)
    {
        $this->_owner = $owner;
    }

    /**
     * Get owner.
     *
     * @return [[@doctodo return_type:getOwner]] [[@doctodo return_description:getOwner]]
     */
    public function getOwner()
    {
        return $this->_owner;
    }

    /**
     * Get collector.
     *
     * @return [[@doctodo return_type:getCollector]] [[@doctodo return_description:getCollector]]
     */
    public function getCollector()
    {
        return $this->_collector;
    }
}
