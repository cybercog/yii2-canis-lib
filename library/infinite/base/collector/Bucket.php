<?php
namespace infinite\base\collector;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use \yii\base\Arrayable;

class Bucket extends \infinite\base\Object implements IteratorAggregate, ArrayAccess, Arrayable {
	protected $_bucket = [];
	protected $_collector;

	public function __construct($collector) {
		$this->_collector = $collector;
	}

	public function add($offset, Item $item) {
		$this->_bucket[$offset] = $item;
	}

	public function toArray() {
		return $this->_bucket;
	}

	/**
	 * Returns an iterator for traversing the attributes in the model.
	 * This method is required by the interface IteratorAggregate.
	 * @return ArrayIterator an iterator for traversing the items in the list.
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->_bucket);
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
		return array_key_exists($offset, $this->_bucket);
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
			return $this->_bucket[$offset];
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
		$this->_bucket[$offset] = $item;
	}

	/**
	 * Sets the element value at the specified offset to null.
	 * This method is required by the SPL interface `ArrayAccess`.
	 * It is implicitly called when you use something like `unset($model[$offset])`.
	 * @param mixed $offset the offset to unset element
	 */
	public function offsetUnset($offset)
	{
		unset($this->_bucket[$offset]);
	}
}
?>