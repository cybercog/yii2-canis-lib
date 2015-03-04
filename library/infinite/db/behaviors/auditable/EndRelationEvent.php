<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * EndRelationEvent [@doctodo write class description for EndRelationEvent].
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

    public function getVerb()
    {
        return new \infinite\base\language\Verb('end');
    }

    public function getStory()
    {
        return '{{agent}} ' . $this->verb->past . ' link with {{directObject}}' . $this->indirectStory;
    }
}
