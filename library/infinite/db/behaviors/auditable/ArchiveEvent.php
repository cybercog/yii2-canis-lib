<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * ArchiveEvent [@doctodo write class description for DeleteEvent].
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
     */
    protected $_exclusive = true;
}
