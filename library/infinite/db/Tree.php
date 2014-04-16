<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db;

class Tree extends \infinite\base\Object
{
    public $object;
    public $children;

    public function __construct($object, $children)
    {
        $this->object = $object;
        $this->children = $children;
    }
}
