<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\behaviors\auditable;

/**
 * AttributesEvent [[@doctodo class_description:teal\db\behaviors\auditable\AttributesEvent]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class AttributesEvent extends Event
{
    /**
     * @var [[@doctodo var_type:attributes]] [[@doctodo var_description:attributes]]
     */
    public $attributes;

    /**
     * @inheritdoc
     */
    public function getHashArray()
    {
        $hash = parent::getHashArray();
        $attributes = $this->attributes;
        ksort($attributes);
        $hash['attributes'] = $attributes;

        return $hash;
    }
}
