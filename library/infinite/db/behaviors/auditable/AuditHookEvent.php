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
 * AuditHookEvent [@doctodo write class description for AuditHookEvent].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class AuditHookEvent extends YiiEvent
{
    /**
     */
    public $auditEvent;
    /**
     */
    public $isValid = true;
}
