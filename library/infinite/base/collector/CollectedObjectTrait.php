<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base\collector;

trait CollectedObjectTrait
{
    protected $_collectorItem;

    public function getCollectorItem()
    {
        return $this->_collectorItem;
    }

    public function setCollectorItem(Item $item)
    {
        $this->_collectorItem = $item;

        return $this;
    }

    public function getCollectedObject(Item $item)
    {
        $this->collectorItem = $item;

        return $this;
    }
}
