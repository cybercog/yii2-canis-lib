<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\behaviors\auditable;

use yii\base\Event as YiiEvent;

/**
 * AuditHookEvent [[@doctodo class_description:teal\db\behaviors\auditable\AuditHookEvent]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class AuditHookEvent extends YiiEvent
{
    /**
     * @var [[@doctodo var_type:auditEvent]] [[@doctodo var_description:auditEvent]]
     */
    public $auditEvent;
    /**
     * @var [[@doctodo var_type:isValid]] [[@doctodo var_description:isValid]]
     */
    public $isValid = true;
}
