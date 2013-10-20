<?php
/**
 * library/setup/Migrator.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\setup;
use infinite\setup\Setup;
use infinite\setup\Exception;
use infinite\helpers\ArrayHelper;
use yii\helpers\Html;

class Migrator extends \infinite\base\Object {
	const BASE_MIGRATION='m000000_000000_base';

	static $_instance;
	static $_setup;

	public $basePath;
	public $envFilePath;
	public $connectionID = 'db';
	public $migrationTable = 'tbl_migration';
	public $errors = array();

	protected $_migrations;
	protected $_migrationsMap = array();
	protected $_applied;

	private $_db;

	public static function createMigratorApplication($_setup = null) {
		if (is_null(self::$_instance)) {
			self::$_instance = new __CLASS__($_setup);
		}
		return self::$_instance;
	}

	public function __construct($_setup = null) {
		if (is_null($_setup)) {
			$_setup = Setup::createSetupApplication();
		}
		self::$_setup = $_setup;
		$this->basePath = $_setup->basePath;
		$this->envFilePath = $_setup->environmentFilePath;
	}

	public function upgrade() {
		$success = 0;
		foreach ($this->newMigrations as $migration) {
			if (!$this->migrateUp($migration)) {
				return false;
			}
			$success++;
		}
		return $success;
	}

	protected function migrateUp($class) {
		if($class === self::BASE_MIGRATION) {
			return;
		}
		$migration = $this->instantiateMigration($class);
		if($migration->up() !== false) {
			$this->getDbConnection()->createCommand()->insert($this->migrationTable, array(
				'version'=>$class,
				'apply_time'=>time(),
			));
			return true;
		} else {
			$this->errors[] = "Failed to apply $class";
			return false;
		}
	}

	public function getNewMigrations() {
		if (is_null($this->_migrations)) {
			$applied = $this->appliedMigrations;
			$this->_migrations = array();
			foreach (Yii::app()->params['migrationPaths'] as $migrationPath) {
				$migrationPath = Yii::getPathOfAlias($migrationPath);
				if (!is_dir($migrationPath)) { throw new Exception("Bad migration path {$migrationPath}!"); continue; }
				$handle = opendir($migrationPath);
				while(($file = readdir($handle))!==false) {
					if($file === '.' || $file === '..') {
						continue;
					}
					$path = $migrationPath . DIRECTORY_SEPARATOR . $file;
					if(preg_match('/^(m(\d{6}_\d{6})_.*?)\.php$/',$file,$matches) AND is_file($path) AND !isset($applied[$matches[1]])) {
						$this->_migrations[] = $matches[1];
						$this->_migrationsMap[$matches[1]] = $path;
					}
				}
				closedir($handle);
			}
			sort($this->_migrations);
		}
		return $this->_migrations;
	}

	protected function instantiateMigration($class) {
		if (!isset($this->_migrationsMap[$class])) {
			return false;
		}
		$file = $this->_migrationsMap[$class];
		require_once($file);
		$migration = new $class;
		$migration->setDbConnection($this->getDbConnection());
		return $migration;
	}

	public function getSetup() {
		return self::$_setup;
	}

	public function getHasAnyMigrations() {
		foreach ($this->appliedMigrations as $version => $applyTime) {
			if ($version !== self::BASE_MIGRATION) {
				return true;
			}
		}
		return false;
	}

	public function getAppliedMigrations() {
		$app = $this->setup->app();
		$db = $this->getDbConnection();
		if($db->schema->getTable($this->migrationTable,true)===null)
		{
			$this->createMigrationHistoryTable();
		}

		if (is_null($this->_applied)) {
			$this->_applied = ArrayHelper::map($db->createCommand()
				->select('version, apply_time')
				->from($this->migrationTable)
				->order('version DESC')
				->queryAll(), 'version', 'apply_time');
		}
		return $this->_applied;
	}

	protected function createMigrationHistoryTable() {
		$db=$this->getDbConnection();
		$db->createCommand()->createTable($this->migrationTable,array(
			'id'=>'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY',
			'version'=>'string NOT NULL',
			'apply_time'=>'integer',
		));
		$db->createCommand()->insert($this->migrationTable,array(
			'version'=>self::BASE_MIGRATION,
			'apply_time'=>time(),
		));
	}

	protected function getDbConnection() {
		if($this->_db!==null) {
			return $this->_db;
		} elseif(($this->_db = $this->setup->app()->getComponent($this->connectionID)) instanceof CDbConnection) {
			return $this->_db;
		}

		throw new Exception("'{$this->connectionID}' is invalid. Please make sure it refers to the ID of a CDbConnection application component.\n");
	}

	public function check() {
		return !empty($this->newMigrations);
	}
}


?>
