<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\data;

/**
 * Sort [[@doctodo class_description:teal\data\Sort]].
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
