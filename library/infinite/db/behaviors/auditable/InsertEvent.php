<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * InsertEvent [@doctodo write class description for InsertEvent]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class InsertEvent extends AttributesEvent
{
    protected $_id = 'insert';
    public $attributes;
}
