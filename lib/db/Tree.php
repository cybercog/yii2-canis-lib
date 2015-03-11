<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db;

/**
 * Tree [[@doctodo class_description:teal\db\Tree]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Tree extends \teal\base\Object
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
