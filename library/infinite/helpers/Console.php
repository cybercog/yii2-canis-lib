<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\helpers;

use Yii;

/**
 * Console [[@doctodo class_description:infinite\helpers\Console]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Console extends \yii\helpers\Console
{
    /**
     * @var [[@doctodo var_type:_progressPrefixSpecial]] [[@doctodo var_description:_progressPrefixSpecial]]
     */
    private static $_progressPrefixSpecial;
    /**
     * @var [[@doctodo var_type:_progressStartSpecial]] [[@doctodo var_description:_progressStartSpecial]]
     */
    private static $_progressStartSpecial;
    /**
     * @var [[@doctodo var_type:_progressWidthSpecial]] [[@doctodo var_description:_progressWidthSpecial]]
     */
    private static $_progressWidthSpecial;

    /**
     * [[@doctodo method_description:updateProgressSpecial]].
     *
     * @param [[@doctodo param_type:done]]   $done   [[@doctodo param_description:done]]
     * @param [[@doctodo param_type:total]]  $total  [[@doctodo param_description:total]]
     * @param [[@doctodo param_type:prefix]] $prefix [[@doctodo param_description:prefix]] [optional]
     */
    public static function updateProgressSpecial($done, $total, $prefix = null)
    {
        $width = self::$_progressWidthSpecial;
        if ($width === false) {
            $width = 0;
        } else {
            $screenSize = 200;
        }
        if ($prefix === null) {
            $prefix = self::$_progressPrefixSpecial;
        } else {
            self::$_progressPrefixSpecial = $prefix;
        }
        $width -= mb_strlen($prefix);

        $percent = ($total == 0) ? 1 : $done / $total;
        $info = sprintf("%d%% (%d/%d)", $percent * 100, $done, $total);

        if ($done > $total || $done == 0) {
            $info .= ' ETA: n/a';
        } elseif ($done < $total) {
            $spent = max((time() - self::$_progressStartSpecial), 1);
            $rate = $done / $spent;
            $info .= sprintf(' ETA: %d sec.', ($total - $done)/$rate);
            $info .= sprintf(' Rate: %f/s', $rate);
        }

        $width -= 3 + mb_strlen($info);
        // skipping progress bar on very small display or if forced to skip
        if ($width < 5) {
            static::output("$prefix$info   ");
        } else {
            if ($percent < 0) {
                $percent = 0;
            } elseif ($percent > 1) {
                $percent = 1;
            }
            $bar = floor($percent * $width);
            $status = str_repeat("=", $bar);
            if ($bar < $width) {
                $status .= ">";
                $status .= str_repeat(" ", $width - $bar - 1);
            }
            static::output("$prefix" . "[$status] $info");
        }
    }
    /**
     * [[@doctodo method_description:startProgressSpecial]].
     *
     * @param [[@doctodo param_type:done]]  $done   [[@doctodo param_description:done]]
     * @param [[@doctodo param_type:total]] $total  [[@doctodo param_description:total]]
     * @param string                        $prefix [[@doctodo param_description:prefix]] [optional]
     * @param [[@doctodo param_type:width]] $width  [[@doctodo param_description:width]] [optional]
     */
    public static function startProgressSpecial($done, $total, $prefix = '', $width = null)
    {
        self::$_progressStartSpecial = time();
        self::$_progressWidthSpecial = $width;
        self::$_progressPrefixSpecial = $prefix;

        static::updateProgressSpecial($done, $total);
    }
    /**
     * [[@doctodo method_description:endProgressSpecial]].
     *
     * @param boolean $remove     [[@doctodo param_description:remove]] [optional]
     * @param boolean $keepPrefix [[@doctodo param_description:keepPrefix]] [optional]
     */
    public static function endProgressSpecial($remove = false, $keepPrefix = true)
    {
        if ($remove === false) {
            static::stdout(PHP_EOL);
        } else {
            if (static::streamSupportsAnsiColors(STDOUT)) {
                static::clearLine();
            }
            static::stdout("\r" . ($keepPrefix ? self::$_progressPrefixSpecial : '') . (is_string($remove) ? $remove : ''));
        }
        flush();

        self::$_progressStartSpecial = null;
        self::$_progressWidthSpecial = null;
        self::$_progressPrefixSpecial = '';
    }
}
