<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * DeleteEvent [@doctodo write class description for DeleteEvent]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class DeleteEvent extends AttributesEvent
{
    public $descriptor;
    public $handleHooksOnCreate = true;

    protected $_id = 'delete';

    /**
    * @inheritdoc
    **/
    public function setDirectObject($object)
    {
        $this->descriptor = $object->descriptor;
    }

    /**
    * @inheritdoc
    **/
    public function setIndirectObject($object)
    {
        parent::setDirectObject($object);
    }
}
