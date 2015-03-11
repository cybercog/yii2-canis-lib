<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\base;

use teal\db\ActiveRecord;
use Yii;
use yii\helpers\VarDumper;

/**
 * Debug [[@doctodo class_description:teal\base\Debug]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Debug extends \teal\base\Object
{
    /**
     * [[@doctodo method_description:db]].
     *
     * @param [[@doctodo param_type:query]] $query [[@doctodo param_description:query]]
     */
    public static function db($query)
    {
        if (is_null($query->params)) {
            $query->params = [];
        }
        $text = $query->createCommand()->sql;
        $values = array_values($query->params);
        array_walk($values, function (&$value) { $value = Yii::$app->db->quoteValue($value); });
        $text = str_replace(array_keys($query->params), $values, $text);
        echo '<pre>';
        echo $text;
        echo '</pre>';
    }

    /**
     * [[@doctodo method_description:d]].
     *
     * @param unknown $what
     * @param unknown $showFrom  (optional)
     * @param integer $stepsBack [[@doctodo param_description:stepsBack]] [optional]
     */
    public static function d($what, $showFrom = true, $stepsBack = 0)
    {
        if (defined('STDIN')) {
            echo CVarDumper::dumpAsString($what, 15, false);
        } else {
            echo '<div class="debug-info group">';
            if ($showFrom) {
                $calledFrom = debug_backtrace();
                if (!defined('ROOT')) {
                    define('ROOT', '');
                }
                echo '<strong>' . substr(str_replace(ROOT, '', $calledFrom[$stepsBack]['file']), 1) . '</strong>';
                echo ' (line <strong>' . $calledFrom[$stepsBack]['line'] . '</strong>)<br />';
            }
            echo VarDumper::dumpAsString($what, 15, true);
            echo '</div>';
        }
    }

    /**
     * [[@doctodo method_description:ar]].
     *
     * @param [[@doctodo param_type:what]] $what  [[@doctodo param_description:what]]
     * @param boolean                      $print [[@doctodo param_description:print]] [optional]
     *
     * @return [[@doctodo return_type:ar]] [[@doctodo return_description:ar]]
     */
    public static function ar($what, $print = true)
    {
        $results = [];
        foreach ($what as $key => $value) {
            if (is_object($value) and $value instanceof ActiveRecord) {
                $a = $value->attributes;
            } elseif (is_object($value) and $value instanceof ActiveRecord) {
                $a = '#OBJECT(' . get_class($value) . ')';
            } elseif (is_array($value)) {
                $a = $self::ar($value, false);
            } else {
                continue;
            }
            $results[$key] = $a;
        }
        if ($print) {
            self::d($results);
        }

        return $results;
    }

    /**
     * [[@doctodo method_description:clean]].
     *
     * @param [[@doctodo param_type:what]] $what [[@doctodo param_description:what]]
     */
    public static function clean(&$what)
    {
        foreach ($what as $key => $value) {
            if (is_object($value)) {
                $what[$key] = 'OBJECT';
            }
            if (is_array($value)) {
                self::clean($value);
            }
        }
    }
    /**
     * [[@doctodo method_description:c]].
     *
     * @param unknown $what
     * @param unknown $showFrom (optional)
     */
    public static function c($what, $showFrom = true)
    {
        self::clean($what);
        if (defined('STDIN')) {
            echo VarDumper::dumpAsString($what, 10, false);
        } else {
            echo '<div class="debug-info group">';
            if ($showFrom) {
                $calledFrom = debug_backtrace();
                if (!defined('ROOT')) {
                    define('ROOT', '');
                }
                echo '<strong>' . substr(str_replace(ROOT, '', $calledFrom[0]['file']), 1) . '</strong>';
                echo ' (line <strong>' . $calledFrom[0]['line'] . '</strong>)<br />';
            }
            echo VarDumper::dumpAsString($what, 10, true);
            echo '</div>';
        }
    }
}
