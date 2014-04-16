<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\data;

class Pagination extends \yii\data\Pagination
{
    protected $_state;

    public function createUrl($page, $absolute = false)
    {
        return '#';
    }
}
