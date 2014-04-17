<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

use yii\base\Event as YiiEvent;

/**
 * AuditHookEvent [@doctodo write class description for AuditHookEvent]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class AuditHookEvent extends YiiEvent
{
    /**
     * @var __var_auditEvent_type__ __var_auditEvent_description__
     */
    public $auditEvent;
    /**
     * @var __var_isValid_type__ __var_isValid_description__
     */
    public $isValid = true;
}
