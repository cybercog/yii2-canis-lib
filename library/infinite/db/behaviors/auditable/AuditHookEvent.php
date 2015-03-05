<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

use yii\base\Event as YiiEvent;

/**
 * AuditHookEvent [[@doctodo class_description:infinite\db\behaviors\auditable\AuditHookEvent]].
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
