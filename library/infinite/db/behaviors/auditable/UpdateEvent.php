<?php
namespace infinite\db\behaviors\auditable;

class UpdateEvent extends AttributesEvent
{
    protected $_id = 'update';
    public $attributes;
}
