<?php
namespace infinite\web\browser;

use infinite\helpers\Html;
use infinite\base\exceptions\Exception;


class Item extends \infinite\base\Object
{
	public $type;
	public $id;
	public $descriptor;
	public $subdescriptor;
	public $isSelectable = false;
	public $hasChildren = false;

	public function package()
	{
		return [
			'type' => $this->type,
			'id' => $this->id,
			'descriptor' => $this->descriptor,
			'subdescriptor' => $this->subdescriptor,
			'hasChildren' => $this->hasChildren,
			'isSelectable' => $this->isSelectable
		];
	}
}
?>