<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\base;

interface ApplicationInterface
{
    public function registerModelAlias($alias, $namespace);
    public function getModelAliases();
    public function registerMigrationAlias($alias);
    public function getMigrationAliases();
}
