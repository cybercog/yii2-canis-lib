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
**/
class Tree extends \infinite\base\Object
{
    public $object;
    public $children;

    /**
    * @inheritdoc
    **/
    public function __construct($object, $children)
    {
        $this->object = $object;
        $this->children = $children;
    }
}
