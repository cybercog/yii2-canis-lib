<?php
namespace infinite\base;

use \infinite\base\Component;

class CollectorItem extends \infinite\base\Object {
	protected $_name;
	protected $_owner;
	protected $_collector;
	protected $_settings;
	protected $_object;

	public function __construct($collector, $name, $itemComponent = null) {
		$this->_name = $name;
		$this->_collector = $collector;
		if (!is_null($itemComponent)) {
			$this->object = $itemComponent;
		}
	}

	public function getName() {
		return $this->_name;
	}

	public function hasObject() {
		return $this->_object !== null;
	}

	public function getObject() {
		return $this->_object;
	}

	public function setObject($object) {
		$this->_object = $object;
	}

	public function getCollector() {
		return $this->_collector;
	}
}
?>