<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\behaviors\auditable;

/**
 * UnarchiveEvent [[@doctodo class_description:teal\db\behaviors\auditable\UnarchiveEvent]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class UnarchiveEvent extends UpdateEvent
{
    /**
     * @inheritdoc
     */
    protected $_id = 'unarchive';
    /**
     * @var [[@doctodo var_type:_exclusive]] [[@doctodo var_description:_exclusive]]
     */
    protected $_exclusive = true;
}
