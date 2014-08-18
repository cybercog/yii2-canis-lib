<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * DeleteRelationEvent [@doctodo write class description for DeleteRelationEvent]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class DeleteRelationEvent extends RelationEvent
{
    /**
     * @inheritdoc
     */
    public $saveOnRegister = true;
    /**
     * @inheritdoc
     */
    protected $_id = 'delete_relation';

    public function getVerb()
    {
    	return new \infinite\base\language\Verb('disassociate');
    }

    public function getStory()
    {
        return '{{agent}} '. $this->verb->past .' {{directObjectType}} {{directObject}}' . $this->indirectStory;
    }

    public function getIndirectConnector()
    {
        return 'and';
    }
}
