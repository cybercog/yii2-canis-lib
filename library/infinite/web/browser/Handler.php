<?php
namespace infinite\web\browser;

abstract class Handler extends \infinite\base\Object
{
	public $bundle;
	abstract public function getTotal();
	abstract public function getItems();

	public function getInstructions()
	{
		if (!isset($this->bundle)) {
			return false;
		}
		return $this->bundle->instructions;
	}
}
?>