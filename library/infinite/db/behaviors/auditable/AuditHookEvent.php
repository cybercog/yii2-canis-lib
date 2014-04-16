<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

use yii\base\Event as YiiEvent;

class AuditHookEvent extends YiiEvent
{
    public $auditEvent;
    public $isValid = true;
}
