<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

/**
 * ClassManager [@doctodo write class description for ClassManager]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class ClassManager extends Component implements IteratorAggregate, ArrayAccess
{
    protected $_classes = [];
    /**
    * @inheritdoc
    **/
    public function init()
    {
        $this->setClasses($this->baseClasses(), false);
    }

    public function baseClasses()
    {
        return [
            'Aca' => 'app\\models\\Aca',
            'Acl' => 'app\\models\\Acl',
            'AclRole' => 'app\\models\\AclRole',
            'Audit' => 'app\\models\\Audit',
            'Role' => 'app\\models\\Role',
            'Group' => 'app\\models\\Group',
            'Registry' => 'app\\models\\Registry',
            'Relation' => 'app\\models\\Relation',
            'User' => 'app\\models\\User',
            'SearchTermResult' => 'infinite\\db\\behaviors\\SearchTermResult',
        ];
    }

    public function setClasses($classes, $override = true)
    {
        foreach ($classes as $key => $class) {
            if ($override || !isset($this->_classes[$key])) {
                $this->_classes[$key] = $class;
            }
        }
    }

    /**
     * Returns an iterator for traversing the attributes in the model.
     * This method is required by the interface IteratorAggregate.
     * @return ArrayIterator an iterator for traversing the items in the list.
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_classes);
    }

    /**
     * Returns whether there is an element at the specified offset.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `isset($model[$offset])`.
     * @param  mixed   $offset the offset to check on
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->_classes);
    }

    /**
     * Returns the element at the specified offset.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `$value = $model[$offset];`.
     * @param  mixed $offset the offset to retrieve element.
     * @return mixed the element at the offset, null if no element is found at the offset
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->_classes[$offset];
        }
        \d($this->_classes);
        \d($offset);
        throw new \Exception("Looking for missing class '{$offset}'");

        return null;
    }

    /**
     * Sets the element at the specified offset.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `$model[$offset] = $item;`.
     * @param integer $offset the offset to set element
     * @param mixed   $item   the element value
     */
    public function offsetSet($offset, $item)
    {
        $this->_classes[$offset] = $item;
    }

    /**
     * Sets the element value at the specified offset to null.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `unset($model[$offset])`.
     * @param mixed $offset the offset to unset element
     */
    public function offsetUnset($offset)
    {
        unset($this->_classes[$offset]);
    }
}
