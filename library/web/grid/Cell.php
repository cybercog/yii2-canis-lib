<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\web\grid;

use teal\helpers\Html;

/**
 * Cell [[@doctodo class_description:teal\web\grid\Cell]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Cell extends \teal\base\Object implements \teal\web\RenderInterface
{
    /**
     * @var [[@doctodo var_type:phoneSize]] [[@doctodo var_description:phoneSize]]
     */
    public $phoneSize = false;
    /**
     * @var [[@doctodo var_type:tabletSize]] [[@doctodo var_description:tabletSize]]
     */
    public $tabletSize = 'auto';
    /**
     * @var [[@doctodo var_type:mediumDesktopSize]] [[@doctodo var_description:mediumDesktopSize]]
     */
    public $mediumDesktopSize = 'auto'; // baseline
    /**
     * @var [[@doctodo var_type:largeDesktopSize]] [[@doctodo var_description:largeDesktopSize]]
     */
    public $largeDesktopSize = false;
    /**
     * @var [[@doctodo var_type:baseSize]] [[@doctodo var_description:baseSize]]
     */
    public $baseSize = 'mediumDesktop';
    /**
     * @var [[@doctodo var_type:htmlOptions]] [[@doctodo var_description:htmlOptions]]
     */
    public $htmlOptions = [];
    /**
     * @var [[@doctodo var_type:_prepend]] [[@doctodo var_description:_prepend]]
     */
    protected $_prepend = [];
    /**
     * @var [[@doctodo var_type:_content]] [[@doctodo var_description:_content]]
     */
    protected $_content;
    /**
     * @var [[@doctodo var_type:_append]] [[@doctodo var_description:_append]]
     */
    protected $_append = [];
    /**
     * @var [[@doctodo var_type:_id]] [[@doctodo var_description:_id]]
     */
    protected $_id;

    /**
     * @var [[@doctodo var_type:_phoneColumns]] [[@doctodo var_description:_phoneColumns]]
     */
    protected $_phoneColumns = 12;
    /**
     * @var [[@doctodo var_type:_tabletColumns]] [[@doctodo var_description:_tabletColumns]]
     */
    protected $_tabletColumns = 6;
    /**
     * @var [[@doctodo var_type:_mediumDesktopColumns]] [[@doctodo var_description:_mediumDesktopColumns]]
     */
    protected $_mediumDesktopColumns = 6;
    /**
     * @var [[@doctodo var_type:_largeDesktopColumns]] [[@doctodo var_description:_largeDesktopColumns]]
     */
    protected $_largeDesktopColumns = 3;
    /**
     * @var [[@doctodo var_type:_maxPhoneColumns]] [[@doctodo var_description:_maxPhoneColumns]]
     */
    protected $_maxPhoneColumns;
    /**
     * @var [[@doctodo var_type:_maxTabletColumns]] [[@doctodo var_description:_maxTabletColumns]]
     */
    protected $_maxTabletColumns;
    /**
     * @var [[@doctodo var_type:_maxMediumDesktopColumns]] [[@doctodo var_description:_maxMediumDesktopColumns]]
     */
    protected $_maxMediumDesktopColumns;
    /**
     * @var [[@doctodo var_type:_maxLargeDesktopColumns]] [[@doctodo var_description:_maxLargeDesktopColumns]]
     */
    protected $_maxLargeDesktopColumns;

    /**
     * Get content.
     *
     * @return [[@doctodo return_type:getContent]] [[@doctodo return_description:getContent]]
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * Set content.
     *
     * @param [[@doctodo param_type:content]] $content [[@doctodo param_description:content]]
     */
    public function setContent($content)
    {
        $this->_content = $content;
    }

    /**
     * [[@doctodo method_description:output]].
     */
    public function output()
    {
        echo $this->generate();
    }

    /**
     * [[@doctodo method_description:prepend]].
     *
     * @param [[@doctodo param_type:pre]] $pre [[@doctodo param_description:pre]]
     */
    public function prepend($pre)
    {
        $this->_prepend[] = $pre;
    }

    /**
     * [[@doctodo method_description:append]].
     *
     * @param [[@doctodo param_type:append]] $append [[@doctodo param_description:append]]
     */
    public function append($append)
    {
        $this->_append[] = $append;
    }

    /**
     * [[@doctodo method_description:generate]].
     *
     * @return [[@doctodo return_type:generate]] [[@doctodo return_description:generate]]
     */
    public function generate()
    {
        $content = $this->content;
        if (is_object($content) && $content instanceof CellContentInterface) {
            $content = $content->generate();
        }
        Html::addCssClass($this->htmlOptions, $this->classes);

        return Html::tag('div', implode($this->_prepend) . $content . implode($this->_append), $this->htmlOptions);
    }

    /**
     * [[@doctodo method_description:generatePhoneSize]].
     *
     * @return [[@doctodo return_type:generatePhoneSize]] [[@doctodo return_description:generatePhoneSize]]
     */
    public function generatePhoneSize()
    {
        return $this->phoneColumns;
    }

    /**
     * [[@doctodo method_description:generateTabletSize]].
     *
     * @return [[@doctodo return_type:generateTabletSize]] [[@doctodo return_description:generateTabletSize]]
     */
    public function generateTabletSize()
    {
        return $this->tabletColumns;
    }

    /**
     * [[@doctodo method_description:generateMediumDesktopSize]].
     *
     * @return [[@doctodo return_type:generateMediumDesktopSize]] [[@doctodo return_description:generateMediumDesktopSize]]
     */
    public function generateMediumDesktopSize()
    {
        return $this->mediumDesktopColumns;
    }

    /**
     * [[@doctodo method_description:generateLargeDesktopSize]].
     *
     * @return [[@doctodo return_type:generateLargeDesktopSize]] [[@doctodo return_description:generateLargeDesktopSize]]
     */
    public function generateLargeDesktopSize()
    {
        return $this->largeDesktopColumns;
    }

    /**
     * Get classes.
     *
     * @return [[@doctodo return_type:getClasses]] [[@doctodo return_description:getClasses]]
     */
    public function getClasses()
    {
        $classes = [];
        $sizes = $this->sizes;
        if (isset($sizes['phone'])) {
            $classes[] = 'col-xs-' . $sizes['phone'];
        }
        if (isset($sizes['tablet'])) {
            $classes[] = 'col-sm-' . $sizes['tablet'];
        }
        if (isset($sizes['mediumDesktop'])) {
            $classes[] = 'col-md-' . $sizes['mediumDesktop'];
        }
        if (isset($sizes['largeDesktop'])) {
            $classes[] = 'col-lg-' . $sizes['largeDesktop'];
        }

        return implode(' ', $classes);
    }

    /**
     * Get sizes.
     *
     * @return [[@doctodo return_type:getSizes]] [[@doctodo return_description:getSizes]]
     */
    public function getSizes()
    {
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

    /**
     * Get id.
     *
     * @return [[@doctodo return_type:getId]] [[@doctodo return_description:getId]]
     */
    public function getId()
    {
        if (is_null($this->_id)) {
            $this->_id = md5(microtime() . mt_rand());
        }

        return $this->_id;
    }

    /**
     * [[@doctodo method_description:addColumns]].
     *
     * @param integer                      $n    [[@doctodo param_description:n]] [optional]
     * @param [[@doctodo param_type:size]] $size [[@doctodo param_description:size]] [optional]
     *
     * @return [[@doctodo return_type:addColumns]] [[@doctodo return_description:addColumns]]
     */
    public function addColumns($n = 1, $size = null)
    {
        if (is_null($size)) {
            $size = $this->baseSize;
        }
        $columnAttribute = $size . 'Columns';
        $hasAttribute = 'has' . ucfirst($size) . 'Columns';
        if (!$this->{$hasAttribute}) {
            $this->{$columnAttribute} = $this->{$columnAttribute};
        }
        $this->{$columnAttribute} += $n;

        return true;
    }

    /**
     * [[@doctodo method_description:maxColumns]].
     *
     * @param [[@doctodo param_type:size]] $size [[@doctodo param_description:size]] [optional]
     *
     * @return [[@doctodo return_type:maxColumns]] [[@doctodo return_description:maxColumns]]
     */
    public function maxColumns($size = null)
    {
        if (is_null($size)) {
            $size = $this->baseSize;
        }
        $columnAttribute = $size . 'Columns';

        $this->{$columnAttribute} = $this->getMaxColumns($size);

        return true;
    }

    /**
     * Get max columns.
     *
     * @param [[@doctodo param_type:size]] $size [[@doctodo param_description:size]] [optional]
     *
     * @return [[@doctodo return_type:getMaxColumns]] [[@doctodo return_description:getMaxColumns]]
     */
    public function getMaxColumns($size = null)
    {
        if (is_object($this->content) && property_exists($this->content, 'maxColumns') && !is_null($this->content->maxColumns)) {
            return $this->content->maxColumns;
        }
        if (is_null($size)) {
            $size = $this->baseSize;
        }
        $columnAttribute = 'max' . ucfirst($size) . 'Columns';

        return $this->{$columnAttribute};
    }

    /**
     * Set max columns.
     *
     * @param [[@doctodo param_type:columns]] $columns [[@doctodo param_description:columns]]
     * @param [[@doctodo param_type:size]]    $size    [[@doctodo param_description:size]] [optional]
     */
    public function setMaxColumns($columns, $size = null)
    {
        if (is_null($size)) {
            $size = $this->baseSize;
        }
        $columnAttribute = 'max' . ucfirst($size) . 'Columns';
        $this->{$columnAttribute} = $columns;
    }

    /**
     * Set columns.
     *
     * @param [[@doctodo param_type:columns]] $columns [[@doctodo param_description:columns]]
     * @param [[@doctodo param_type:size]]    $size    [[@doctodo param_description:size]] [optional]
     */
    public function setColumns($columns, $size = null)
    {
        if (is_null($size)) {
            $size = $this->baseSize;
        }
        $columnAttribute = $size . 'Columns';
        $this->{$columnAttribute} = $columns;
    }

    /**
     * Get columns.
     *
     * @param [[@doctodo param_type:size]] $size [[@doctodo param_description:size]] [optional]
     *
     * @return [[@doctodo return_type:getColumns]] [[@doctodo return_description:getColumns]]
     */
    public function getColumns($size = null)
    {
        if (is_object($this->content) && property_exists($this->content, 'columns') && !is_null($this->content->columns)) {
            return $this->content->columns;
        }

        if (is_null($size)) {
            $size = $this->baseSize;
        }
        $columnAttribute = $size . 'Columns';

        return $this->{$columnAttribute};
    }

    /**
     * Set phone columns.
     *
     * @param [[@doctodo param_type:columns]] $columns [[@doctodo param_description:columns]]
     */
    public function setPhoneColumns($columns)
    {
        $this->phoneSize = true;
        $this->_phoneColumns = $columns;
    }

    /**
     * Set tablet columns.
     *
     * @param [[@doctodo param_type:columns]] $columns [[@doctodo param_description:columns]]
     */
    public function setTabletColumns($columns)
    {
        $this->tabletSize = true;
        $this->_tabletColumns = $columns;
    }

    /**
     * Set medium desktop columns.
     *
     * @param [[@doctodo param_type:columns]] $columns [[@doctodo param_description:columns]]
     */
    public function setMediumDesktopColumns($columns)
    {
        $this->mediumDesktopSize = true;
        $this->_mediumDesktopColumns = $columns;
    }

    /**
     * Set large desktop columns.
     *
     * @param [[@doctodo param_type:columns]] $columns [[@doctodo param_description:columns]]
     */
    public function setLargeDesktopColumns($columns)
    {
        $this->largeDesktopSize = true;
        $this->_largeDesktopColumns = $columns;
    }

    /**
     * Set max phone columns.
     *
     * @param [[@doctodo param_type:columns]] $columns [[@doctodo param_description:columns]]
     */
    public function setMaxPhoneColumns($columns)
    {
        $this->_maxPhoneColumns = $columns;
    }

    /**
     * Set max tablet columns.
     *
     * @param [[@doctodo param_type:columns]] $columns [[@doctodo param_description:columns]]
     */
    public function setMaxTabletColumns($columns)
    {
        $this->_maxTabletColumns = $columns;
    }

    /**
     * Set max medium desktop columns.
     *
     * @param [[@doctodo param_type:columns]] $columns [[@doctodo param_description:columns]]
     */
    public function setMaxMediumDesktopColumns($columns)
    {
        $this->_maxMediumDesktopColumns = $columns;
    }

    /**
     * Set max large desktop columns.
     *
     * @param [[@doctodo param_type:columns]] $columns [[@doctodo param_description:columns]]
     */
    public function setMaxLargeDesktopColumns($columns)
    {
        $this->_maxLargeDesktopColumns = $columns;
    }

    /**
     * Get max phone columns.
     *
     * @return [[@doctodo return_type:getMaxPhoneColumns]] [[@doctodo return_description:getMaxPhoneColumns]]
     */
    public function getMaxPhoneColumns()
    {
        if ($this->_maxPhoneColumns === null) {
            return 12;
        }

        return $this->_maxPhoneColumns;
    }

    /**
     * Get max tablet columns.
     *
     * @return [[@doctodo return_type:getMaxTabletColumns]] [[@doctodo return_description:getMaxTabletColumns]]
     */
    public function getMaxTabletColumns()
    {
        if ($this->_maxTabletColumns === null) {
            return 12;
        }

        return $this->_maxTabletColumns;
    }

    /**
     * Get max medium desktop columns.
     *
     * @return [[@doctodo return_type:getMaxMediumDesktopColumns]] [[@doctodo return_description:getMaxMediumDesktopColumns]]
     */
    public function getMaxMediumDesktopColumns()
    {
        if ($this->_maxMediumDesktopColumns === null) {
            return 12;
        }

        return $this->_maxMediumDesktopColumns;
    }

    /**
     * Get max large desktop columns.
     *
     * @return [[@doctodo return_type:getMaxLargeDesktopColumns]] [[@doctodo return_description:getMaxLargeDesktopColumns]]
     */
    public function getMaxLargeDesktopColumns()
    {
        if ($this->_maxLargeDesktopColumns === null) {
            return 12;
        }

        return $this->_maxLargeDesktopColumns;
    }

    /**
     * Get phone columns.
     *
     * @return [[@doctodo return_type:getPhoneColumns]] [[@doctodo return_description:getPhoneColumns]]
     */
    public function getPhoneColumns()
    {
        if (!$this->hasPhoneColumns) {
            return 12;
        }

        return $this->_phoneColumns;
    }

    /**
     * Get tablet columns.
     *
     * @return [[@doctodo return_type:getTabletColumns]] [[@doctodo return_description:getTabletColumns]]
     */
    public function getTabletColumns()
    {
        if (!$this->hasTabletColumns) {
            return 6;
        }

        return $this->_tabletColumns;
    }

    /**
     * Get medium desktop columns.
     *
     * @return [[@doctodo return_type:getMediumDesktopColumns]] [[@doctodo return_description:getMediumDesktopColumns]]
     */
    public function getMediumDesktopColumns()
    {
        if (!$this->hasMediumDesktopColumns) {
            return 6;
        }

        return $this->_mediumDesktopColumns;
    }

    /**
     * Get large desktop columns.
     *
     * @return [[@doctodo return_type:getLargeDesktopColumns]] [[@doctodo return_description:getLargeDesktopColumns]]
     */
    public function getLargeDesktopColumns()
    {
        if (!$this->hasLargeDesktopColumns) {
            return 6;
        }

        return $this->_largeDesktopColumns;
    }

    /**
     * Get has phone columns.
     *
     * @return [[@doctodo return_type:getHasPhoneColumns]] [[@doctodo return_description:getHasPhoneColumns]]
     */
    public function getHasPhoneColumns()
    {
        return $this->_phoneColumns !== null;
    }

    /**
     * Get has tablet columns.
     *
     * @return [[@doctodo return_type:getHasTabletColumns]] [[@doctodo return_description:getHasTabletColumns]]
     */
    public function getHasTabletColumns()
    {
        return $this->_tabletColumns !== null;
    }

    /**
     * Get has medium desktop columns.
     *
     * @return [[@doctodo return_type:getHasMediumDesktopColumns]] [[@doctodo return_description:getHasMediumDesktopColumns]]
     */
    public function getHasMediumDesktopColumns()
    {
        return $this->_mediumDesktopColumns !== null;
    }

    /**
     * Get has large desktop columns.
     *
     * @return [[@doctodo return_type:getHasLargeDesktopColumns]] [[@doctodo return_description:getHasLargeDesktopColumns]]
     */
    public function getHasLargeDesktopColumns()
    {
        return $this->_largeDesktopColumns !== null;
    }

    /**
     * Get flex.
     *
     * @param [[@doctodo param_type:size]] $size [[@doctodo param_description:size]] [optional]
     *
     * @return [[@doctodo return_type:getFlex]] [[@doctodo return_description:getFlex]]
     */
    public function getFlex($size = null)
    {
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
