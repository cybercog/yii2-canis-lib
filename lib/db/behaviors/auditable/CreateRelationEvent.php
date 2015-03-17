<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db\behaviors\auditable;

/**
 * CreateRelationEvent [[@doctodo class_description:canis\db\behaviors\auditable\CreateRelationEvent]].
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
        return new \canis\base\language\Verb('link');
    }
}
