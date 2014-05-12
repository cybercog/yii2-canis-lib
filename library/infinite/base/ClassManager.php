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
 */
class ClassManager extends Component implements IteratorAggregate, ArrayAccess
{
    /**
     * @var __var__classes_type__ __var__classes_description__
     */
    protected $_classes = [];
    /**
    * @inheritdoc
     */
    public function init()
    {
        $this->setClasses($this->baseClasses(), false);
    }

    /**
     * __method_baseClasses_description__
     * @return __return_baseClasses_type__ __return_baseClasses_description__
     */
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
            'RelationDependency' => 'app\\models\\RelationDependency',
            'User' => 'app\\models\\User',
            'Identity' => 'app\\models\\Identity',
            'IdentityProvider' => 'app\\models\\IdentityProvider',
            'SearchTermResult' => 'infinite\\db\\behaviors\\SearchTermResult',
        ];
    }

    /**
     * Set classes
     * @param __param_classes_type__ $classes __param_classes_description__
     * @param boolean $override __param_override_description__ [optional]
     */
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
     *
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
     *
     * @param mixed   $offset the offset to check on
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
     *
     * @param mixed $offset the offset to retrieve element.
     * @return mixed the element at the offset, null if no element is found at the offset
     * @throws \ __exception_\_description__
     * @throws \ __exception_\_description__
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
     *
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
     *
     * @param mixed $offset the offset to unset element
     */
    public function offsetUnset($offset)
    {
        unset($this->_classes[$offset]);
    }
}
