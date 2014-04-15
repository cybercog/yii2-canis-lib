<?php
/**
 * library/db/behaviors/Date.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db\behaviors;

use infinite\helpers\Date as DateHelper;

class Auditable extends \infinite\db\behaviors\ActiveRecord
{
    protected $_ignoreAttributes = ['modified', 'created', 'created_by_id', 'modified_by_id'];
    protected $_dirtyAttributes;

    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            \infinite\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
            \infinite\db\ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            \infinite\db\ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',

            \infinite\db\ActiveRecord::EVENT_BEFORE_DELETE=> 'beforeDelete',
            \infinite\db\ActiveRecord::EVENT_AFTER_DELETE=> 'afterDelete'
        ];
    }

    public function beforeSave($event)
    {
        // capture dirty attributes
        $this->_dirtyAttributes = $this->owner->dirtyAttributes;
        foreach ($this->_dirtyAttributes as $name => $value) {
            if (in_array($name, $this->ignoreAttributes)) {
                unset($this->_dirtyAttributes[$name]);
            }
        }
    }

    public function afterSave($event)
    {

    }

    public function beforeDelete($event)
    {

    }

    public function afterDelete($event)
    {

    }

    public function setIgnoreAttributes($value)
    {
        foreach ($value as $field) {
            if (!in_array($field, $this->_ignoreAttributes)) {
                $this->_ignoreAttributes[] = $field;
            }
        }
    }

    public function getIgnoreAttributes()
    {
        return $this->_ignoreAttributes;
    }

}
