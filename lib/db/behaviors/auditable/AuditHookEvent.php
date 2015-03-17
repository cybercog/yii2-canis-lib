<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db\behaviors\auditable;

use yii\base\Event as YiiEvent;

/**
 * AuditHookEvent [[@doctodo class_description:canis\db\behaviors\auditable\AuditHookEvent]].
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
