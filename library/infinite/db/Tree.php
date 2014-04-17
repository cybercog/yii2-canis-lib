<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db;

/**
 * Tree [@doctodo write class description for Tree]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Tree extends \infinite\base\Object
{
    /**
     * @var __var_object_type__ __var_object_description__
     */
    public $object;
    /**
     * @var __var_children_type__ __var_children_description__
     */
    public $children;

    /**
    * @inheritdoc
     */
    public function __construct($object, $children)
    {
        $this->object = $object;
        $this->children = $children;
    }
}
