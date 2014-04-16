<?php
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
