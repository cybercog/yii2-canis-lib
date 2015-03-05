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
 * ActiveArchivable [[@doctodo class_description:infinite\db\behaviors\ActiveArchivable]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ActiveArchivable extends ActiveRecord
{
    /**
     * @var [[@doctodo var_type:archiveUserField]] [[@doctodo var_description:archiveUserField]]
     */
    public $archiveUserField = 'archived_user_id';
    /**
     * @var [[@doctodo var_type:archiveField]] [[@doctodo var_description:archiveField]]
     */
    public $archiveField = 'archived';
    /**
     * @var [[@doctodo var_type:databaseTimeFormat]] [[@doctodo var_description:databaseTimeFormat]]
     */
    public $databaseTimeFormat = 'Y-m-d H:i:s';
    /**
     * @var [[@doctodo var_type:_isArchivable]] [[@doctodo var_description:_isArchivable]]
     */
    protected $_isArchivable;
    /**
     * @var [[@doctodo var_type:_trackUserArchive]] [[@doctodo var_description:_trackUserArchive]]
     */
    protected $_trackUserArchive;
    /**
     * @var [[@doctodo var_type:_userID]] [[@doctodo var_description:_userID]]
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
     * [[@doctodo method_description:isArchivable]].
     *
     * @return [[@doctodo return_type:isArchivable]] [[@doctodo return_description:isArchivable]]
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
     *
     * @return [[@doctodo return_type:getArchived]] [[@doctodo return_description:getArchived]]
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
     * [[@doctodo method_description:trackUserArchive]].
     *
     * @return [[@doctodo return_type:trackUserArchive]] [[@doctodo return_description:trackUserArchive]]
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
     * [[@doctodo method_description:archive]].
     *
     * @param yii\base\ModelEvent $event          [[@doctodo param_description:event]] [optional]
     * @param array               $baseAuditEvent [[@doctodo param_description:baseAuditEvent]] [optional]
     *
     * @return [[@doctodo return_type:archive]] [[@doctodo return_description:archive]]
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
     * [[@doctodo method_description:unarchive]].
     *
     * @param yii\base\ModelEvent $event          [[@doctodo param_description:event]] [optional]
     * @param array               $baseAuditEvent [[@doctodo param_description:baseAuditEvent]] [optional]
     *
     * @return [[@doctodo return_type:unarchive]] [[@doctodo return_description:unarchive]]
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
     * [[@doctodo method_description:_getUserId]].
     *
     * @return [[@doctodo return_type:_getUserId]] [[@doctodo return_description:_getUserId]]
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

    /**
     * [[@doctodo method_description:registerArchiveAuditEvent]].
     *
     * @param array $base [[@doctodo param_description:base]] [optional]
     *
     * @return [[@doctodo return_type:registerArchiveAuditEvent]] [[@doctodo return_description:registerArchiveAuditEvent]]
     */
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

    /**
     * [[@doctodo method_description:registerUnarchiveAuditEvent]].
     *
     * @param array $base [[@doctodo param_description:base]] [optional]
     *
     * @return [[@doctodo return_type:registerUnarchiveAuditEvent]] [[@doctodo return_description:registerUnarchiveAuditEvent]]
     */
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

    /**
     * [[@doctodo method_description:registerArchivableAuditEvent]].
     *
     * @param array $base [[@doctodo param_description:base]] [optional]
     *
     * @return [[@doctodo return_type:registerArchivableAuditEvent]] [[@doctodo return_description:registerArchivableAuditEvent]]
     */
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
