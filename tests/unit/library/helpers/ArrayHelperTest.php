<?php

namespace tealunit\library\helpers;

use teal\helpers\ArrayHelper;
use tealunit\TestCase;

class ArrayHelperTest extends TestCase
{
    public function testMap()
    {
        $objects = [];
        $o1 = new \tealunit\library\helpers\testHelpers\DummyObject();
        $o1->id = 'a';
        $o1->name = 'A Test';
        $objects[] = $o1;

        $o2 = new \tealunit\library\helpers\testHelpers\DummyObject();
        $o2->id = 'b';
        $o2->name = 'B Test';
        $objects[] = $o2;

        $set = ArrayHelper::map($objects, 'id');
        $this->assertEquals(2, count($set));
        $this->assertTrue(isset($set['a']));
        $this->assertTrue(isset($set['b']));

        $this->assertTrue($set['a'] === $o1);
        $this->assertTrue($set['b'] === $o2);
    }
}
