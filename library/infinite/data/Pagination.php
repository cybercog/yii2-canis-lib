<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\data;

/**
 * Pagination [[@doctodo class_description:infinite\data\Pagination]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Pagination extends \yii\data\Pagination
{
    /**
     * @var [[@doctodo var_type:_state]] [[@doctodo var_description:_state]]
     */
    protected $_state;

    /**
     * @inheritdoc
     */
    public function createUrl($page, $pageSize = null, $absolute = false)
    {
        return '#';
    }
}
