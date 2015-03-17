<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\base;

use Yii;

trait ApplicationTrait
{
    use ComponentTrait;

    protected $_migrationAliases = [];
    protected $_modelAliases = [];

    public function init()
    {
        $start = microtime(true);
        foreach ($this->modules as $moduleKey => $moduleConfig) {
            if (substr($moduleKey, 0, 3) === 'Set') {
                $this->getModule($moduleKey);
            }
        }
        parent::init();
        $duration = round((microtime(true) - $start) * 1000, 2);
        Yii::trace("Init took " . $duration . 'ms');
    }

    public function getIsDbAvailable()
    {
        if (!isset($this->db)) {
            return false;
        }
        $canisSetup = defined('TEAL_SETUP') && TEAL_SETUP;
        $canisSetupDbReady = defined('TEAL_SETUP_DB_READY') && TEAL_SETUP_DB_READY;
        $canisSetupDb = $canisSetup && !$canisSetupDbReady;
        if ($canisSetupDb) {
            return false;
        }

        return true;
    }

    public function registerModelAlias($alias, $namespace)
    {
        if (strncmp($alias, ':', 1)) {
            $alias = ':' . $alias;
        }
        if (!isset($this->_modelAliases[$alias])) {
            $this->_modelAliases[$alias] = $namespace;
        }

        return true;
    }

    public function getModelAliases()
    {
        return $this->_modelAliases;
    }

    public function registerMigrationAlias($alias)
    {
        if (!in_array($alias, $this->_migrationAliases)) {
            $this->_migrationAliases[] = $alias;
        }
        return true;
    }

    public function getMigrationAliases()
    {
        if (!isset(Yii::$app->params['migrationAliases'])) {
            Yii::$app->params['migrationAliases'] = [];
        }

        return array_unique(array_merge(Yii::$app->params['migrationAliases'], $this->_migrationAliases));
    }
}
