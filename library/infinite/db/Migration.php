<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db;

/**
 * Migration [@doctodo write class description for Migration]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
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
        $this->db->createCommand('DROP TABLE IF EXISTS `'.$table.'`')->execute();
        echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }
}
