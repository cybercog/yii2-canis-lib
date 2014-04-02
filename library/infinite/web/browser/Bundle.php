<?php
namespace infinite\web\browser;

use Yii;
use infinite\helpers\Html;
use infinite\helpers\ArrayHelper;
use infinite\base\exceptions\Exception;
use yii\base\InvalidConfigException;

class Bundle extends \infinite\base\Object
{
	public $itemClass = 'infinite\\web\\browser\\Item';
	public $sortDirection = SORT_ASC;
	protected $_id; // never set, based on instructions
	protected $_instructions;
	protected $_type; // pivot: category list; item: items list
	protected $_typeOptions = [];
	protected $_items;
	protected $_sorted = false;
	protected $_handled = false;
	protected $_total;
	protected $_offset = 0;

	protected $_baseInstructions = ['task' => null];

	public function getId()
	{
		if (is_null($this->_id)) {
			$this->_id = md5(serialize($this->instructions));
		}
		return $this->_id;
	}

	public function getInstructions()
	{
		if (is_null($this->_instructions)) {
			throw new InvalidConfigException('Browser response bundle requires instructions');
		}
		return $this->_instructions;
	}

	public function setInstructions($instructions)
	{
		if (!is_null($this->_instructions)) {
			throw new Exception('Instructions for browser responses can only be set once.');
		}
		$this->_instructions = array_merge($this->_baseInstructions, $instructions);
	}

	public function addItem($item)
	{
		if (!isset($this->_items)) {
			$this->_items = [];
		}
		if (!is_array($item)) {
			$item = [];
		}
		if (!isset($item['class'])) {
			$item['class'] = $this->itemClass;
		}
		$item = Yii::createObject($item);
		$this->_items[$item->id] = $item;
		$this->_sorted = false;
		return $item;
	}

	public function package()
	{
		$package = [];
		$package['id'] = $this->id;
		$package['instructions'] = $this->instructions;
		$package['type'] = $this->type;
		$package['typeOptions'] = $this->typeOptions;
		$package['total'] = $this->total;
		$package['bundle'] = false;
		if (isset($this->_items)) {
			$package['bundle'] = ['offset' => $this->offset, 'size' => count($this->_items), 'items' => []];
			$this->ensureSorting();
			foreach ($this->_items as $item) {
				$package['bundle']['items'][$item->id] = $item->package();
			}
		}
		return $package;
	}

	public function ensureSorting()
	{
		if (!$this->_sorted) {
			ArrayHelper::multisort($this->_items, 'sortKey', $this->sortDirection);
		}
		$this->_sorted = true;
	}

	public function setTypeOptions($options)
	{
		$this->_typeOptions = array_merge($this->_typeOptions, $options);
	}

	public function getTypeOptions()
	{
		return $this->_typeOptions;
	}

	public function setType($type)
	{
		$acceptableTypes = ['pivot', 'item'];
		if (!in_array($type, $acceptableTypes)) {
			throw new InvalidConfigException('Browser response bundle must be one of the following types: '. implode(', ', $acceptableTypes));
		}
		$this->_type = $type;
	}

	public function getType()
	{
		if (is_null($this->_type)) {
			$this->type = 'item';
		}
		return $this->_type;
	}

	public function predictTotal()
	{
		return false;
	}

	public function getTotal()
	{
		if (is_null($this->_total)) {
			if (isset($this->_items)) {
				return count($this->_items);
			} else {
				return $this->predictTotal();
			}
		}
		return $this->_total;
	}

	public function setTotal($total)
	{
		$this->_total = $total;
	}


	public function getOffset()
	{
		return $this->_offset;
	}

	public function setOffset($offset)
	{
		$this->_offset = $offset;
	}

	public function getHandlers()
	{
		return [];
	}

	public function getHandler()
	{
		$handlers = $this->handlers;
		if (isset($this->instructions['handler'])
			&& isset($handlers[$this->instructions['handler']])) {
			return Yii::createObject([
				'class' => $handlers[$this->instructions['handler']],
				'bundle' => $this
			]);
		}
		return false;
	}

	public function handle()
	{
		if ($this->_handled) { return true; }
		$handler = $this->handler;
		if (!$handler) { return false; }
		$items = $handler->items;
		if ($items === false) { return false; }
		foreach ($items as $item) {
			$this->addItem($item);
		}
		$this->_handled = true;
	}
}
?>