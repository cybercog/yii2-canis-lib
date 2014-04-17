<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\console\controllers;

use Yii;
use infinite\base\exceptions\Exception;

/**
 * MigrateController [@doctodo write class description for MigrateController]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class MigrateController extends \yii\console\controllers\MigrateController
{
    /**
     * @var array the directories storing the migration classes. This can contain either
a path alias or a directory.
     */
    public $migrationPaths = [];

    /**
     * @var __var_migrationsMap_type__ __var_migrationsMap_description__
     */
    protected $migrationsMap = [];

    /**
     * @inheritdoc
     */
    public $templateFile = '@infinite/views/system/migration.phpt';

    /**
    * @inheritdoc
     */
    protected function createMigration($class)
    {
        if (!isset($this->migrationsMap[$class])) {
            return false;
        }
        $file = $this->migrationsMap[$class];
        require_once($file);

        return new $class(['db' => $this->db]);
    }

    /**
     * Returns the migrations that are not applied.
     * @return array list of new migrations
     * @throws Exception __exception_Exception_description__
     */
    protected function getNewMigrations()
    {
        $applied = [];
        foreach ($this->getMigrationHistory(-1) as $version=>$time) {
            $applied[$version] = true;
        }

        $migrations = [];
        foreach (array_merge($this->migrationPaths, Yii::$app->migrationAliases) as $migrationPathAlias) {
            $migrationPath = Yii::getAlias($migrationPathAlias);
            if (!is_dir($migrationPath)) { throw new Exception("Bad migration path {$migrationPath}!"); continue; }
            $handle = opendir($migrationPath);
            while (($file = readdir($handle))!==false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $path = $migrationPath . DIRECTORY_SEPARATOR . $file;
                if (preg_match('/^(m(\d{6}_\d{6})_.*?)\.php$/',$file,$matches) AND is_file($path)) {
                    $migrationClassName = str_replace('/', '\\', substr($migrationPathAlias, 1)) .'\\'.  $matches[1];
                    if (!isset($applied[$migrationClassName])) {
                        $migrations[] = $migrationClassName;
                        $this->migrationsMap[$migrationClassName] = $path;
                    }
                }
            }
            closedir($handle);
        }
        sort($migrations);

        return $migrations;
    }
}
