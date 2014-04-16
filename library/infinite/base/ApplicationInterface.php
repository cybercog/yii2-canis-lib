<?php
namespace infinite\base;

interface ApplicationInterface
{
    public function registerModelAlias($alias, $namespace);
    public function getModelAliases();
    public function registerMigrationAlias($alias);
    public function getMigrationAliases();
}
