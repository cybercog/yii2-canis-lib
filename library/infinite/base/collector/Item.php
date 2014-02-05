<?php
namespace infinite\base\collector;

use Yii;
use infinite\base\exceptions\Exception;

class Item extends \infinite\base\Object {
	//public $name;
	protected $_owner;
	protected $_collector;
	protected $_settings;
	protected $_object;
	protected $_systemId;

	public function setSystemId($id)
	{
		$this->_systemId = $id;
	}

	public function getSystemId()
	{
		if (is_null($this->_systemId) && !is_null($this->object) && isset($this->object->systemId)) {
			$this->_systemId = $this->object->systemId;
		}
		return $this->_systemId;
	}

	public function setCollector($collector)
	{
		$this->_collector = $collector;
	}

	public function hasObject()
	{
		return $this->_object !== null;
	}

	public function getObject()
	{
		return $this->_object;
	}

	public function setObject($object)
	{
		if (is_array($object)) {
			$object = Yii::createObject($object);
		}
		if (!($object instanceof CollectedObjectInterface)) {
			throw new Exception("Bad object passed to collector!");
		}
		$this->_object = $object;
		$object->collectorItem = $this;
	}

	public function setOwner($owner) {
		$this->_owner = $owner;
	}

	public function getOwner() {
		return $this->_owner;
	}

	public function getCollector() {
		return $this->_collector;
	}
}
?>