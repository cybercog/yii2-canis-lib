<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors;

use Yii;
use yii\base\ModelEvent;

/**
 * ActiveArchivable [@doctodo write class description for ActiveArchivable]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ActiveArchivable extends ActiveRecord
{
    /**
     * @var __var_archiveUserField_type__ __var_archiveUserField_description__
     */
    public $archiveUserField = 'archived_user_id';
    /**
     * @var __var_archiveField_type__ __var_archiveField_description__
     */
    public $archiveField = 'archived';
    /**
     * @var __var_databaseTimeFormat_type__ __var_databaseTimeFormat_description__
     */
    public $databaseTimeFormat = 'Y-m-d H:i:s';
    /**
     * @var __var__isArchivable_type__ __var__isArchivable_description__
     */
    protected $_isArchivable;
    /**
     * @var __var__trackUserArchive_type__ __var__trackUserArchive_description__
     */
    protected $_trackUserArchive;
    /**
     * @var __var__userID_type__ __var__userID_description__
     */
    public static $_userID;

    /**
     * __method_isArchivable_description__
     * @return __return_isArchivable_type__ __return_isArchivable_description__
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
     * Get archived
     * @return __return_getArchived_type__ __return_getArchived_description__
     */
    public function getArchived()
    {
        if (!$this->isArchivable()) { return false; }
        if (empty($this->owner->{$this->archiveField})) {
            return false;
        }

        return true;
    }

    /**
     * __method_trackUserArchive_description__
     * @return __return_trackUserArchive_type__ __return_trackUserArchive_description__
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
     * __method_archive_description__
     * @param yii\base\ModelEvent $event __param_event_description__ [optional]
     * @return __return_archive_type__ __return_archive_description__
     */
    public function archive(ModelEvent $event = null)
    {
        if (is_null($event)) { $event = new ModelEvent; }
        if (!$this->isArchivable()) { $event->isValid = false; return false; }

        $nowDate = date($this->databaseTimeFormat);
        $this->owner->{$this->archiveField} = $nowDate;
        if ($this->trackUserArchive()) {
            $this->owner->archiveUserField = self::_getUserId();
        }

        return $this->owner->save();
    }

    /**
     * __method_unarchive_description__
     * @param yii\base\ModelEvent $event __param_event_description__ [optional]
     * @return __return_unarchive_type__ __return_unarchive_description__
     */
    public function unarchive(ModelEvent $event = null)
    {
        if (is_null($event)) { $event = new ModelEvent; }
        if (!$this->isArchivable()) { $event->isValid = false; return false; }

        $this->owner->{$this->archiveField} = null;
        if ($this->trackUserArchive()) {
            $this->owner->archiveUserField = null;
        }

        return $this->owner->save();
    }

    /**
     * __method__getUserId_description__
     * @return __return__getUserId_type__ __return__getUserId_description__
     */
    protected static function _getUserId()
    {
        if (is_null(self::$_userID)) {
            self::$_userID = null;
            if (isset(Yii::$app->user) AND !empty(Yii::$app->user->id)) {
                self::$_userID = Yii::$app->user->id;
            }
        }

        return self::$_userID;
    }
}
