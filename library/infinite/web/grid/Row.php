<?php
namespace infinite\web\grid;

use infinite\helpers\Html;

class Row extends \infinite\base\Object {
	const TOTAL_COLUMNS = 12;

	public $htmlOptions = ['class' => 'row'];
	protected $_cells = [];
	protected $_fillAttempted = false;


	public function output() {
		echo $this->generate();
	}
	
	public function generate() {
		$this->fill();
		$content = [];
		foreach ($this->_cells as $item) {
			$content[] = $item->generate();
		}
		//return implode('', $content);
		return Html::tag('div', implode('', $content), $this->htmlOptions);
	}


	public function fill() {
		if (!$this->_fillAttempted) {
			$fillSizes = ['phone' => self::TOTAL_COLUMNS, 'tablet' => self::TOTAL_COLUMNS, 'mediumDesktop' => self::TOTAL_COLUMNS, 'largeDesktop' => self::TOTAL_COLUMNS];

			foreach ($this->_cells as $cell) {
				$sizes = $cell->sizes;
				foreach ($fillSizes as $size => $left) {
					if (!isset($sizes[$size])) {
						unset($fillSizes[$size]);
					} elseif($sizes[$size] !== 'auto') {
						$fillSizes[$size] = $left - $sizes[$size];
					}
				}
			}

			foreach ($fillSizes as $size => $toFill) {
				$toDistribute = $this->getDistributionColumns($size);
				if (!empty($toDistribute)) {
					$columnSize = max(1, floor($toFill/count($toDistribute)));
					foreach ($toDistribute as $cell) {
						$fillSizes[$size] = $fillSizes[$size] - ($columnSize - $cell->getColumns($size));
						$cell->setColumns($columnSize, $size);
					}
				}
			}

			foreach ($fillSizes as $size => $toFill) {
				if ($toFill <= 0) { continue; }
				foreach ($this->getColumnFlex($size) as $columnId => $flex) {
					if ($toFill <= 0) { break; }
					if (empty($flex)) { continue; }

					$columnItem = $this->_cells[$columnId];
					$addColumns = min($toFill, $columnItem->getFlex($size));
					$columnItem->addColumns($addColumns, $size);
					$toFill = $toFill - $addColumns;
				}
			}
			$this->_fillAttempted = true;
		}
	}

	public function getColumnFlex($size = 'phone') {
		$flex = [];
		foreach ($this->_cells as $column) {
			$flex[$column->id] = $column->getFlex($size);
		}
		arsort($flex, SORT_NUMERIC);
		return $flex;
	}

	public function getDistributionColumns($size = null) {
		$auto = [];
		foreach ($this->_cells as $cell) {
			if ($cell->getColumns($size) === 'auto') {
				$auto[$cell->id] = $cell;
			}
		}
		return $auto;
	}

	public function isFilled() {
		return $this->columnCount === self::TOTAL_COLUMNS;
	}

	public function getColumnCount() {
		$columnCount = 0;
		foreach ($this->_cells as $item) {
			if ($item->columns === 'auto') { continue; }
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

	public function addCell(Cell $item, $check = false) {
		if (!$check || $this->hasRoom($item->columns)) {
			$this->_cells[$item->id] = $item;
			return true;
		}
		return false;
	}

	public function addCells(&$items) {
		foreach ($items as $ikey => $item) {
			if ($this->addCell($item, true)) {
				unset($items[$ikey]);
			} else {
				break;
			}
		}
	}

	public function setCells($cells) {
		foreach ($cells as $cell) {
			$this->addCell($cell, false);
		}
	}
}
?>