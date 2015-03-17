<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db;

/**
 * Migration [[@doctodo class_description:canis\db\Migration]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Migration extends \yii\db\Migration
{
    use \canis\base\ObjectTrait;
    /**
     * Builds and executes a SQL statement for dropping a DB table.
     *
     * @param string $table the table to be dropped. The name will be properly quoted by the method.
     */
    public function dropExistingTable($table)
    {
        echo "    > drop table $table if exists ...";
        $time = microtime(true);
        $this->db->createCommand('DROP TABLE IF EXISTS `' . $table . '`')->execute();
        echo " done (time: " . sprintf('%.3f', microtime(true) - $time) . "s)\n";
    }
}
