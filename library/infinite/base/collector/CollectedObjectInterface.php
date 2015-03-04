<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base\collector;

interface CollectedObjectInterface
{
    public function getCollectorItem();
    public function setCollectorItem(Item $item);
    public function getCollectedObject(Item $item);
}
