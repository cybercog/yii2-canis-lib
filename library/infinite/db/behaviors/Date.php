<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors;

use infinite\helpers\Date as DateHelper;

/**
 * Date [@doctodo write class description for Date]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Date extends \infinite\db\behaviors\ActiveRecord
{
    /**
     * @var __var__handle_type__ __var__handle_description__
     */
    protected static $_handle = [];
    /**
     * @var __var_dbTimeFormat_type__ __var_dbTimeFormat_description__
     */
    public $dbTimeFormat = "G:i:s";
    /**
     * @var __var_dbDateFormat_type__ __var_dbDateFormat_description__
     */
    public $dbDateFormat = "Y-m-d";

    /**
     * @var __var_humanTimeFormat_type__ __var_humanTimeFormat_description__
     */
    public $humanTimeFormat = "g:i:s a";
    /**
     * @var __var_humanDateFormat_type__ __var_humanDateFormat_description__
     */
    public $humanDateFormat = "m/d/Y";

    /**
    * @inheritdoc
     */
    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_BEFORE_VALIDATE=> '_toDatabase',
            \infinite\db\ActiveRecord::EVENT_AFTER_VALIDATE=> '_toHumanErrorCheck',
            \infinite\db\ActiveRecord::EVENT_AFTER_UPDATE => '_toHuman',
            \infinite\db\ActiveRecord::EVENT_AFTER_INSERT => '_toHuman',
            // \infinite\db\ActiveRecord::EVENT_AFTER_FIND => '_toHuman',
            // \infinite\db\ActiveRecord::EVENT_AFTER_SAVE_FAIL => '_toHuman'
        ];
    }

    /**
     * __method__toDatabase_description__
     * @param __param_event_type__ $event __param_event_description__
     * @return __return__toDatabase_type__ __return__toDatabase_description__
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
     * __method__toHumanErrorCheck_description__
     * @param __param_event_type__ $event __param_event_description__
     */
    public function _toHumanErrorCheck($event)
    {
        if ($this->owner->hasErrors()) {
            $this->_toHuman($event);
        }
    }

    /**
     * __method__toHuman_description__
     * @param __param_event_type__ $event __param_event_description__
     * @return __return__toHuman_type__ __return__toHuman_description__
     */
    public function _toHuman($event)
    {
        foreach ($this->handle as $field => $format) {
            $this->owner->{$field} = $this->_formatForHuman($this->owner->{$field}, $format);
        }

        return true;
    }

    /**
     * __method__formatForDatabase_description__
     * @param __param_field_type__ $field __param_field_description__
     * @param __param_format_type__ $format __param_format_description__
     * @return __return__formatForDatabase_type__ __return__formatForDatabase_description__
     */
    protected function _formatForDatabase($field, $format)
    {
        if (empty($field)) { $field = null; return $field; }
        if (empty($field) || in_array($field, ['0000-00-00', '00:00:00', '0000-00-00 00:00:00'])) {
            return null;
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
                    $field = DateHelper::date($this->dbDateFormat ." ". $this->dbTimeFormat, DateHelper::strtotime($field));
                } else {
                    $field = DateHelper::date($this->dbDateFormat ." ". $this->dbTimeFormat, DateHelper::strtotime($field ." 00:00:00"));
                }
            break;
        }
        if (empty($field)) {
            return null;
        }

        return $field;
    }

    /**
     * __method__formatForHuman_description__
     * @param __param_field_type__ $field __param_field_description__
     * @param __param_format_type__ $format __param_format_description__
     * @return __return__formatForHuman_type__ __return__formatForHuman_description__
     */
    protected function _formatForHuman($field, $format)
    {
        if (empty($field) || in_array($field, ['0000-00-00', '00:00:00', '0000-00-00 00:00:00'])) { $field = null; return $field; }
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
                    $field = DateHelper::date($this->humanDateFormat ." ". $this->humanTimeFormat, DateHelper::strtotime($field));
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
     * Get handle
     * @return __return_getHandle_type__ __return_getHandle_description__
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
