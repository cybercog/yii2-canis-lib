<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\console\controllers;

use infinite\base\exceptions\Exception;
use Yii;

/**
 * MigrateController [@doctodo write class description for MigrateController].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class MigrateController extends \yii\console\controllers\MigrateController
{
    /**
     * @var array the directories storing the migration classes. This can contain either a path alias or a directory.
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
        require_once $file;

        return new $class(['db' => $this->db]);
    }

    /**
     * Returns the migrations that are not applied.
     *
     * @throws Exception __exception_Exception_description__
     *
     * @return array list of new migrations
     */
    protected function getNewMigrations()
    {
        $applied = [];
        foreach ($this->getMigrationHistory(-1) as $version => $time) {
            $applied[$version] = true;
        }

        $migrations = [];
        foreach (array_merge($this->migrationPaths, Yii::$app->migrationAliases) as $migrationPathAlias) {
            $migrationPath = Yii::getAlias($migrationPathAlias);
            if (!is_dir($migrationPath)) {
                throw new Exception("Bad migration path {$migrationPath}!");
                continue;
            }
            $handle = opendir($migrationPath);
            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $path = $migrationPath . DIRECTORY_SEPARATOR . $file;
                if (preg_match('/^(m(\d{6}_\d{6})_.*?)\.php$/', $file, $matches) and is_file($path)) {
                    $migrationClassName = str_replace('/', '\\', substr($migrationPathAlias, 1)) . '\\' . $matches[1];
                    if (!isset($applied[$migrationClassName])) {
                        $key = $migrationClassName::baseClassName() . '-' . md5($migrationClassName);
                        $migrations[$key] = $migrationClassName;
                        $this->migrationsMap[$migrationClassName] = $path;
                    }
                }
            }
            closedir($handle);
        }
        ksort($migrations);

        return $migrations;
    }

    public function actionUpPlain($limit = 0)
    {
        $result = parent::actionUp($limit);
        if ($result !== static::EXIT_CODE_ERROR) {
            echo "\n\nMigrated up successfully.";
        }

        return $result;
    }

    public function actionNewPlain($limit = 10)
    {
        if ($limit === 'all') {
            $limit = null;
        } else {
            $limit = (int) $limit;
            if ($limit < 1) {
                throw new Exception("The limit must be greater than 0.");
            }
        }

        $migrations = $this->getNewMigrations();

        if (empty($migrations)) {
            echo 'None';
        } else {
            $n = count($migrations);
            echo "Found $n new " . ($n === 1 ? 'migration' : 'migrations') . ":\n";

            foreach ($migrations as $migration) {
                echo $migration . "\n";
            }
        }
    }
}
