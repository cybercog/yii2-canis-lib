<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * ArchiveEvent [[@doctodo class_description:infinite\db\behaviors\auditable\ArchiveEvent]].
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
