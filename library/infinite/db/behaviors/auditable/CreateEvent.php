<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * CreateEvent [[@doctodo class_description:infinite\db\behaviors\auditable\CreateEvent]].
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
            return new \infinite\base\language\Verb('add');
        }

        return new \infinite\base\language\Verb('create');
    }
}
