<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors;

use infinite\helpers\Date as DateHelper;

/**
 * Date [@doctodo write class description for Date].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Date extends \infinite\db\behaviors\ActiveRecord
{
    /**
     */
    protected static $_handle = [];
    /**
     */
    public $dbTimeFormat = "G:i:s";
    /**
     */
    public $dbDateFormat = "Y-m-d";

    /**
     */
    public $humanTimeFormat = "g:i:s a";
    /**
     */
    public $humanDateFormat = "m/d/Y";

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_BEFORE_VALIDATE => '_toDatabase',
            \infinite\db\ActiveRecord::EVENT_AFTER_VALIDATE => '_toHumanErrorCheck',
            \infinite\db\ActiveRecord::EVENT_AFTER_UPDATE => '_toHuman',
            \infinite\db\ActiveRecord::EVENT_AFTER_INSERT => '_toHuman',
            // \infinite\db\ActiveRecord::EVENT_AFTER_FIND => '_toHuman',
            // \infinite\db\ActiveRecord::EVENT_AFTER_SAVE_FAIL => '_toHuman'
        ];
    }

    /**
     *
     */
    public function _toDatabase($event)
    {
        foreach ($this->handle as $field => $format) {
            if ($this->owner->isAttributeChanged($field)) {
                $this->owner->{$field} = $this->_formatForDatabase($this->owner->{$field}, $format);
            }
        }

        return true;
    }

    /**
     *
     */
    public function _toHumanErrorCheck($event)
    {
        if ($this->owner->hasErrors()) {
            $this->_toHuman($event);
        }
    }

    /**
     *
     */
    public function _toHuman($event)
    {
        foreach ($this->handle as $field => $format) {
            $this->owner->{$field} = $this->_formatForHuman($this->owner->{$field}, $format);
        }

        return true;
    }

    /**
     *
     */
    protected function _formatForDatabase($field, $format)
    {
        if (empty($field)) {
            $field = null;

            return $field;
        }
        if (empty($field) || in_array($field, ['0000-00-00', '00:00:00', '0000-00-00 00:00:00'])) {
            return;
        }
        switch ($format) {
            case 'date';
                $field = DateHelper::date($this->dbDateFormat, DateHelper::strtotime($field));
            break;
            case 'time':
                $field = DateHelper::date($this->dbTimeFormat, DateHelper::strtotime($field));
            break;
            case 'datetime':
                if (strlen($field) > 10) {
                    $field = DateHelper::date($this->dbDateFormat . " " . $this->dbTimeFormat, DateHelper::strtotime($field));
                } else {
                    $field = DateHelper::date($this->dbDateFormat . " " . $this->dbTimeFormat, DateHelper::strtotime($field . " 00:00:00"));
                }
            break;
        }
        if (empty($field)) {
            return;
        }

        return $field;
    }

    /**
     *
     */
    protected function _formatForHuman($field, $format)
    {
        if (empty($field) || in_array($field, ['0000-00-00', '00:00:00', '0000-00-00 00:00:00'])) {
            $field = null;

            return $field;
        }
        switch ($format) {
            case 'date';
                $field = DateHelper::date($this->humanDateFormat, DateHelper::strtotime($field));
            break;
            case 'time':
                $field = DateHelper::date($this->humanTimeFormat, DateHelper::strtotime($field));
            break;
            case 'datetime':
                if (substr($field, -8) === "00:00:00") {
                    $field = DateHelper::date($this->humanDateFormat, DateHelper::strtotime($field));
                } else {
                    $field = DateHelper::date($this->humanDateFormat . " " . $this->humanTimeFormat, DateHelper::strtotime($field));
                }
            break;
        }
        if (empty($field)) {
            $field = null;
        }

        return $field;
    }

    public function convertToDatabaseDate($attributes = null)
    {
        if ($attributes === null) {
            $attributes = $this->owner->attributes;
        }
        $handles = $this->getHandle();
        foreach ($attributes as $key => $value) {
            if (!isset($handles[$key])) {
                continue;
            }
            $attributes[$key] = $this->_formatForDatabase($value, $handles[$key]);
        }

        return $attributes;
    }

    public function convertToHumanDate($attributes = null)
    {
        if ($attributes === null) {
            $attributes = $this->owner->attributes;
        }
        $handles = $this->getHandle();
        foreach ($attributes as $key => $value) {
            if (!isset($handles[$key])) {
                continue;
            }
            $attributes[$key] = $this->_formatForHuman($value, $handles[$key]);
        }

        return $attributes;
    }

    /**
     * Get handle.
     */
    public function getHandle()
    {
        $ownerClass = get_class($this->owner);
        $ownerTable = $ownerClass::tableName();
        if (!isset(self::$_handle[$ownerTable])) {
            self::$_handle[$ownerTable] = [];

            $ownerClass = get_class($this->owner);
            $schema = $ownerClass::getTableSchema();

            foreach ($schema->columns as $column) {
                switch ($column->dbType) {
                    case 'date':
                        self::$_handle[$ownerTable][$column->name] = 'date';
                    break;
                    case 'time';
                        self::$_handle[$ownerTable][$column->name] = 'time';
                    break;
                    case 'datetime':
                        self::$_handle[$ownerTable][$column->name] = 'datetime';
                    break;
                }
            }
        }

        return self::$_handle[$ownerTable];
    }
}
