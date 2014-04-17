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
**/
abstract class Handler extends \infinite\base\Object
{
    /**
     * @var __var_bundle_type__ __var_bundle_description__
     */
    public $bundle;
    /**
     * __method_getTotal_description__
     */
    abstract public function getTotal();
    /**
     * __method_getItems_description__
     */
    abstract public function getItems();

    /**
     * __method_getInstructions_description__
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
     * __method_getFilterQuery_description__
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
