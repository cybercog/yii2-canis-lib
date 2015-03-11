<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\behaviors\auditable;

use Yii;

/**
 * EventEvent [[@doctodo class_description:teal\db\behaviors\auditable\EventEvent]].
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
