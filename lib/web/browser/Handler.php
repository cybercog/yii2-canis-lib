<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\web\browser;

/**
 * Handler [[@doctodo class_description:canis\web\browser\Handler]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class Handler extends \canis\base\Object
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
