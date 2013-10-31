<?php
/**
 * library/db/Connection.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db;

class Migration extends \yii\db\Migration
{
	/**
	 * Builds and executes a SQL statement for dropping a DB table.
	 * @param string $table the table to be dropped. The name will be properly quoted by the method.
	 */
	public function dropExistingTable($table)
	{
		echo "    > drop table $table if exists ...";
		$time = microtime(true);
		$this->db->createCommand('DROP TABLE IF EXISTS :table', [':table' => $table])->execute();
		echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
	}
}
