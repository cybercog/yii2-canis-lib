<?php
namespace infinite\web\grid;

use \infinite\helpers\Html;
use \infinite\base\exceptions\Exception;

use \yii\bootstrap\Widget;

class Cell extends \infinite\base\Object {
	public $maxColumns = 3;
	public $content;

	public $phoneSize = false;
	public $tabletSize = 'auto';
	public $mediumDesktopSize = 'auto'; // baseline
	public $largeDesktopSize = false;

	protected $_id;
	protected $_columns;
	protected $_defaultColumns = 3;


	public function render() {
		echo $this->generate();
	}
	
	public function generate() {
		$content = $this->content;
		if ($content instanceof Widget) {
			$content = $content->generate();
		}
		return Html::tag('div', $content, ['class' => $this->classes]);
	}

	public function getClasses() {
		$classes = [];
		if ($this->phoneSize) {
			if ($this->phoneSize === 'auto') {
				$this->phoneSize = 12;
			}
			$classes[] = 'col-xs-'. $this->phoneSize;
		}
		if ($this->tabletSize) {
			if ($this->tabletSize === 'auto') {
				$this->tabletSize = 6;
			}
			$classes[] = 'col-sm-'. $this->tabletSize;
		}
		if ($this->mediumDesktopSize) {
			if ($this->mediumDesktopSize === 'auto') {
				$this->mediumDesktopSize = $this->columns;
			}
			$classes[] = 'col-md-'. $this->mediumDesktopSize;
		}
		if ($this->largeDesktopSize) {
			if ($this->largeDesktopSize === 'auto') {
				$this->largeDesktopSize = $this->columns;
			}
			$classes[] = 'col-lg-'. $this->largeDesktopSize;
		}
		return implode(' ', $classes);
	}

	public function getId() {
		if (is_null($this->_id)) {
			$this->_id = uniqid(md5(rand()), true);
		}
		return $this->_id;
	}

	public function addColumns($n = 1) {
		if (is_null($this->_columns)) {
			$this->_columns = $this->_defaultColumns;
		}
		$this->_columns += $n;
		return true;
	}

	public function maxColumns() {
		$this->_columns = $this->maxColumns;
		return true;
	}

	public function setColumns($columns) {
		if (!isset($this->_columns)) {
			$this->_columns = $columns;
		} else {
			throw new Exception("Unable to re-set column size to {$columns}");
		}
	}

	public function getColumns() {
		if (is_null($this->_columns)) {
			return $this->_defaultColumns;
		}
		return $this->_columns;
	}

	public function getFlex() {
		return $this->maxColumns - $this->columns;
	}

}
?>