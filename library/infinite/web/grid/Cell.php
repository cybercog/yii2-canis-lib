<?php
namespace infinite\web\grid;

use \infinite\helpers\Html;
use \infinite\base\exceptions\Exception;


class Cell extends \infinite\base\Object {
	public $phoneSize = false;
	public $tabletSize = 'auto';
	public $mediumDesktopSize = 'auto'; // baseline
	public $largeDesktopSize = false;
	public $baseSize = 'mediumDesktop';

	protected $_content;
	protected $_id;
	protected $_columns;
	protected $_defaultColumns = 3;
	protected $_maxColumns = 3;


	public function getContent() {
		return $this->_content;
	}

	public function setContent($content) {
		$this->_content = $content;
	}

	public function render() {
		echo $this->generate();
	}
	
	public function generate() {
		$content = $this->content;
		if ($content instanceof CellContentInterface) {
			$content = $content->generate();
		} else {
			echo "holla";
			var_dump($content);exit;
		}
		return Html::tag('div', $content, ['class' => $this->classes]);
	}

	public function generatePhoneSize() {
		if ($this->baseSize === 'phoneSize') {
			return $this->columns;
		}
		return 12;
	}

	public function generateTabletSize() {
		if ($this->baseSize === 'tabletSize') {
			return $this->columns;
		}
		return 6;
	}

	public function generateMediumDesktopSize() {
		if ($this->baseSize === 'mediumDesktop') {
			return $this->columns;
		}
		return 6;
	}

	public function generateLargeDesktopSize() {
		if ($this->baseSize === 'largeDesktop') {
			return $this->columns;
		}
		return 3;
	}


	public function getClasses() {
		$classes = [];
		if ($this->phoneSize) {
			if ($this->phoneSize === 'auto') {
				$this->phoneSize = $this->generatePhoneSize();
			}
			$classes[] = 'col-xs-'. $this->phoneSize;
		}
		if ($this->tabletSize) {
			if ($this->tabletSize === 'auto') {
				$this->tabletSize = $this->generateTabletSize();
			}
			$classes[] = 'col-sm-'. $this->tabletSize;
		}
		if ($this->mediumDesktopSize) {
			if ($this->mediumDesktopSize === 'auto') {
				$this->mediumDesktopSize = $this->generateMediumDesktopSize();
			}
			$classes[] = 'col-md-'. $this->mediumDesktopSize;
		}
		if ($this->largeDesktopSize) {
			if ($this->largeDesktopSize === 'auto') {
				$this->largeDesktopSize = $this->generateLargeDesktopSize();
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

	public function getMaxColumns() {
		if (property_exists($this->content, 'maxColumns') && !is_null($this->content->maxColumns)) {
			return $this->content->maxColumns;
		}
		return $this->_maxColumns;
	}

	public function setMaxColumns($columns) {
		$this->_maxColumns = $columns;
	}

	public function setColumns($columns) {
		$this->_columns = $columns;
	}

	public function getColumns() {
		if (property_exists($this->content, 'columns') && !is_null($this->content->columns)) {
			return $this->content->columns;
		}
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