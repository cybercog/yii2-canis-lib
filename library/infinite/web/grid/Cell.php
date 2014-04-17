<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\grid;

use infinite\helpers\Html;

/**
 * Cell [@doctodo write class description for Cell]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class Cell extends \infinite\base\Object implements \infinite\web\RenderInterface
{
    /*
        $*Size attributes values:
            false: don't put it in the class
            true: use the $_*Columns value
            'auto': call generate*()
    */
    /**
     * @var __var_phoneSize_type__ __var_phoneSize_description__
     */
    public $phoneSize = false;
    /**
     * @var __var_tabletSize_type__ __var_tabletSize_description__
     */
    public $tabletSize = 'auto';
    /**
     * @var __var_mediumDesktopSize_type__ __var_mediumDesktopSize_description__
     */
    public $mediumDesktopSize = 'auto'; // baseline
    /**
     * @var __var_largeDesktopSize_type__ __var_largeDesktopSize_description__
     */
    public $largeDesktopSize = false;
    /**
     * @var __var_baseSize_type__ __var_baseSize_description__
     */
    public $baseSize = 'mediumDesktop';
    /**
     * @var __var_htmlOptions_type__ __var_htmlOptions_description__
     */
    public $htmlOptions = [];

    /**
     * @var __var__prepend_type__ __var__prepend_description__
     */
    protected $_prepend = [];
    /**
     * @var __var__content_type__ __var__content_description__
     */
    protected $_content;
    /**
     * @var __var__append_type__ __var__append_description__
     */
    protected $_append = [];
    /**
     * @var __var__id_type__ __var__id_description__
     */
    protected $_id;

    /**
     * @var __var__phoneColumns_type__ __var__phoneColumns_description__
     */
    protected $_phoneColumns = 12;
    /**
     * @var __var__tabletColumns_type__ __var__tabletColumns_description__
     */
    protected $_tabletColumns = 6;
    /**
     * @var __var__mediumDesktopColumns_type__ __var__mediumDesktopColumns_description__
     */
    protected $_mediumDesktopColumns = 6;
    /**
     * @var __var__largeDesktopColumns_type__ __var__largeDesktopColumns_description__
     */
    protected $_largeDesktopColumns = 3;

    /**
     * @var __var__maxPhoneColumns_type__ __var__maxPhoneColumns_description__
     */
    protected $_maxPhoneColumns;
    /**
     * @var __var__maxTabletColumns_type__ __var__maxTabletColumns_description__
     */
    protected $_maxTabletColumns;
    /**
     * @var __var__maxMediumDesktopColumns_type__ __var__maxMediumDesktopColumns_description__
     */
    protected $_maxMediumDesktopColumns;
    /**
     * @var __var__maxLargeDesktopColumns_type__ __var__maxLargeDesktopColumns_description__
     */
    protected $_maxLargeDesktopColumns;


    /**
     * __method_getContent_description__
     * @return __return_getContent_type__ __return_getContent_description__
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * __method_setContent_description__
     * @param __param_content_type__ $content __param_content_description__
     */
    public function setContent($content)
    {
        $this->_content = $content;
    }

    /**
     * __method_output_description__
     */
    public function output()
    {
        echo $this->generate();
    }

    /**
     * __method_prepend_description__
     * @param __param_pre_type__ $pre __param_pre_description__
     */
    public function prepend($pre)
    {
        $this->_prepend[] = $pre;
    }

    /**
     * __method_append_description__
     * @param __param_append_type__ $append __param_append_description__
     */
    public function append($append)
    {
        $this->_append[] = $append;
    }

    /**
     * __method_generate_description__
     * @return __return_generate_type__ __return_generate_description__
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
     * __method_generatePhoneSize_description__
     * @return __return_generatePhoneSize_type__ __return_generatePhoneSize_description__
     */
    public function generatePhoneSize()
    {
        return $this->phoneColumns;
    }

    /**
     * __method_generateTabletSize_description__
     * @return __return_generateTabletSize_type__ __return_generateTabletSize_description__
     */
    public function generateTabletSize()
    {
        return $this->tabletColumns;
    }

    /**
     * __method_generateMediumDesktopSize_description__
     * @return __return_generateMediumDesktopSize_type__ __return_generateMediumDesktopSize_description__
     */
    public function generateMediumDesktopSize()
    {
        return $this->mediumDesktopColumns;
    }

    /**
     * __method_generateLargeDesktopSize_description__
     * @return __return_generateLargeDesktopSize_type__ __return_generateLargeDesktopSize_description__
     */
    public function generateLargeDesktopSize()
    {
        return $this->largeDesktopColumns;
    }


    /**
     * __method_getClasses_description__
     * @return __return_getClasses_type__ __return_getClasses_description__
     */
    public function getClasses()
    {
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

    /**
     * __method_getSizes_description__
     * @return __return_getSizes_type__ __return_getSizes_description__
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
     * __method_getId_description__
     * @return __return_getId_type__ __return_getId_description__
     */
    public function getId()
    {
        if (is_null($this->_id)) {
            $this->_id = md5(microtime() . mt_rand());
        }

        return $this->_id;
    }

    /**
     * __method_addColumns_description__
     * @param integer $n __param_n_description__ [optional]
     * @param __param_size_type__ $size __param_size_description__ [optional]
     * @return __return_addColumns_type__ __return_addColumns_description__
     */
    public function addColumns($n = 1, $size = null)
    {
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

    /**
     * __method_maxColumns_description__
     * @param __param_size_type__ $size __param_size_description__ [optional]
     * @return __return_maxColumns_type__ __return_maxColumns_description__
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
     * __method_getMaxColumns_description__
     * @param __param_size_type__ $size __param_size_description__ [optional]
     * @return __return_getMaxColumns_type__ __return_getMaxColumns_description__
     */
    public function getMaxColumns($size = null)
    {
        if (is_object($this->content) && property_exists($this->content, 'maxColumns') && !is_null($this->content->maxColumns)) {
            return $this->content->maxColumns;
        }
        if (is_null($size)) {
            $size = $this->baseSize;
        }
        $columnAttribute = 'max'. ucfirst($size) . 'Columns';

        return $this->{$columnAttribute};
    }

    /**
     * __method_setMaxColumns_description__
     * @param __param_columns_type__ $columns __param_columns_description__
     * @param __param_size_type__ $size __param_size_description__ [optional]
     */
    public function setMaxColumns($columns, $size = null)
    {
        if (is_null($size)) {
            $size = $this->baseSize;
        }
        $columnAttribute = 'max'. ucfirst($size) . 'Columns';
        $this->{$columnAttribute} = $columns;
    }

    /**
     * __method_setColumns_description__
     * @param __param_columns_type__ $columns __param_columns_description__
     * @param __param_size_type__ $size __param_size_description__ [optional]
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
     * __method_getColumns_description__
     * @param __param_size_type__ $size __param_size_description__ [optional]
     * @return __return_getColumns_type__ __return_getColumns_description__
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
     * __method_setPhoneColumns_description__
     * @param __param_columns_type__ $columns __param_columns_description__
     */
    public function setPhoneColumns($columns)
    {
        $this->phoneSize = true;
        $this->_phoneColumns = $columns;
    }

    /**
     * __method_setTabletColumns_description__
     * @param __param_columns_type__ $columns __param_columns_description__
     */
    public function setTabletColumns($columns)
    {
        $this->tabletSize = true;
        $this->_tabletColumns = $columns;
    }

    /**
     * __method_setMediumDesktopColumns_description__
     * @param __param_columns_type__ $columns __param_columns_description__
     */
    public function setMediumDesktopColumns($columns)
    {
        $this->mediumDesktopSize = true;
        $this->_mediumDesktopColumns = $columns;
    }

    /**
     * __method_setLargeDesktopColumns_description__
     * @param __param_columns_type__ $columns __param_columns_description__
     */
    public function setLargeDesktopColumns($columns)
    {
        $this->largeDesktopSize = true;
        $this->_largeDesktopColumns = $columns;
    }


    /**
     * __method_setMaxPhoneColumns_description__
     * @param __param_columns_type__ $columns __param_columns_description__
     */
    public function setMaxPhoneColumns($columns)
    {
        $this->_maxPhoneColumns = $columns;
    }

    /**
     * __method_setMaxTabletColumns_description__
     * @param __param_columns_type__ $columns __param_columns_description__
     */
    public function setMaxTabletColumns($columns)
    {
        $this->_maxTabletColumns = $columns;
    }

    /**
     * __method_setMaxMediumDesktopColumns_description__
     * @param __param_columns_type__ $columns __param_columns_description__
     */
    public function setMaxMediumDesktopColumns($columns)
    {
        $this->_maxMediumDesktopColumns = $columns;
    }

    /**
     * __method_setMaxLargeDesktopColumns_description__
     * @param __param_columns_type__ $columns __param_columns_description__
     */
    public function setMaxLargeDesktopColumns($columns)
    {
        $this->_maxLargeDesktopColumns = $columns;
    }



    /**
     * __method_getMaxPhoneColumns_description__
     * @return __return_getMaxPhoneColumns_type__ __return_getMaxPhoneColumns_description__
     */
    public function getMaxPhoneColumns()
    {
        if ($this->_maxPhoneColumns === null) {
            return 12;
        }

        return $this->_maxPhoneColumns;
    }

    /**
     * __method_getMaxTabletColumns_description__
     * @return __return_getMaxTabletColumns_type__ __return_getMaxTabletColumns_description__
     */
    public function getMaxTabletColumns()
    {
        if ($this->_maxTabletColumns === null) {
            return 12;
        }

        return $this->_maxTabletColumns;
    }

    /**
     * __method_getMaxMediumDesktopColumns_description__
     * @return __return_getMaxMediumDesktopColumns_type__ __return_getMaxMediumDesktopColumns_description__
     */
    public function getMaxMediumDesktopColumns()
    {
        if ($this->_maxMediumDesktopColumns === null) {
            return 12;
        }

        return $this->_maxMediumDesktopColumns;
    }

    /**
     * __method_getMaxLargeDesktopColumns_description__
     * @return __return_getMaxLargeDesktopColumns_type__ __return_getMaxLargeDesktopColumns_description__
     */
    public function getMaxLargeDesktopColumns()
    {
        if ($this->_maxLargeDesktopColumns === null) {
            return 12;
        }

        return $this->_maxLargeDesktopColumns;
    }

    /**
     * __method_getPhoneColumns_description__
     * @return __return_getPhoneColumns_type__ __return_getPhoneColumns_description__
     */
    public function getPhoneColumns()
    {
        if (!$this->hasPhoneColumns) {
            return 12;
        }

        return $this->_phoneColumns;
    }

    /**
     * __method_getTabletColumns_description__
     * @return __return_getTabletColumns_type__ __return_getTabletColumns_description__
     */
    public function getTabletColumns()
    {
        if (!$this->hasTabletColumns) {
            return 6;
        }

        return $this->_tabletColumns;
    }

    /**
     * __method_getMediumDesktopColumns_description__
     * @return __return_getMediumDesktopColumns_type__ __return_getMediumDesktopColumns_description__
     */
    public function getMediumDesktopColumns()
    {
        if (!$this->hasMediumDesktopColumns) {
            return 6;
        }

        return $this->_mediumDesktopColumns;
    }

    /**
     * __method_getLargeDesktopColumns_description__
     * @return __return_getLargeDesktopColumns_type__ __return_getLargeDesktopColumns_description__
     */
    public function getLargeDesktopColumns()
    {
        if (!$this->hasLargeDesktopColumns) {
            return 6;
        }

        return $this->_largeDesktopColumns;
    }

    /**
     * __method_getHasPhoneColumns_description__
     * @return __return_getHasPhoneColumns_type__ __return_getHasPhoneColumns_description__
     */
    public function getHasPhoneColumns()
    {
        return $this->_phoneColumns !== null;
    }

    /**
     * __method_getHasTabletColumns_description__
     * @return __return_getHasTabletColumns_type__ __return_getHasTabletColumns_description__
     */
    public function getHasTabletColumns()
    {
        return $this->_tabletColumns !== null;
    }

    /**
     * __method_getHasMediumDesktopColumns_description__
     * @return __return_getHasMediumDesktopColumns_type__ __return_getHasMediumDesktopColumns_description__
     */
    public function getHasMediumDesktopColumns()
    {
        return $this->_mediumDesktopColumns !== null;
    }

    /**
     * __method_getHasLargeDesktopColumns_description__
     * @return __return_getHasLargeDesktopColumns_type__ __return_getHasLargeDesktopColumns_description__
     */
    public function getHasLargeDesktopColumns()
    {
        return $this->_largeDesktopColumns !== null;
    }

    /**
     * __method_getFlex_description__
     * @param __param_size_type__ $size __param_size_description__ [optional]
     * @return __return_getFlex_type__ __return_getFlex_description__
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
