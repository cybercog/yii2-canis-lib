<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\behaviors\auditable;

/**
 * CreateRelationEvent [[@doctodo class_description:teal\db\behaviors\auditable\CreateRelationEvent]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class CreateRelationEvent extends RelationEvent
{
    /**
     * @inheritdoc
     */
    protected $_id = 'create_relation';

    /**
     * @inheritdoc
     */
    public function getVerb()
    {
        return new \teal\base\language\Verb('link');
    }
}
