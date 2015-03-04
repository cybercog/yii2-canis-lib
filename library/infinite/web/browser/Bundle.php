<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\browser;

use infinite\base\exceptions\Exception;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Bundle [@doctodo write class description for Bundle].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Bundle extends \infinite\base\Object
{
    /**
     */
    public $itemClass = 'infinite\web\browser\Item';
    /**
     */
    public $limit = 30;
    /**
     */
    protected $_id; // never set, based on instructions
    /**
     */
    protected $_instructions;
    /**
     */
    protected $_filterQuery = false;
    /**
     */
    protected $_type; // pivot: category list; item: items list
    /**
     */
    protected $_typeOptions = [];
    /**
     */
    protected $_items = [];
    /**
     */
    protected $_handled = false;
    /**
     */
    protected $_total;
    /**
     */
    protected $_offset = 0;
    /**
     */
    protected $_baseInstructions = ['task' => null];

    /**
     * Get id.
     */
    public function getId()
    {
        if (is_null($this->_id)) {
            $this->_id = md5(serialize($this->instructions));
        }

        return $this->_id;
    }

    /**
     * Get instructions.
     */
    public function getInstructions()
    {
        if (is_null($this->_instructions)) {
            throw new InvalidConfigException('Browser response bundle requires instructions');
        }

        return $this->_instructions;
    }

    /**
     * Set instructions.
     */
    public function setInstructions($instructions)
    {
        if (!is_null($this->_instructions)) {
            throw new Exception('Instructions for browser responses can only be set once.');
        }
        $this->_instructions = array_merge($this->_baseInstructions, $instructions);
        if (isset($this->_instructions['id'])) {
            $this->_id = $this->_instructions['id'];
        }
        if (isset($this->_instructions['offset'])) {
            $this->_offset = $this->_instructions['offset'];
        }

        if (isset($this->_instructions['filterQuery'])) {
            $this->filterQuery = $this->_instructions['filterQuery'];
        }

        unset($this->_instructions['id'], $this->_instructions['offset'], $this->_instructions['filterQuery']);
    }

    /**
     *
     */
    public function addItem($item)
    {
        if (!isset($this->_items)) {
            $this->_items = [];
        }
        if (!is_array($item)) {
            $item = [];
        }
        if (!isset($item['class'])) {
            $item['class'] = $this->itemClass;
        }
        $item = Yii::createObject($item);
        $this->_items[$item->id] = $item;

        return $item;
    }

    /**
     *
     */
    public function package()
    {
        $package = [];
        $package['id'] = $this->id;
        $package['instructions'] = $this->instructions;
        $package['filterQuery'] = $this->filterQuery;
        $package['type'] = $this->type;
        $package['typeOptions'] = $this->typeOptions;
        $package['total'] = $this->total;
        $package['bundle'] = false;
        $package['bundle'] = ['offset' => $this->offset, 'size' => count($this->_items), 'items' => []];
        foreach ($this->_items as $item) {
            $package['bundle']['items'][$item->id] = $item->package();
        }

        return $package;
    }

    /**
     * Set filter query.
     */
    public function setFilterQuery($value)
    {
        if ($value === 'false') {
            $value = false;
        }
        $this->_filterQuery = $value;
    }

    /**
     * Get filter query.
     */
    public function getFilterQuery()
    {
        return $this->_filterQuery;
    }

    /**
     * Set type options.
     */
    public function setTypeOptions($options)
    {
        $this->_typeOptions = array_merge($this->_typeOptions, $options);
    }

    /**
     * Get type options.
     */
    public function getTypeOptions()
    {
        return $this->_typeOptions;
    }

    /**
     * Set type.
     */
    public function setType($type)
    {
        $acceptableTypes = ['pivot', 'item'];
        if (!in_array($type, $acceptableTypes)) {
            throw new InvalidConfigException('Browser response bundle must be one of the following types: ' . implode(', ', $acceptableTypes));
        }
        $this->_type = $type;
    }

    /**
     * Get type.
     */
    public function getType()
    {
        if (is_null($this->_type)) {
            $this->type = 'item';
        }

        return $this->_type;
    }

    /**
     *
     */
    public function predictTotal()
    {
        if ($this->handler) {
            return $this->handler->total;
        }

        return false;
    }

    /**
     * Get total.
     */
    public function getTotal()
    {
        if (is_null($this->_total)) {
            if (($total = $this->predictTotal())) {
                $this->_total = $total;
            } elseif (isset($this->_items)) {
                return count($this->_items);
            } else {
                $this->_total = false;
            }
        }

        return $this->_total;
    }

    /**
     * Set total.
     */
    public function setTotal($total)
    {
        $this->_total = $total;
    }

    /**
     * Get offset.
     */
    public function getOffset()
    {
        return $this->_offset;
    }

    /**
     * Set offset.
     */
    public function setOffset($offset)
    {
        $this->_offset = $offset;
    }

    /**
     * Get handlers.
     */
    public function getHandlers()
    {
        return [];
    }

    /**
     * Get handler.
     */
    public function getHandler()
    {
        $handlers = $this->handlers;
        if (isset($this->instructions['handler'])
            && isset($handlers[$this->instructions['handler']])) {
            return Yii::createObject([
                'class' => $handlers[$this->instructions['handler']],
                'bundle' => $this,
            ]);
        }

        return false;
    }

    /**
     *
     */
    public function handle()
    {
        if ($this->_handled) {
            return true;
        }
        $handler = $this->handler;
        if (!$handler) {
            return false;
        }
        $items = $handler->items;
        if ($items === false) {
            return false;
        }
        foreach ($items as $item) {
            $this->addItem($item);
        }
        $this->_handled = true;
    }
}
