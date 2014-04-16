<?php
namespace infinite\db\behaviors\auditable;

class AttributesEvent extends Event
{
	public $attributes;

	public function getHashArray()
    {
    	$hash = parent::getHashArray();
    	$attributes = $this->attributes;
    	ksort($attributes);
    	$hash['attributes'] = $attributes;
    	return $hash;
    }
}