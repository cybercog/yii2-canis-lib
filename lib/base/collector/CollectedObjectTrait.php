<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\base\collector;

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
