<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db\behaviors\auditable;

use Yii;

/**
 * EventEvent [[@doctodo class_description:canis\db\behaviors\auditable\EventEvent]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class EventEvent extends \yii\base\Event
{
    /**
     * @var [[@doctodo var_type:model]] [[@doctodo var_description:model]]
     */
    public $model;
}
