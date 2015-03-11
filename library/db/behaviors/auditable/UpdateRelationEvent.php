<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\behaviors\auditable;

/**
 * UpdateRelationEvent [[@doctodo class_description:teal\db\behaviors\auditable\UpdateRelationEvent]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class UpdateRelationEvent extends RelationEvent
{
    /**
     * @inheritdoc
     */
    public $saveOnRegister = true;
    /**
     * @inheritdoc
     */
    protected $_id = 'update_relation';

    /**
     * @inheritdoc
     */
    public function getVerb()
    {
        return new \teal\base\language\Verb('update');
    }

    /**
     * @inheritdoc
     */
    public function getStory()
    {
        return '{{agent}} ' . $this->verb->past . ' link with {{directObject}}' . $this->indirectStory;
    }
}
