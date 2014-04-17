<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * UpdateRelationEvent [@doctodo write class description for UpdateRelationEvent]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class UpdateRelationEvent extends AttributesEvent
{
    /**
     * @inheritdoc
     */
    public $saveOnRegister = true;
    /**
     * @inheritdoc
     */
    protected $_id = 'update_relation';
}
