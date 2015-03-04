<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * UnarchiveEvent [@doctodo write class description for DeleteEvent].
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
     */
    protected $_exclusive = true;
}
