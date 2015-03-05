<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\grid;

use infinite\helpers\Html;

/**
 * Row [[@doctodo class_description:infinite\web\grid\Row]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Row extends \infinite\base\Object
{
    const TOTAL_COLUMNS = 12;

    /**
     * @var [[@doctodo var_type:_trueWidth]] [[@doctodo var_description:_trueWidth]]
     */
    protected $_trueWidth;

    /**
     * @var [[@doctodo var_type:htmlOptions]] [[@doctodo var_description:htmlOptions]]
     */
    public $htmlOptions = ['class' => 'row'];
    /**
     * @var [[@doctodo var_type:_cells]] [[@doctodo var_description:_cells]]
     */
    protected $_cells = [];
    /**
     * @var [[@doctodo var_type:_fillAttempted]] [[@doctodo var_description:_fillAttempted]]
     */
    protected $_fillAttempted = false;

    /**
     * [[@doctodo method_description:output]].
     */
    public function output()
    {
        echo $this->generate();
    }

    /**
     * [[@doctodo method_description:generate]].
     *
     * @return [[@doctodo return_type:generate]] [[@doctodo return_description:generate]]
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
     * [[@doctodo method_description:fill]].
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
                if ($toFill <= 0) {
                    continue;
                }
                foreach ($this->getColumnFlex($size) as $columnId => $flex) {
                    if ($toFill <= 0) {
                        break;
                    }
                    if (empty($flex)) {
                        continue;
                    }

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
     * Get column flex.
     *
     * @param string $size [[@doctodo param_description:size]] [optional]
     *
     * @return [[@doctodo return_type:getColumnFlex]] [[@doctodo return_description:getColumnFlex]]
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
     * Get distribution columns.
     *
     * @return [[@doctodo return_type:getDistributionColumns]] [[@doctodo return_description:getDistributionColumns]]
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
     * [[@doctodo method_description:isFilled]].
     *
     * @return [[@doctodo return_type:isFilled]] [[@doctodo return_description:isFilled]]
     */
    public function isFilled()
    {
        return $this->columnCount === $this->trueWidth;
    }

    /**
     * Get column count.
     *
     * @return [[@doctodo return_type:getColumnCount]] [[@doctodo return_description:getColumnCount]]
     */
    public function getColumnCount()
    {
        $columnCount = 0;
        foreach ($this->_cells as $item) {
            if ($item->columns === 'auto') {
                continue;
            }
            $columnCount += $item->columns;
        }

        return $columnCount;
    }

    /**
     * [[@doctodo method_description:hasRoom]].
     *
     * @return [[@doctodo return_type:hasRoom]] [[@doctodo return_description:hasRoom]]
     */
    public function hasRoom($additional)
    {
        if ($this->columnCount + $additional > $this->trueWidth) {
            return false;
        }

        return true;
    }

    /**
     * [[@doctodo method_description:addCell]].
     *
     * @param infinite\web\grid\Cell $item  [[@doctodo param_description:item]]
     * @param boolean                $check [[@doctodo param_description:check]] [optional]
     *
     * @return [[@doctodo return_type:addCell]] [[@doctodo return_description:addCell]]
     */
    public function addCell(Cell $item, $check = false)
    {
        if (!$check || $this->hasRoom($item->columns)) {
            $this->_cells[$item->id] = $item;

            return $item;
        }

        return false;
    }

    /**
     * [[@doctodo method_description:addCells]].
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
     * Set cells.
     */
    public function setCells($cells)
    {
        foreach ($cells as $cell) {
            $this->addCell($cell, false);
        }
    }

    /**
     * Get true width.
     *
     * @return [[@doctodo return_type:getTrueWidth]] [[@doctodo return_description:getTrueWidth]]
     */
    public function getTrueWidth()
    {
        if (is_null($this->_trueWidth)) {
            return self::TOTAL_COLUMNS;
        }

        return $this->_trueWidth;
    }

    /**
     * Set true width.
     */
    public function setTrueWidth($width)
    {
        $this->_trueWidth = $width;
    }
}
