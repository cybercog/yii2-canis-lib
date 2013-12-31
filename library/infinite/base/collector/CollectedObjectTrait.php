<?php
namespace infinite\base\collector;

trait CollectedObjectTrait
{
	protected $_collectorItem;

	public function getCollectorItem() {
		return $this->_collectorItem;
	}

	public function setCollectorItem(Item $item) {
		$this->_collectorItem = $item;
		return $this;
	}

	public function getCollectedObject(Item $item) {
		$this->collectorItem = $item;
		return $this;
	}
}