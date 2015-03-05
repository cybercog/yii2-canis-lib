<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\data;

/**
 * Sort [[@doctodo class_description:infinite\data\Sort]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Sort extends \yii\data\Sort
{
    /**
     * @var [[@doctodo var_type:sortOrders]] [[@doctodo var_description:sortOrders]]
     */
    public $sortOrders = [];

    /**
     * @inheritdoc
     */
    public function getAttributeOrders($recalculate = false)
    {
        return $this->sortOrders;
    }
}
