<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\browser;

/**
 * Handler [@doctodo write class description for Handler]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class Handler extends \infinite\base\Object
{
    /**
     * @var __var_bundle_type__ __var_bundle_description__
     */
    public $bundle;
    /**
     * Get total
     */
    abstract public function getTotal();
    /**
     * Get items
     */
    abstract public function getItems();

    /**
     * Get instructions
     * @return __return_getInstructions_type__ __return_getInstructions_description__
     */
    public function getInstructions()
    {
        if (!isset($this->bundle)) {
            return false;
        }

        return $this->bundle->instructions;
    }

    /**
     * Get filter query
     * @return __return_getFilterQuery_type__ __return_getFilterQuery_description__
     */
    public function getFilterQuery()
    {
        if (!isset($this->bundle)) {
            return false;
        }

        return $this->bundle->filterQuery;
    }
}
