<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db\behaviors\auditable;

/**
 * DeleteRelationEvent [[@doctodo class_description:canis\db\behaviors\auditable\DeleteRelationEvent]].
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
        return new \canis\base\language\Verb('unlink');
    }

    /**
     * @inheritdoc
     */
    public function getIndirectConnector()
    {
        return 'and';
    }
}
