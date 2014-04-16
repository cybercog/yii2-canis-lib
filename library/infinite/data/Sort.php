<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\data;

class Sort extends \yii\data\Sort
{
    public $sortOrders = [];

    public function getAttributeOrders($recalculate = false)
    {
        return $this->sortOrders;
    }
}
