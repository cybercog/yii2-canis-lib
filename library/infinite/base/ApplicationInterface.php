<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base;

interface ApplicationInterface
{
    public function registerModelAlias($alias, $namespace);
    public function getModelAliases();
    public function registerMigrationAlias($alias);
    public function getMigrationAliases();
}
