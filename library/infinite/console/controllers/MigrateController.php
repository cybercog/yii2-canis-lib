<?php

namespace infinite\console\controllers;
use Yii;
use infinite\base\Exception;

class MigrateController extends \yii\console\controllers\MigrateController {
	/**
	 * @var array the directories storing the migration classes. This can contain either
	 * a path alias or a directory.
	 */
	public $migrationPaths = ['@app/migrations'];


	protected $migrationsMap = [];

	public $templateFile = '@infinite/views/system/migration.php';

	protected function createMigration($class)
	{
		if (!isset($this->migrationsMap[$class]))
		{
			return false;
		}
		$file = $this->migrationsMap[$class];
		require_once($file);
		return new $class(['db' => $this->db]);
	}

	/**
	 * Returns the migrations that are not applied.
	 * @return array list of new migrations
	 */
	protected function getNewMigrations() {
		$applied = array();
		foreach($this->getMigrationHistory(-1) as $version=>$time) {
			$applied[substr($version,1,13)] = true;
		}

		$migrations = [];
		foreach ($this->migrationPaths as $migrationPath) {
			$migrationPath = Yii::getAlias($migrationPath);
			if (!is_dir($migrationPath)) { throw new Exception("Bad migration path {$migrationPath}!"); continue; }
			$handle = opendir($migrationPath);
			while(($file = readdir($handle))!==false) {
				if($file === '.' || $file === '..') {
					continue;
				}
				$path = $migrationPath . DIRECTORY_SEPARATOR . $file;
				if(preg_match('/^(m(\d{6}_\d{6})_.*?)\.php$/',$file,$matches) AND is_file($path) AND !isset($applied[$matches[2]])) {
					$migrations[] = $matches[1];
					$this->migrationsMap[$matches[1]] = $path;
				}
			}
			closedir($handle);
		}
		sort($migrations);
		return $migrations;
	}
}
?>