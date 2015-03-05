<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db;

/**
 * Tree [[@doctodo class_description:infinite\db\Tree]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Tree extends \infinite\base\Object
{
    /**
     * @var [[@doctodo var_type:object]] [[@doctodo var_description:object]]
     */
    public $object;
    /**
     * @var [[@doctodo var_type:children]] [[@doctodo var_description:children]]
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
