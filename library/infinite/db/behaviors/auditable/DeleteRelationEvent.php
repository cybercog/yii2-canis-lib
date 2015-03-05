<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * DeleteRelationEvent [[@doctodo class_description:infinite\db\behaviors\auditable\DeleteRelationEvent]].
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

    /**
     * @inheritdoc
     */
    public function getVerb()
    {
        return new \infinite\base\language\Verb('unlink');
    }

    /**
     * @inheritdoc
     */
    public function getIndirectConnector()
    {
        return 'and';
    }
}
