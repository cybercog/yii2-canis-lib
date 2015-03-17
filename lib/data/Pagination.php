<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\data;

/**
 * Pagination [[@doctodo class_description:canis\data\Pagination]].
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
