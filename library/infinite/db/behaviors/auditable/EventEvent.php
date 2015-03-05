<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

use Yii;

/**
 * EventEvent [[@doctodo class_description:infinite\db\behaviors\auditable\EventEvent]].
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
