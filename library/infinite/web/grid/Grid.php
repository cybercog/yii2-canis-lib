<?php
namespace infinite\web\grid;

use Yii;

class Grid extends \infinite\base\Object {
	//public $fillPreviousRows = true;
	public $rowClass = 'infinite\web\grid\Row';

	protected $_prepended = [];
	protected $_appended = [];
	protected $_rows = [];
	protected $_currentRow;

	public function output() {
		echo $this->generate();
	}

	public function generate() {
		$items = [];
		foreach ($this->_prepended as $item) {
			$items[] = $item->generate();
		}
		foreach ($this->_rows as $row) {
			$items[] = $row->generate();
		}
		foreach ($this->_appended as $item) {
			$items[] = $item->generate();
		}
		return implode('', $items);
	}

	public function prepend($item) {
		$this->_prepended[] = $item;
	}

	public function append($item) {
		$this->_appended[] = $item;
	}

	public function addRow($item) {
		if (is_array($item)) {
			$item = Yii::createObject(['class' => $this->rowClass, 'cells' => $item]);;
		}
		$this->_rows[] = $item;
		$this->_currentRow = null;
	}

	public function addRows($items) {
		foreach ($items as $item) {
			$this->_rows[] = $this->addRow($item);
		}
		$this->_currentRow = null;
	}

	public function setCells($items) {
		Yii::beginProfile(__CLASS__ . ':'. __FUNCTION__);
		while (!empty($items)) {
			$this->currentRow->addCells($items);
			if (!empty($items)) {
				$this->_currentRow = null;
			}
		}
		Yii::endProfile(__CLASS__ . ':'. __FUNCTION__);
	}

	public function getCurrentRow() {
		if (isset($this->_currentRow) && $this->_currentRow->isFilled()) {
			$this->_currentRow = null;
		}
		if (is_null($this->_currentRow)) {
			$this->_currentRow = Yii::createObject(['class' => $this->rowClass]);
			$this->_rows[] = $this->_currentRow;
		}
		return $this->_currentRow;
	}
}
?>