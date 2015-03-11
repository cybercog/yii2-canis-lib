<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\base\collector;

interface CollectedObjectInterface
{
    public function getCollectorItem();
    public function setCollectorItem(Item $item);
    public function getCollectedObject(Item $item);
}
