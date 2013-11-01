<?php
/**
 * library/db/behaviors/Date.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db\behaviors;

use \infinite\helpers\Date as DateHelper;

class Date extends \infinite\db\behaviors\ActiveRecord
{
    protected $_handle = null;
    public $dbTimeFormat = "G:i:s";
    public $dbDateFormat = "Y-m-d";

    public $humanTimeFormat = "g:i a";
    public $humanDateFormat = "m/d/Y";

    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_BEFORE_VALIDATE=> '_toDatabase',
            \infinite\db\ActiveRecord::EVENT_AFTER_VALIDATE=> '_toHumanErrorCheck',
            \infinite\db\ActiveRecord::EVENT_AFTER_UPDATE => '_toHuman',
            \infinite\db\ActiveRecord::EVENT_AFTER_INSERT => '_toHuman',
            // \infinite\db\ActiveRecord::EVENT_AFTER_SAVE_FAIL => '_toHuman'
        ];
    }

    public function _toDatabase($event)
    {
        foreach ($this->handle as $field => $format) {
            $this->owner->{$field} = $this->_formatForDatabase($this->owner->{$field}, $format);
        }
        return true;
    }

    public function _toHumanErrorCheck($event) {
        if ($this->owner->hasErrors()) {
            $this->_toHuman($event);
        }
    }

    public function _toHuman($event)
    {
        foreach ($this->handle as $field => $format) {
            $this->owner->{$field} = $this->_formatForHuman($this->owner->{$field}, $format);
        }
        return true;
    }

    protected function _formatForDatabase($field, $format)
    {
        if (empty($field)) { $field = null; return $field; }
        if (empty($field) || in_array($field, ['0000-00-00', '00:00:00', '0000-00-00 00:00:00'])) {
            $field = null;
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
            $field = null;
        }
        return $field;
    }

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

    public function getHandle()
    {
        if (is_null($this->_handle)) {
            $this->_handle = [];

            $ownerClass = get_class($this->owner);
            $schema = $ownerClass::getTableSchema();

            foreach ($schema->columns as $column) {
                switch ($column->dbType) {
                    case 'date':
                        $this->_handle[$column->name] = 'date';
                    break;
                    case 'time';
                        $this->_handle[$column->name] = 'time';
                    break;
                    case 'datetime':
                        $this->_handle[$column->name] = 'datetime';
                    break;
                }
            }
        }
        return $this->_handle;
    }

}
