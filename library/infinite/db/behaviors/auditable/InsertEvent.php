<?php
namespace infinite\db\behaviors\auditable;

class InsertEvent extends AttributesEvent
{
    protected $_id = 'insert';
	public $attributes;
}