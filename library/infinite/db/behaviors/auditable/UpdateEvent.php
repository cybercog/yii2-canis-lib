<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * UpdateEvent [@doctodo write class description for UpdateEvent]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class UpdateEvent extends AttributesEvent
{
    protected $_id = 'update';
    public $attributes;
}
