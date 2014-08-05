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
 */
class InsertEvent extends AttributesEvent
{
    /**
     * @inheritdoc
     */
    protected $_id = 'insert';
    /**
     * @inheritdoc
     */
    public $attributes;

    public function getVerb()
    {
    	if (isset($this->indirectObject)) {
    		return new \infinite\base\language\Verb('add');
    	}
    	return new \infinite\base\language\Verb('create');
    }
}
