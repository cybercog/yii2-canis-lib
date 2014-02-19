<?php
namespace infinite\web\grid;

use infinite\helpers\Html;
use infinite\base\exceptions\Exception;


class Cell extends \infinite\base\Object implements \infinite\web\RenderInterface {
	/*
		$*Size attributes values:
			false: don't put it in the class
			true: use the $_*Columns value
			'auto': call generate*()
	*/
	public $phoneSize = false;
	public $tabletSize = 'auto';
	public $mediumDesktopSize = 'auto'; // baseline
	public $largeDesktopSize = false;
	public $baseSize = 'mediumDesktop';
	public $htmlOptions = [];

	protected $_prepend = [];
	protected $_content;
	protected $_append = [];
	protected $_id;

	protected $_phoneColumns = 12;
	protected $_tabletColumns = 6;
	protected $_mediumDesktopColumns = 6;
	protected $_largeDesktopColumns = 3;

	protected $_maxPhoneColumns;
	protected $_maxTabletColumns;
	protected $_maxMediumDesktopColumns;
	protected $_maxLargeDesktopColumns;


	public function getContent() {
		return $this->_content;
	}

	public function setContent($content) {
		$this->_content = $content;
	}

	public function output() {
		echo $this->generate();
	}

	public function prepend($pre)
	{
		$this->_prepend[] = $pre;
	}

	public function append($append)
	{
		$this->_append[] = $append;
	}
	
	public function generate() {
		$content = $this->content;
		if (is_object($content) && $content instanceof CellContentInterface) {
			$content = $content->generate();
		}
		Html::addCssClass($this->htmlOptions, $this->classes);
		return Html::tag('div', implode($this->_prepend) . $content . implode($this->_append), $this->htmlOptions);
	}

	public function generatePhoneSize() {
		return $this->phoneColumns;
	}

	public function generateTabletSize() {
		return $this->tabletColumns;
	}

	public function generateMediumDesktopSize() {
		return $this->mediumDesktopColumns;
	}

	public function generateLargeDesktopSize() {
		return $this->largeDesktopColumns;
	}


	public function getClasses() {
		$classes = [];
		$sizes = $this->sizes;
		if (isset($sizes['phone'])) {
			$classes[] = 'col-xs-'. $sizes['phone'];
		}
		if (isset($sizes['tablet'])) {
			$classes[] = 'col-sm-'. $sizes['tablet'];
		}
		if (isset($sizes['mediumDesktop'])) {
			$classes[] = 'col-md-'. $sizes['mediumDesktop'];
		}
		if (isset($sizes['largeDesktop'])) {
			$classes[] = 'col-lg-'. $sizes['largeDesktop'];
		}

		return implode(' ', $classes);
	}

	public function getSizes() {
		$sizes = [];
		if ($this->phoneSize === 'auto') {
			$sizes['phone'] = $this->generatePhoneSize();
		} elseif ($this->phoneSize === true && $this->hasPhoneColumns) {
			$sizes['phone'] = $this->phoneColumns;
		}
		if ($this->tabletSize === 'auto') {
			$sizes['tablet'] = $this->generateTabletSize();
		} elseif ($this->tabletSize === true && $this->hasTabletColumns) {
			$sizes['tablet'] = $this->tabletColumns;
		}
		if ($this->mediumDesktopSize === 'auto') {
			$sizes['mediumDesktop'] = $this->generateMediumDesktopSize();
		} elseif ($this->mediumDesktopSize === true && $this->hasMediumDesktopColumns) {
			$sizes['mediumDesktop'] = $this->mediumDesktopColumns;
		}
		if ($this->largeDesktopSize === 'auto') {
			$sizes['largeDesktop'] = $this->generateLargeDesktopSize();
		} elseif ($this->largeDesktopSize === true && $this->hasLargeDesktopColumns) {
			$sizes['largeDesktop'] = $this->largeDesktopColumns;
		}
		return $sizes;
	}

	public function getId() {
		if (is_null($this->_id)) {
			$this->_id = md5(microtime() . mt_rand());
		}
		return $this->_id;
	}

	public function addColumns($n = 1, $size = null) {
		if (is_null($size)) {
			$size = $this->baseSize;
		}
		$columnAttribute = $size . 'Columns';
		$hasAttribute = 'has'. ucfirst($size) . 'Columns';
		if (!$this->{$hasAttribute}) {
			$this->{$columnAttribute} = $this->{$columnAttribute};
		}
		$this->{$columnAttribute} += $n;
		return true;
	}

	public function maxColumns($size = null) {
		if (is_null($size)) {
			$size = $this->baseSize;
		}
		$columnAttribute = $size . 'Columns';

		$this->{$columnAttribute} = $this->getMaxColumns($size);
		return true;
	}

	public function getMaxColumns($size = null) {
		if (is_object($this->content) && property_exists($this->content, 'maxColumns') && !is_null($this->content->maxColumns)) {
			return $this->content->maxColumns;
		}
		if (is_null($size)) {
			$size = $this->baseSize;
		}
		$columnAttribute = 'max'. ucfirst($size) . 'Columns';
		return $this->{$columnAttribute};
	}

	public function setMaxColumns($columns, $size = null) {
		if (is_null($size)) {
			$size = $this->baseSize;
		}
		$columnAttribute = 'max'. ucfirst($size) . 'Columns';
		$this->{$columnAttribute} = $columns;
	}

	public function setColumns($columns, $size = null) {
		if (is_null($size)) {
			$size = $this->baseSize;
		}
		$columnAttribute = $size . 'Columns';
		$this->{$columnAttribute} = $columns;
	}

	public function getColumns($size = null) {
		if (is_object($this->content) && property_exists($this->content, 'columns') && !is_null($this->content->columns)) {
			return $this->content->columns;
		}

		if (is_null($size)) {
			$size = $this->baseSize;
		}
		$columnAttribute = $size . 'Columns';
		return $this->{$columnAttribute};
	}

	public function setPhoneColumns($columns) {
		$this->phoneSize = true;
		$this->_phoneColumns = $columns;
	}

	public function setTabletColumns($columns) {
		$this->tabletSize = true;
		$this->_tabletColumns = $columns;
	}

	public function setMediumDesktopColumns($columns) {
		$this->mediumDesktopSize = true;
		$this->_mediumDesktopColumns = $columns;
	}

	public function setLargeDesktopColumns($columns) {
		$this->largeDesktopSize = true;
		$this->_largeDesktopColumns = $columns;
	}


	public function setMaxPhoneColumns($columns) {
		$this->_maxPhoneColumns = $columns;
	}

	public function setMaxTabletColumns($columns) {
		$this->_maxTabletColumns = $columns;
	}

	public function setMaxMediumDesktopColumns($columns) {
		$this->_maxMediumDesktopColumns = $columns;
	}

	public function setMaxLargeDesktopColumns($columns) {
		$this->_maxLargeDesktopColumns = $columns;
	}


	
	public function getMaxPhoneColumns() {
		if ($this->_maxPhoneColumns === null) {
			return 12;
		}
		return $this->_maxPhoneColumns;
	}

	public function getMaxTabletColumns() {
		if ($this->_maxTabletColumns === null) {
			return 12;
		}
		return $this->_maxTabletColumns;
	}

	public function getMaxMediumDesktopColumns() {
		if ($this->_maxMediumDesktopColumns === null) {
			return 12;
		}
		return $this->_maxMediumDesktopColumns;
	}

	public function getMaxLargeDesktopColumns() {
		if ($this->_maxLargeDesktopColumns === null) {
			return 12;
		}
		return $this->_maxLargeDesktopColumns;
	}

	public function getPhoneColumns() {
		if (!$this->hasPhoneColumns) {
			return 12;
		}
		return $this->_phoneColumns;
	}

	public function getTabletColumns() {
		if (!$this->hasTabletColumns) {
			return 6;
		}
		return $this->_tabletColumns;
	}

	public function getMediumDesktopColumns() {
		if (!$this->hasMediumDesktopColumns) {
			return 6;
		}
		return $this->_mediumDesktopColumns;
	}

	public function getLargeDesktopColumns() {
		if (!$this->hasLargeDesktopColumns) {
			return 6;
		}
		return $this->_largeDesktopColumns;
	}

	public function getHasPhoneColumns() {
		return $this->_phoneColumns !== null;
	}

	public function getHasTabletColumns() {
		return $this->_tabletColumns !== null;
	}

	public function getHasMediumDesktopColumns() {
		return $this->_mediumDesktopColumns !== null;
	}

	public function getHasLargeDesktopColumns() {
		return $this->_largeDesktopColumns !== null;
	}

	public function getFlex($size = null) {
		if (is_null($size)) {
			$size = $this->baseSize;
		}
		$getter = $size . 'Columns';
		$flex = $this->getMaxColumns($size) - $this->{$getter};
		if ($flex < 0) {
			return 0;
		}
		return $flex;
	}

}
?>