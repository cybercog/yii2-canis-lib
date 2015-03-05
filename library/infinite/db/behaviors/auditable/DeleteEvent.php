<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * DeleteEvent [[@doctodo class_description:infinite\db\behaviors\auditable\DeleteEvent]].
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
        return new \infinite\base\language\Verb('delete');
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
