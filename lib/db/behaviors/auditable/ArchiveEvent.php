<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db\behaviors\auditable;

/**
 * ArchiveEvent [[@doctodo class_description:canis\db\behaviors\auditable\ArchiveEvent]].
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
