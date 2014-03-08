<?php
namespace infinite\base\collector;

use Yii;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

use infinite\base\exceptions\Exception;

use yii\base\Arrayable;
use yii\base\Event;

class Component extends \infinite\base\Component implements IteratorAggregate, ArrayAccess 
{
	const EVENT_AFTER_LOAD = 'afterLoad';
	const EVENT_AFTER_INIT = 'afterInit';

	protected $_collectors = [];
	protected $_init_collectors = [];
	protected $_loaded = false;

	public function init() {
		Yii::$app->on(\yii\base\Application::EVENT_BEFORE_REQUEST, [$this, 'beforeRequest']);
		parent::init();
	}

	public function beforeRequest($event) {
		if (empty($this->_init_collectors)) { return; }
		// load
		$this->load();
	}

	public function load() {
		if (!$this->_loaded) {
			Yii::beginProfile(__CLASS__.'::'.__FUNCTION__);
			foreach ($this->_init_collectors as $id => $collector) {
				$this->internalRegisterCollector($id, $collector);
			}
			$this->_init_collectors = null;

			// initialize
			$this->trigger(self::EVENT_AFTER_LOAD);

			// final round
			$this->trigger(self::EVENT_AFTER_INIT);
			Yii::endProfile(__CLASS__.'::'.__FUNCTION__);
			$this->_loaded = true;
		}
	}

	public function areReady() {
		$this->load();
		Yii::beginProfile(__CLASS__.'::'.__FUNCTION__);
		foreach ($this->_collectors as $collector) {
			if (!is_object($collector)) { continue; }
			Yii::beginProfile(__CLASS__.'::'.__FUNCTION__ .'::'.$collector->systemId);
			if (!$collector->isReady()) {
				Yii::endProfile(__CLASS__.'::'.__FUNCTION__ .'::'.$collector->systemId);
				Yii::endProfile(__CLASS__.'::'.__FUNCTION__);
				return false;
			}
			Yii::endProfile(__CLASS__.'::'.__FUNCTION__ .'::'.$collector->systemId);
		}
		Yii::endProfile(__CLASS__.'::'.__FUNCTION__);
		return true;
	}

	public function initialize() {
		foreach ($this->_collectors as $collector) {
			if (!is_object($collector)) { continue; }
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
		$this->_init_collectors = $collectors;
	}

	public function onAfterLoad($action) {
		return $this->on(self::EVENT_AFTER_LOAD, $action);
	}

	public function onAfterInit($action) {
		return $this->on(self::EVENT_AFTER_INIT, $action);
	}

	protected function internalRegisterCollector($id, $collector) {
		Yii::beginProfile(__CLASS__.'::'.__FUNCTION__.'::'.$id);
		if (is_array($collector) && empty($collector['lazyLoad'])) {
			$collector = Yii::createObject($collector);
			$collector->systemId = $id;
		}
		$this->_collectors[$id] = $collector;
		Yii::endProfile(__CLASS__.'::'.__FUNCTION__.'::'.$id);
		return $collector;
	}

	public function toArray()
	{
		return $this->_collectors;
	}

	public function getSleepingCount()
	{
		return count($this->sleeping());
	}


	public function sleeping()
	{
		$s = [];
		foreach ($this->_collectors as $c) {
			if (is_array($c)) {
				$s[] = $c;
			}
		}
		return $s;
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
			if (is_array($this->_collectors[$offset])) {
				$this->_collectors[$offset] = Yii::createObject($this->_collectors[$offset]);
				$this->_collectors[$offset]->systemId = $offset;
				$this->_collectors[$offset]->beforeRequest(new Event);
			}
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