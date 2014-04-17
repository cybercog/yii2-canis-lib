<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\security\role;

use infinite\helpers\ArrayHelper;

/**
 * Item [@doctodo write class description for Item]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class Item extends \infinite\base\collector\Item
{
    public $exclusive = false;
    public $inheritedEditable = true;
    public $name;
    public $level = 100;

    public function getPackage()
    {
        return [
            'id' => $this->id,
            'system_id' => $this->object->system_id,
            'label' => $this->name,
            'exclusive' => $this->exclusive,
            // 'inheritedEditable' => $this->inheritedEditable,
            'level' => $this->level,
        ];
    }

    public function getId()
    {
        if (!isset($this->object)) {
            return false;
        }

        return ArrayHelper::getValue($this->object, 'primaryKey');
    }

    /**
    * @inheritdoc
    **/
    public function getSystemId()
    {
        if (parent::getSystemId()) {
            return parent::getSystemId();
        }

        return ArrayHelper::getValue($this->object, 'system_id');
    }

    public function getLevelSection()
    {
        if ($this->level > INFINITE_ROLE_LEVEL_MANAGER) {
            return 'owner';
        } elseif ($this->level > INFINITE_ROLE_LEVEL_EDITOR) {
            return 'manager';
        } elseif ($this->level > INFINTE_ROLE_LEVEL_COMMENTER) {
            return 'editor';
        } elseif ($this->level > INFINITE_ROLE_LEVEL_VIEWER) {
            return 'commenter';
        } elseif ($this->level > INFINITE_ROLE_LEVEL_BROWSER) {
            return 'viewer';
        } elseif ($this->level > 0) {
            return 'browser';
        }

        return 'none';
    }
}
