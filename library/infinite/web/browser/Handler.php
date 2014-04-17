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
    public $bundle;
    abstract public function getTotal();
    abstract public function getItems();

    public function getInstructions()
    {
        if (!isset($this->bundle)) {
            return false;
        }

        return $this->bundle->instructions;
    }

    public function getFilterQuery()
    {
        if (!isset($this->bundle)) {
            return false;
        }

        return $this->bundle->filterQuery;
    }
}
