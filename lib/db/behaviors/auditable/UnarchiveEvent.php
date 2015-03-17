<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db\behaviors\auditable;

/**
 * UnarchiveEvent [[@doctodo class_description:canis\db\behaviors\auditable\UnarchiveEvent]].
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
