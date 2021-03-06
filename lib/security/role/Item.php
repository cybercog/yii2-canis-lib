<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\security\role;

use canis\helpers\ArrayHelper;

/**
 * Item [[@doctodo class_description:canis\security\role\Item]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Item extends \canis\base\collector\Item
{
    /**
     * @var [[@doctodo var_type:exclusive]] [[@doctodo var_description:exclusive]]
     */
    public $exclusive = false;
    /**
     * @var [[@doctodo var_type:inheritedEditable]] [[@doctodo var_description:inheritedEditable]]
     */
    public $inheritedEditable = true;
    /**
     * @var [[@doctodo var_type:name]] [[@doctodo var_description:name]]
     */
    public $name;
    /**
     * @var [[@doctodo var_type:level]] [[@doctodo var_description:level]]
     */
    public $level = 100;

    /**
     * Get package.
     *
     * @return [[@doctodo return_type:getPackage]] [[@doctodo return_description:getPackage]]
     */
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

    /**
     * Get id.
     *
     * @return [[@doctodo return_type:getId]] [[@doctodo return_description:getId]]
     */
    public function getId()
    {
        if (!isset($this->object)) {
            return false;
        }

        return ArrayHelper::getValue($this->object, 'primaryKey');
    }

    /**
     * @inheritdoc
     */
    public function getSystemId()
    {
        if (parent::getSystemId()) {
            return parent::getSystemId();
        }

        return ArrayHelper::getValue($this->object, 'system_id');
    }

    /**
     * Get level section.
     *
     * @return [[@doctodo return_type:getLevelSection]] [[@doctodo return_description:getLevelSection]]
     */
    public function getLevelSection()
    {
        if ($this->level > CANIS_ROLE_LEVEL_MANAGER) {
            return 'owner';
        } elseif ($this->level > CANIS_ROLE_LEVEL_EDITOR) {
            return 'manager';
        } elseif ($this->level > CANIS_ROLE_LEVEL_COMMENTER) {
            return 'editor';
        } elseif ($this->level > CANIS_ROLE_LEVEL_VIEWER) {
            return 'commenter';
        } elseif ($this->level > CANIS_ROLE_LEVEL_BROWSER) {
            return 'viewer';
        } elseif ($this->level > 0) {
            return 'browser';
        }

        return 'none';
    }
}
