<?php
namespace infinite\base\collector;

use Yii;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

use \yii\base\Arrayable;

class Component extends \infinite\base\Component  implements IteratorAggregate, ArrayAccess, Arrayable 
{
	const EVENT_AFTER_LOAD = 'afterLoad';

	protected $_collectors = [];

	public function init() {
		parent::init();
	}

	public function areReady() {
		Yii::beginProfile(__CLASS__.'::'.__FUNCTION__);
		foreach ($this->_collectors as $collector) {
			if (!$collector->isReady()) {
				return false;
			}
		}
		Yii::endProfile(__CLASS__.'::'.__FUNCTION__);
		return true;
	}

	public function initialize() {
		foreach ($this->_collectors as $collector) {
			if (!$collector->initialize()) {
				return false;
			}
		}
		return true;
	}

	public function getCollectors() {
		return $this->_collectors;
	}

	public function setCollectors($collectors) {
		Yii::beginProfile(__CLASS__.'::'.__FUNCTION__);
		foreach ($collectors as $id => $collector) {
			$this->internalRegisterCollector($id, $collector);
		}
		
		Yii::beginProfile(__CLASS__.'::'.__FUNCTION__.':afterLoad');
		$this->trigger(self::EVENT_AFTER_LOAD);
		Yii::endProfile(__CLASS__.'::'.__FUNCTION__.':afterLoad');

		Yii::endProfile(__CLASS__.'::'.__FUNCTION__);
	}

	public function onAfterLoad($action) {
		return $this->on(self::EVENT_AFTER_LOAD, $action);
	}

	protected function internalRegisterCollector($id, $collector) {
		if (is_array($collector)) {
			$collector = Yii::createObject($collector);
		}
		$this->_collectors[$id] = $collector;
		return $collector;
	}

	public function toArray() {
		return $this->_collectors;
	}

	/**
	 * Returns an iterator for traversing the attributes in the model.
	 * This method is required by the interface IteratorAggregate.
	 * @return ArrayIterator an iterator for traversing the items in the list.
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->_collectors);
	}

	/**
	 * Returns whether there is an element at the specified offset.
	 * This method is required by the SPL interface `ArrayAccess`.
	 * It is implicitly called when you use something like `isset($model[$offset])`.
	 * @param mixed $offset the offset to check on
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->_collectors);
	}

	/**
	 * Returns the element at the specified offset.
	 * This method is required by the SPL interface `ArrayAccess`.
	 * It is implicitly called when you use something like `$value = $model[$offset];`.
	 * @param mixed $offset the offset to retrieve element.
	 * @return mixed the element at the offset, null if no element is found at the offset
	 */
	public function offsetGet($offset)
	{
		if ($this->offsetExists($offset)) {
			return $this->_collectors[$offset];
		}
		return null;
	}

	/**
	 * Sets the element at the specified offset.
	 * This method is required by the SPL interface `ArrayAccess`.
	 * It is implicitly called when you use something like `$model[$offset] = $item;`.
	 * @param integer $offset the offset to set element
	 * @param mixed $item the element value
	 */
	public function offsetSet($offset, $item)
	{
		$this->_collectors[$offset] = $item;
	}

	/**
	 * Sets the element value at the specified offset to null.
	 * This method is required by the SPL interface `ArrayAccess`.
	 * It is implicitly called when you use something like `unset($model[$offset])`.
	 * @param mixed $offset the offset to unset element
	 */
	public function offsetUnset($offset)
	{
		unset($this->_collectors[$offset]);
	}
	
}
?>