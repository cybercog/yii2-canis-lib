<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\browser;

/**
 * Handler [@doctodo write class description for Handler].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class Handler extends \infinite\base\Object
{
    /**
     */
    public $bundle;
    /**
     * Get total.
     */
    abstract public function getTotal();
    /**
     * Get items.
     */
    abstract public function getItems();

    /**
     * Get instructions.
     */
    public function getInstructions()
    {
        if (!isset($this->bundle)) {
            return false;
        }

        return $this->bundle->instructions;
    }

    /**
     * Get filter query.
     */
    public function getFilterQuery()
    {
        if (!isset($this->bundle)) {
            return false;
        }

        return $this->bundle->filterQuery;
    }
}
