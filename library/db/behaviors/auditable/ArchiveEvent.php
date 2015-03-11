<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\behaviors\auditable;

/**
 * ArchiveEvent [[@doctodo class_description:teal\db\behaviors\auditable\ArchiveEvent]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ArchiveEvent extends UpdateEvent
{
    /**
     * @inheritdoc
     */
    protected $_id = 'archive';
    /**
     * @var [[@doctodo var_type:_exclusive]] [[@doctodo var_description:_exclusive]]
     */
    protected $_exclusive = true;
}
