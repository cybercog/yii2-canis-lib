<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\data;

/**
 * Sort [[@doctodo class_description:canis\data\Sort]].
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
