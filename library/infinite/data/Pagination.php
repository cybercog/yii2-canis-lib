<?php
namespace infinite\data;

class Pagination extends \yii\data\Pagination
{
    protected $_state;

    public function createUrl($page, $absolute = false)
    {
        return '#';
    }
}
