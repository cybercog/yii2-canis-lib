<?php
/**
 * library/security/role/Engine.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\security\role;

use infinite\helpers\ArrayHelper;

class Engine extends \infinite\base\Engine
{
    const MODEL = '\app\models\Role';
    const GLOBAL_MODEL = '__GLOBAL__';

    protected $_registry = array();
    protected $_registryById = array();
    protected $_registryByModel = array();

    protected $_role_models;

    public $initial = array();


    public function beforeRequest()
    {
        $this->register(null, $this->initial);
        return parent::beforeRequest();
    }

    public function getTypes()
    {
        return $this->_registryById;
    }

    public function getRoleModel($system_id)
    {
        if (isset($this->roleModels[$system_id])) {
            return $this->roleModels[$system_id];
        }
        return false;
    }

    public function registerRoleModel($role)
    {
        $this->roleModels; // first we want to fill this in
        $this->_role_models[$role->system_id] = $role;
        return true;
    }

    public function getRoleModels()
    {
        if (is_null($this->_role_models)) {
            $model = self::MODEL;
            $raw = $model::find()->all();
            $this->_role_models = ArrayHelper::map(array_values($raw), 'system_id');
        }
        return $this->_role_models;
    }

    /**
     *
     *
     * @param unknown $systemId (optional)
     * @return unknown
     */
    public function get($systemId)
    {
        if (isset($this->_registry[$systemId])) {
            return $this->_registry[$systemId];
        }
        return false;
    }

    public function getRoles($module)
    {
        $roles = array();
        if (isset($this->_registryByModel[$module->primaryModel])) {
            $roles = array_merge($roles, $this->_registryByModel[$module->primaryModel]);
        }

        if (isset($this->_registryByModel[self::GLOBAL_MODEL])) {
            $roles = array_merge($roles, $this->_registryByModel[self::GLOBAL_MODEL]);
        }

        $bad = array();
        if (!$module->isOwnable) {
            $bad[] = 'owner';
        }

        if (!empty($bad)) {
            foreach ($roles as $k => $v) {
                if (in_array($v->system_id, $bad)) {
                    unset($roles[$k]);
                }
            }
        }

        return ArrayHelper::multisort($roles, 'level', true);
    }

    public function getRoleList($module)
    {
        $roles = $this->getRoles($module);
        return ArrayHelper::map($roles, 'id', 'name');
    }
    /**
     *
     *
     * @param unknown $id (optional)
     * @return unknown
     */
    public function getByPk($id)
    {
        if (isset($this->_registryById[$id])) {
            return $this->_registryById[$id];
        }
        return false;
    }

    /**
     *
     *
     * @param unknown $owner
     * @param unknown $acas
     * @return unknown
     */
    public function register($owner, $roles)
    {
        if (empty($roles)) {
            return true;
        }
        foreach ($roles as $role) {
            $role = new RoleItem($role);
            if (!isset($this->_registry[$role->system_id])) {
                $this->_registry[$role->system_id] = $role;
                $this->_registryById[$role->id] = $role;
            }
            if (is_null($owner)) {
                if (!isset($this->_registryByModel[self::GLOBAL_MODEL])) { $this->_registryByModel[self::GLOBAL_MODEL] = array(); }
                $this->_registryByModel[self::GLOBAL_MODEL][] = $this->_registry[$role->system_id];
            } else {
                if (!isset($this->_registryByModel[$owner->primaryModel])) { $this->_registryByModel[$owner->primaryModel] = array(); }
                $this->_registryByModel[$owner->primaryModel] = $this->_registry[$role->system_id];
            }
        }
        return true;
    }
}
