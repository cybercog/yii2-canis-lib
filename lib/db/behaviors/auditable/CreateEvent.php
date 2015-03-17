<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db\behaviors\auditable;

/**
 * CreateEvent [[@doctodo class_description:canis\db\behaviors\auditable\CreateEvent]].
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
            return new \canis\base\language\Verb('add');
        }

        return new \canis\base\language\Verb('create');
    }
}
