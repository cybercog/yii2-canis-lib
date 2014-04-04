<?php
namespace infinite\web\browser;

use infinite\helpers\Html;
use infinite\base\exceptions\Exception;


class Item extends \infinite\base\Object
{
	public $type;
	public $id;
	public $label;
	protected $_sortKey;

	public function package()
	{
		return [
			'type' => $this->type,
			'id' => $this->id,
			'label' => $this->label
		];
	}

	public function getSortKey()
	{
		if (is_null($this->_sortKey)) {
			return implode('.', [$this->label, $this->id, md5(mt_rand(0, 1000000))]);
		}
		return $this->_sortKey;
	}

	public function setSortKey($key)
	{
		$this->_sortKey = $key;
	}
}
?>