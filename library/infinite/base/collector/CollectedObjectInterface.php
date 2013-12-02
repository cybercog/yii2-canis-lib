<?php
namespace infinite\base\collector;

interface CollectedObjectInterface
{
	public function getCollectorItem();
	public function setCollectorItem($item);
}