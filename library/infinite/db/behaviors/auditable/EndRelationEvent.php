<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * EndRelationEvent [[@doctodo class_description:infinite\db\behaviors\auditable\EndRelationEvent]].
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
        return new \infinite\base\language\Verb('end');
    }

    /**
     * @inheritdoc
     */
    public function getStory()
    {
        return '{{agent}} ' . $this->verb->past . ' link with {{directObject}}' . $this->indirectStory;
    }
}
