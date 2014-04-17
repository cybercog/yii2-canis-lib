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
**/
class ActiveArchivable extends ActiveRecord
{
    public $archiveUserField = 'archived_user_id';
    public $archiveField = 'archived';
    public $databaseTimeFormat = 'Y-m-d H:i:s';
    protected $_isArchivable;
    protected $_trackUserArchive;
    public static $_userID;

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

    public function getArchived()
    {
        if (!$this->isArchivable()) { return false; }
        if (empty($this->owner->{$this->archiveField})) {
            return false;
        }

        return true;
    }

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

    public function archive(Event $event = null)
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

    public function unarchive(Event $event = null)
    {
        if (is_null($event)) { $event = new ModelEvent; }
        if (!$this->isArchivable()) { $event->isValid = false; return false; }

        $this->owner->{$this->archiveField} = null;
        if ($this->trackUserArchive()) {
            $this->owner->archiveUserField = null;
        }

        return $this->owner->save();
    }

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
