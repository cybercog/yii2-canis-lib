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
 * Date [[@doctodo class_description:infinite\db\behaviors\Date]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Date extends \infinite\db\behaviors\ActiveRecord
{
    /**
     * @var [[@doctodo var_type:_handle]] [[@doctodo var_description:_handle]]
     */
    protected static $_handle = [];
    /**
     * @var [[@doctodo var_type:dbTimeFormat]] [[@doctodo var_description:dbTimeFormat]]
     */
    public $dbTimeFormat = "G:i:s";
    /**
     * @var [[@doctodo var_type:dbDateFormat]] [[@doctodo var_description:dbDateFormat]]
     */
    public $dbDateFormat = "Y-m-d";

    /**
     * @var [[@doctodo var_type:humanTimeFormat]] [[@doctodo var_description:humanTimeFormat]]
     */
    public $humanTimeFormat = "g:i:s a";
    /**
     * @var [[@doctodo var_type:humanDateFormat]] [[@doctodo var_description:humanDateFormat]]
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
     * [[@doctodo method_description:_toDatabase]].
     *
     * @return [[@doctodo return_type:_toDatabase]] [[@doctodo return_description:_toDatabase]]
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
     * [[@doctodo method_description:_toHumanErrorCheck]].
     */
    public function _toHumanErrorCheck($event)
    {
        if ($this->owner->hasErrors()) {
            $this->_toHuman($event);
        }
    }

    /**
     * [[@doctodo method_description:_toHuman]].
     *
     * @return [[@doctodo return_type:_toHuman]] [[@doctodo return_description:_toHuman]]
     */
    public function _toHuman($event)
    {
        foreach ($this->handle as $field => $format) {
            $this->owner->{$field} = $this->_formatForHuman($this->owner->{$field}, $format);
        }

        return true;
    }

    /**
     * [[@doctodo method_description:_formatForDatabase]].
     *
     * @return [[@doctodo return_type:_formatForDatabase]] [[@doctodo return_description:_formatForDatabase]]
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
     * [[@doctodo method_description:_formatForHuman]].
     *
     * @return [[@doctodo return_type:_formatForHuman]] [[@doctodo return_description:_formatForHuman]]
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

    /**
     * [[@doctodo method_description:convertToDatabaseDate]].
     *
     * @return [[@doctodo return_type:convertToDatabaseDate]] [[@doctodo return_description:convertToDatabaseDate]]
     */
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

    /**
     * [[@doctodo method_description:convertToHumanDate]].
     *
     * @return [[@doctodo return_type:convertToHumanDate]] [[@doctodo return_description:convertToHumanDate]]
     */
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
     *
     * @return [[@doctodo return_type:getHandle]] [[@doctodo return_description:getHandle]]
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
