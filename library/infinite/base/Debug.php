<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base;

use Yii;

use yii\helpers\VarDumper;
use infinite\db\ActiveRecord;

class Debug extends \infinite\base\Object
{
    public static function db($query)
    {
        if (is_null($query->params)) { $query->params = []; }
        $text = $query->createCommand()->sql;
        $values = array_values($query->params);
        array_walk($values, function (&$value) { $value = Yii::$app->db->quoteValue($value); });
        $text = str_replace(array_keys($query->params), $values, $text);
        echo '<pre>';
        echo $text;
        echo '</pre>';

    }

    /**
     *
     *
     * @param unknown $what
     * @param unknown $showFrom (optional)
     */
    public static function d($what, $showFrom = true, $stepsBack = 0)
    {
        if (defined('STDIN')) {
            echo CVarDumper::dumpAsString($what, 15, false);
        } else {
            echo '<div class="debug-info group">';
            if ($showFrom) {
                $calledFrom = debug_backtrace();
                if (!defined('ROOT')) { define('ROOT', ''); }
                echo '<strong>' . substr(str_replace(ROOT, '', $calledFrom[$stepsBack]['file']), 1) . '</strong>';
                echo ' (line <strong>' . $calledFrom[$stepsBack]['line'] . '</strong>)<br />';
            }
            echo VarDumper::dumpAsString($what, 15, true);
            echo '</div>';
        }
    }

    public static function ar($what, $print = true)
    {
        $results = [];
        foreach ($what as $key => $value) {
            if (is_object($value) AND $value instanceof ActiveRecord) {
                $a = $value->attributes;
            } elseif (is_object($value) AND $value instanceof ActiveRecord) {
                $a = '#OBJECT('.get_class($value).')';
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
     *
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
                if (!defined('ROOT')) { define('ROOT', ''); }
                echo '<strong>' . substr(str_replace(ROOT, '', $calledFrom[0]['file']), 1) . '</strong>';
                echo ' (line <strong>' . $calledFrom[0]['line'] . '</strong>)<br />';
            }
            echo VarDumper::dumpAsString($what, 10, true);
            echo '</div>';
        }
    }
}
