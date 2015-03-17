<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db\behaviors\auditable;

/**
 * AttributesEvent [[@doctodo class_description:canis\db\behaviors\auditable\AttributesEvent]].
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
