<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * UpdateEvent [@doctodo write class description for UpdateEvent].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class UpdateEvent extends RelationEvent
{
    /**
     * @inheritdoc
     */
    protected $_id = 'update';
    /**
     * @inheritdoc
     */
    public $attributes;

    public function getVerb()
    {
        return new \infinite\base\language\Verb('update');
    }

    public function getIndirectConnector()
    {
        return 'in';
    }
}
