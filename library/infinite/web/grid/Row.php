<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\grid;

use infinite\helpers\Html;

/**
 * Row [@doctodo write class description for Row]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Row extends \infinite\base\Object
{
    const TOTAL_COLUMNS = 12;

    /**
     * @var __var_htmlOptions_type__ __var_htmlOptions_description__
     */
    public $htmlOptions = ['class' => 'row'];
    /**
     * @var __var__cells_type__ __var__cells_description__
     */
    protected $_cells = [];
    /**
     * @var __var__fillAttempted_type__ __var__fillAttempted_description__
     */
    protected $_fillAttempted = false;

    /**
     * __method_output_description__
     */
    public function output()
    {
        echo $this->generate();
    }

    /**
     * __method_generate_description__
     * @return __return_generate_type__ __return_generate_description__
     */
    public function generate()
    {
        $this->fill();
        $content = [];
        foreach ($this->_cells as $item) {
            $content[] = $item->generate();
        }
        //return implode('', $content);
        return Html::tag('div', implode('', $content), $this->htmlOptions);
    }

    /**
     * __method_fill_description__
     */
    public function fill()
    {
        if (!$this->_fillAttempted) {
            $fillSizes = ['phone' => self::TOTAL_COLUMNS, 'tablet' => self::TOTAL_COLUMNS, 'mediumDesktop' => self::TOTAL_COLUMNS, 'largeDesktop' => self::TOTAL_COLUMNS];

            foreach ($this->_cells as $cell) {
                $sizes = $cell->sizes;
                foreach ($fillSizes as $size => $left) {
                    if (!isset($sizes[$size])) {
                        unset($fillSizes[$size]);
                    } elseif ($sizes[$size] !== 'auto') {
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

    /**
     * Get column flex
     * @param string $size __param_size_description__ [optional]
     * @return __return_getColumnFlex_type__ __return_getColumnFlex_description__
     */
    public function getColumnFlex($size = 'phone')
    {
        $flex = [];
        foreach ($this->_cells as $column) {
            $flex[$column->id] = $column->getFlex($size);
        }
        arsort($flex, SORT_NUMERIC);

        return $flex;
    }

    /**
     * Get distribution columns
     * @param __param_size_type__ $size __param_size_description__ [optional]
     * @return __return_getDistributionColumns_type__ __return_getDistributionColumns_description__
     */
    public function getDistributionColumns($size = null)
    {
        $auto = [];
        foreach ($this->_cells as $cell) {
            if ($cell->getColumns($size) === 'auto') {
                $auto[$cell->id] = $cell;
            }
        }

        return $auto;
    }

    /**
     * __method_isFilled_description__
     * @return __return_isFilled_type__ __return_isFilled_description__
     */
    public function isFilled()
    {
        return $this->columnCount === self::TOTAL_COLUMNS;
    }

    /**
     * Get column count
     * @return __return_getColumnCount_type__ __return_getColumnCount_description__
     */
    public function getColumnCount()
    {
        $columnCount = 0;
        foreach ($this->_cells as $item) {
            if ($item->columns === 'auto') { continue; }
            $columnCount += $item->columns;
        }

        return $columnCount;
    }

    /**
     * __method_hasRoom_description__
     * @param __param_additional_type__ $additional __param_additional_description__
     * @return __return_hasRoom_type__ __return_hasRoom_description__
     */
    public function hasRoom($additional)
    {
        if ($this->columnCount + $additional > self::TOTAL_COLUMNS) {
            return false;
        }

        return true;
    }

    /**
     * __method_addCell_description__
     * @param infinite\web\grid\Cell $item __param_item_description__
     * @param boolean $check __param_check_description__ [optional]
     * @return __return_addCell_type__ __return_addCell_description__
     */
    public function addCell(Cell $item, $check = false)
    {
        if (!$check || $this->hasRoom($item->columns)) {
            $this->_cells[$item->id] = $item;

            return true;
        }

        return false;
    }

    /**
     * __method_addCells_description__
     * @param __param_items_type__ $items __param_items_description__
     */
    public function addCells(&$items)
    {
        foreach ($items as $ikey => $item) {
            if ($this->addCell($item, true)) {
                unset($items[$ikey]);
            } else {
                break;
            }
        }
    }

    /**
     * Set cells
     * @param __param_cells_type__ $cells __param_cells_description__
     */
    public function setCells($cells)
    {
        foreach ($cells as $cell) {
            $this->addCell($cell, false);
        }
    }
}
