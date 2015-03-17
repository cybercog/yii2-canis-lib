<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\base\collector;

interface CollectedObjectInterface
{
    public function getCollectorItem();
    public function setCollectorItem(Item $item);
    public function getCollectedObject(Item $item);
}
