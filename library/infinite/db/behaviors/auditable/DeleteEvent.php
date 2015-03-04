<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * DeleteEvent [@doctodo write class description for DeleteEvent].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class DeleteEvent extends AttributesEvent
{
    /**
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

    public function getIndirectConnector()
    {
        return 'from';
    }

    public function getStory()
    {
        return '{{agent}} ' . $this->verb->past . ' [[' . $this->descriptor . ']]' . $this->indirectStory;
    }
}
