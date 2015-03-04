<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors;

use Yii;
use yii\base\ModelEvent;

/**
 * ActiveArchivable [@doctodo write class description for ActiveArchivable].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ActiveArchivable extends ActiveRecord
{
    /**
     */
    public $archiveUserField = 'archived_user_id';
    /**
     */
    public $archiveField = 'archived';
    /**
     */
    public $databaseTimeFormat = 'Y-m-d H:i:s';
    /**
     */
    protected $_isArchivable;
    /**
     */
    protected $_trackUserArchive;
    /**
     */
    public static $_userID;
    /**
     * @var string Audit archive event class
     */
    public $archiveEventClass = 'infinite\db\behaviors\auditable\ArchiveEvent';
    /**
     * @var string Audit unarchive event class
     */
    public $unarchiveEventClass = 'infinite\db\behaviors\auditable\UnarchiveEvent';

    /**
     *
     */
    public function isArchivable()
    {
        if (is_null($this->_isArchivable)) {
            $this->_isArchivable = true;
            $ownerClass = get_class($this->owner);
            $schema = $ownerClass::getTableSchema();

            if (!isset($schema->columns[$this->archiveField])) {
                $this->_isArchivable = false;
            }
        }

        return $this->_isArchivable;
    }

    /**
     * Get archived.
     */
    public function getArchived()
    {
        if (!$this->isArchivable()) {
            return false;
        }
        if (empty($this->owner->{$this->archiveField})) {
            return false;
        }

        return true;
    }

    /**
     *
     */
    public function trackUserArchive()
    {
        if (is_null($this->_trackUserArchive)) {
            $this->_trackUserArchive = true;
            $ownerClass = get_class($this->owner);
            $schema = $ownerClass::getTableSchema();

            if (!isset($schema->columns[$this->archiveUserField])) {
                $this->_trackUserArchive = false;
            }
        }

        return $this->_trackUserArchive;
    }

    /**
     *
     */
    public function archive(ModelEvent $event = null, $baseAuditEvent = [])
    {
        if (is_null($event)) {
            $event = new ModelEvent();
        }
        if (!$this->isArchivable()) {
            $event->isValid = false;

            return false;
        }

        $nowDate = date($this->databaseTimeFormat);
        $this->owner->{$this->archiveField} = $nowDate;
        if ($this->trackUserArchive()) {
            $this->owner->archiveUserField = self::_getUserId();
        }
        $this->registerArchiveAuditEvent($baseAuditEvent);

        return $this->owner->save();
    }

    /**
     *
     */
    public function unarchive(ModelEvent $event = null, $baseAuditEvent = [])
    {
        if (is_null($event)) {
            $event = new ModelEvent();
        }
        if (!$this->isArchivable()) {
            $event->isValid = false;

            return false;
        }

        $this->owner->{$this->archiveField} = null;
        if ($this->trackUserArchive()) {
            $this->owner->archiveUserField = null;
        }
        $this->registerUnarchiveAuditEvent($baseAuditEvent);

        return $this->owner->save();
    }

    /**
     *
     */
    protected static function _getUserId()
    {
        if (is_null(self::$_userID)) {
            self::$_userID = null;
            if (isset(Yii::$app->user) and !empty(Yii::$app->user->id)) {
                self::$_userID = Yii::$app->user->id;
            }
        }

        return self::$_userID;
    }

    public function registerArchiveAuditEvent($base = [])
    {
        if ($this->owner->getBehavior('Auditable') === null) {
            return false;
        }
        if (!isset($base['class'])) {
            $base['class'] = $this->archiveEventClass;
        }

        return $this->registerArchivableAuditEvent($base);
    }

    public function registerUnarchiveAuditEvent($base = [])
    {
        if ($this->owner->getBehavior('Auditable') === null) {
            return false;
        }
        if (!isset($base['class'])) {
            $base['class'] = $this->unarchiveEventClass;
        }

        return $this->registerArchivableAuditEvent($base);
    }

    protected function registerArchivableAuditEvent($base = [])
    {
        if ($this->owner->getBehavior('Auditable') === null) {
            return false;
        }
        $eventLog = $base;
        if (!isset($eventLog['class'])) {
            return false;
        }
        $eventLog['directObject'] = $this->owner;
        $eventLog['indirectObject'] = $this->owner->indirectObject;

        return $this->owner->registerAuditEvent($eventLog);
    }
}
