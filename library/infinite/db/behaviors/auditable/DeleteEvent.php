<?php
namespace infinite\db\behaviors\auditable;

class DeleteEvent extends AttributesEvent
{
	public $descriptor;
    public $handleHooksOnCreate = true;

    protected $_id = 'delete';

	public function setDirectObject($object)
	{
		$this->descriptor = $object->descriptor;
	}

	public function setIndirectObject($object)
	{
		parent::setDirectObject($object);
	}
}