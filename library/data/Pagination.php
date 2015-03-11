<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\data;

/**
 * Pagination [[@doctodo class_description:teal\data\Pagination]].
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
