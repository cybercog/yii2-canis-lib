<?php
namespace infinite\web\grid;

class Row extends \infinite\base\Object {
	const TOTAL_COLUMNS = 12;

	protected $_cells = [];
	protected $_fillAttempted = false;

	public function render() {
		echo $this->generate();
	}
	
	public function generate() {
		$this->fill();
		$content = [];
		foreach ($this->_cells as $item) {
			$content[] = $item->generate();
		}
		return implode('', $content);
		// return Html::tag('div', implode('', $content), ['class' => 'row']);
	}

	public function fill() {
		if (!$this->_fillAttempted) {
			$toFill = self::TOTAL_COLUMNS - $this->columnCount;
			if (!empty($toFill)) {
				foreach ($this->columnFlex as $columnId => $flex) {
					if (empty($toFill)) { break; }
					if (empty($flex)) { continue; }
					$columnItem = $this->_cells[$columnId];
					$addColumns = min($toFill, $columnItem->flex);
					$columnItem->addColumns($addColumns);
					$toFill = $toFill - $addColumns;
				}
			}
			$this->_fillAttempted = true;
		}
	}

	public function getColumnFlex() {
		$flex = [];
		foreach ($this->_cells as $column) {
			$flex[$column->id] = $column->flex;
		}
		arsort($flex, SORT_NUMERIC);
		return $flex;
	}

	public function isFilled() {
		return $this->columnCount === self::TOTAL_COLUMNS;
	}

	public function getColumnCount() {
		$columnCount = 0;
		foreach ($this->_cells as $item) {
			$columnCount += $item->columns;
		}
		return $columnCount;
	}

	public function hasRoom($additional) {
		if ($this->columnCount + $additional > self::TOTAL_COLUMNS) {
			return false;
		}
		return true;
	}

	public function addCell(Cell $item) {
		if ($this->hasRoom($item->columns)) {
			$this->_cells[$item->id] = $item;
			return true;
		}
		return false;
	}

	public function addCells(&$items) {
		foreach ($items as $ikey => $item) {
			if ($this->addCell($item)) {
				unset($items[$ikey]);
			} else {
				break;
			}
		}
	}
}
?>