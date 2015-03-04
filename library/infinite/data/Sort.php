<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\data;

/**
 * Sort [@doctodo write class description for Sort].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Sort extends \yii\data\Sort
{
    /**
     * @var __var_sortOrders_type__ __var_sortOrders_description__
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
