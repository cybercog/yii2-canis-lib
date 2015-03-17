<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db\behaviors\auditable;

/**
 * DeleteEvent [[@doctodo class_description:canis\db\behaviors\auditable\DeleteEvent]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class DeleteEvent extends AttributesEvent
{
    /**
     * @var [[@doctodo var_type:descriptor]] [[@doctodo var_description:descriptor]]
     */
    public $descriptor;
    /**
     * @inheritdoc
     */
    public $handleHooksOnCreate = true;

    /**
     * @inheritdoc
     */
    protected $_id = 'delete';

    /**
     * @inheritdoc
     */
    public function getVerb()
    {
        return new \canis\base\language\Verb('delete');
    }

    /**
     * @inheritdoc
     */
    public function setDirectObject($object)
    {
        $this->descriptor = $object->descriptor;
    }

    /**
     * @inheritdoc
     */
    public function getIndirectConnector()
    {
        return 'from';
    }

    /**
     * @inheritdoc
     */
    public function getStory()
    {
        return '{{agent}} ' . $this->verb->past . ' [[' . $this->descriptor . ']]' . $this->indirectStory;
    }
}
