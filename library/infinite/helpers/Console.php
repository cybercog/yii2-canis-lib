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
 * Html [@doctodo write class description for Html].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Console extends \yii\helpers\Console
{
    private static $_progressPrefixSpecial;
    private static $_progressStartSpecial;
    private static $_progressWidthSpecial;

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
            static::output("$prefix"."[$status] $info");
        }
    }
    public static function startProgressSpecial($done, $total, $prefix = '', $width = null)
    {
        self::$_progressStartSpecial = time();
        self::$_progressWidthSpecial = $width;
        self::$_progressPrefixSpecial = $prefix;

        static::updateProgressSpecial($done, $total);
    }
    public static function endProgressSpecial($remove = false, $keepPrefix = true)
    {
        if ($remove === false) {
            static::stdout(PHP_EOL);
        } else {
            if (static::streamSupportsAnsiColors(STDOUT)) {
                static::clearLine();
            }
            static::stdout("\r".($keepPrefix ? self::$_progressPrefixSpecial : '').(is_string($remove) ? $remove : ''));
        }
        flush();

        self::$_progressStartSpecial = null;
        self::$_progressWidthSpecial = null;
        self::$_progressPrefixSpecial = '';
    }
}
