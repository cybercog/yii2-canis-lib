<?php
namespace infinite\base;
trait ComponentTrait {
	public function hasBehavior($name)
	{
		return $this->getBehavior($name) !== null;
	}
}
?>