<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\base;

interface ApplicationInterface
{
    public function registerModelAlias($alias, $namespace);
    public function getModelAliases();
    public function registerMigrationAlias($alias);
    public function getMigrationAliases();
}
