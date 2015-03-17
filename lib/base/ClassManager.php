<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\base;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

/**
 * ClassManager [[@doctodo class_description:canis\base\ClassManager]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ClassManager extends Component implements IteratorAggregate, ArrayAccess
{
    /**
     * @var [[@doctodo var_type:_classes]] [[@doctodo var_description:_classes]]
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
     * [[@doctodo method_description:baseClasses]].
     *
     * @return [[@doctodo return_type:baseClasses]] [[@doctodo return_description:baseClasses]]
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
            'SearchTermResult' => 'canis\db\behaviors\SearchTermResult',
        ];
    }

    /**
     * Set classes.
     *
     * @param [[@doctodo param_type:classes]] $classes  [[@doctodo param_description:classes]]
     * @param boolean                         $override [[@doctodo param_description:override]] [optional]
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
     * @param mixed $offset the offset to check on
     *
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
     *
     * @throws \ [[@doctodo exception_description:\]]
     * @return mixed the element at the offset, null if no element is found at the offset
     *
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->_classes[$offset];
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
