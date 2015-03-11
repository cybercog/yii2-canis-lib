<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\behaviors\auditable;

/**
 * DeleteRelationEvent [[@doctodo class_description:teal\db\behaviors\auditable\DeleteRelationEvent]].
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
        return new \teal\base\language\Verb('unlink');
    }

    /**
     * @inheritdoc
     */
    public function getIndirectConnector()
    {
        return 'and';
    }
}
