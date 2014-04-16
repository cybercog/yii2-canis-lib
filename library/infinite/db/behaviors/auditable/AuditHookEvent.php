<?php
namespace infinite\db\behaviors\auditable;

use yii\base\Event as YiiEvent;

class AuditHookEvent extends YiiEvent
{
    public $auditEvent;
    public $isValid = true;
}
