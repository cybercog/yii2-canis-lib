<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\browser;

/**
 * Handler [[@doctodo class_description:infinite\web\browser\Handler]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class Handler extends \infinite\base\Object
{
    /**
     * @var [[@doctodo var_type:bundle]] [[@doctodo var_description:bundle]]
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
     *
     * @return [[@doctodo return_type:getInstructions]] [[@doctodo return_description:getInstructions]]
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
     *
     * @return [[@doctodo return_type:getFilterQuery]] [[@doctodo return_description:getFilterQuery]]
     */
    public function getFilterQuery()
    {
        if (!isset($this->bundle)) {
            return false;
        }

        return $this->bundle->filterQuery;
    }
}
