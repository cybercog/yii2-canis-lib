<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\behaviors\auditable;

/**
 * DeleteEvent [[@doctodo class_description:teal\db\behaviors\auditable\DeleteEvent]].
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
        return new \teal\base\language\Verb('delete');
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
