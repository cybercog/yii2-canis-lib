<?php
namespace infinite\web\grid;

class Grid extends \infinite\base\Object {
	//public $fillPreviousRows = true;

	protected $_rows = [];
	protected $_currentRow;

	public function render() {
		echo $this->generate();
	}

	public function generate() {
		$items = [];
		foreach ($this->_rows as $row) {
			$items[] = $row->generate();
		}
		return implode('', $items);
	}

	public function addCells($items) {
		while (!empty($items)) {
			$this->currentRow->addCells($items);
		}
	}

	public function getCurrentRow() {
		if (isset($this->_currentRow) && $this->_currentRow->isFilled()) {
			$this->_currentRow = null;
		}
		if (is_null($this->_currentRow)) {
			$this->_currentRow = new Row;
			$this->_rows[] = $this->_currentRow;
		}
		return $this->_currentRow;
	}
}
?>