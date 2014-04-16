<?php
namespace infinite\data;

class Sort extends \yii\data\Sort
{
    public $sortOrders = [];

    public function getAttributeOrders($recalculate = false)
    {
        return $this->sortOrders;
    }
}
