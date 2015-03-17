<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db;

/**
 * Tree [[@doctodo class_description:canis\db\Tree]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Tree extends \canis\base\Object
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
