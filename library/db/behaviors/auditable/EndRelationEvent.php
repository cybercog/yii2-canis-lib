<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\behaviors\auditable;

/**
 * EndRelationEvent [[@doctodo class_description:teal\db\behaviors\auditable\EndRelationEvent]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class EndRelationEvent extends RelationEvent
{
    /**
     * @inheritdoc
     */
    public $saveOnRegister = true;
    /**
     * @inheritdoc
     */
    protected $_id = 'end_relation';

    /**
     * @inheritdoc
     */
    public function getVerb()
    {
        return new \teal\base\language\Verb('end');
    }

    /**
     * @inheritdoc
     */
    public function getStory()
    {
        return '{{agent}} ' . $this->verb->past . ' link with {{directObject}}' . $this->indirectStory;
    }
}
