<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\behaviors\auditable;

/**
 * CreateEvent [[@doctodo class_description:teal\db\behaviors\auditable\CreateEvent]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class CreateEvent extends AttributesEvent
{
    /**
     * @inheritdoc
     */
    protected $_id = 'create';
    /**
     * @inheritdoc
     */
    public $attributes;

    /**
     * @inheritdoc
     */
    public function getVerb()
    {
        if (isset($this->indirectObject)) {
            return new \teal\base\language\Verb('add');
        }

        return new \teal\base\language\Verb('create');
    }
}
