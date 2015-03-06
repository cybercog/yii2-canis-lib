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
 * Bucket [[@doctodo class_description:infinite\base\collector\Bucket]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Bucket extends \infinite\base\Object implements IteratorAggregate, ArrayAccess
{
    /**
     * @var [[@doctodo var_type:_bucket]] [[@doctodo var_description:_bucket]]
     */
    protected $_bucket = [];
    /**
     * @var [[@doctodo var_type:_collector]] [[@doctodo var_description:_collector]]
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
     * [[@doctodo method_description:add]].
     *
     * @param [[@doctodo param_type:offset]] $offset [[@doctodo param_description:offset]]
     * @param infinite\base\collector\Item   $item   [[@doctodo param_description:item]]
     */
    public function add($offset, Item $item)
    {
        $this->_bucket[$offset] = $item;
    }

    /**
     * [[@doctodo method_description:toArray]].
     *
     * @return [[@doctodo return_type:toArray]] [[@doctodo return_description:toArray]]
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
