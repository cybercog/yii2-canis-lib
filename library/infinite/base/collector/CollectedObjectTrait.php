<?php
namespace infinite\base\collector;

trait CollectedObjectTrait
{
	protected $_collectorItem;

	public function getCollectorItem() {
		return $this->_collectorItem;
	}

	public function setCollectorItem($item) {
		$this->_collectorItem = $item;
		return $this;
	}
}