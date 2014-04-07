<?php
namespace infinite\web\browser;

use infinite\helpers\Html;
use infinite\base\exceptions\Exception;


class Item extends \infinite\base\Object
{
	public $type;
	public $id;
	public $label;

	public function package()
	{
		return [
			'type' => $this->type,
			'id' => $this->id,
			'label' => $this->label
		];
	}
}
?>