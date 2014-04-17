<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\data;

/**
 * Pagination [@doctodo write class description for Pagination]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Pagination extends \yii\data\Pagination
{
    /**
     * @var __var__state_type__ __var__state_description__
     */
    protected $_state;

    /**
    * @inheritdoc
     */
    public function createUrl($page, $absolute = false)
    {
        return '#';
    }
}
