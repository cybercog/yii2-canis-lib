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
    /**
     * @var __var_exclusive_type__ __var_exclusive_description__
     */
    public $exclusive = false;
    /**
     * @var __var_inheritedEditable_type__ __var_inheritedEditable_description__
     */
    public $inheritedEditable = true;
    /**
     * @var __var_name_type__ __var_name_description__
     */
    public $name;
    /**
     * @var __var_level_type__ __var_level_description__
     */
    public $level = 100;

    /**
     * Get package
     * @return __return_getPackage_type__ __return_getPackage_description__
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
     * Get id
     * @return __return_getId_type__ __return_getId_description__
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
    **/
    public function getSystemId()
    {
        if (parent::getSystemId()) {
            return parent::getSystemId();
        }

        return ArrayHelper::getValue($this->object, 'system_id');
    }

    /**
     * Get level section
     * @return __return_getLevelSection_type__ __return_getLevelSection_description__
     */
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
