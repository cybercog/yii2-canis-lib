<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base\collector;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

/**
 * Bucket [@doctodo write class description for Bucket].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Bucket extends \infinite\base\Object implements IteratorAggregate, ArrayAccess
{
    /**
     * @var __var__bucket_type__ __var__bucket_description__
     */
    protected $_bucket = [];
    /**
     * @var __var__collector_type__ __var__collector_description__
     */
    protected $_collector;

    /**
     * @inheritdoc
     */
    public function __construct($collector)
    {
        $this->_collector = $collector;
    }

    /**
     * __method_add_description__.
     *
     * @param __param_offset_type__        $offset __param_offset_description__
     * @param infinite\base\collector\Item $item   __param_item_description__
     */
    public function add($offset, Item $item)
    {
        $this->_bucket[$offset] = $item;
    }

    /**
     * __method_toArray_description__.
     *
     * @return __return_toArray_type__ __return_toArray_description__
     */
    public function toArray()
    {
        return $this->_bucket;
    }

    /**
     * Returns an iterator for traversing the attributes in the model.
     * This method is required by the interface IteratorAggregate.
     *
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
     *
     * @param mixed $offset the offset to check on
     *
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
     *
     * @param mixed $offset the offset to retrieve element.
     *
     * @return mixed the element at the offset, null if no element is found at the offset
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->_bucket[$offset];
        }

        return;
    }

    /**
     * Sets the element at the specified offset.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `$model[$offset] = $item;`.
     *
     * @param integer $offset the offset to set element
     * @param mixed   $item   the element value
     */
    public function offsetSet($offset, $item)
    {
        $this->_bucket[$offset] = $item;
    }

    /**
     * Sets the element value at the specified offset to null.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `unset($model[$offset])`.
     *
     * @param mixed $offset the offset to unset element
     */
    public function offsetUnset($offset)
    {
        unset($this->_bucket[$offset]);
    }
}
