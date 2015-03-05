<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * UnarchiveEvent [[@doctodo class_description:infinite\db\behaviors\auditable\UnarchiveEvent]].
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
