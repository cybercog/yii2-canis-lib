<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\helpers;

/**
 * Date [[@doctodo class_description:infinite\helpers\Date]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Date extends \infinite\base\Object
{
    /**
     * @var [[@doctodo var_type:_now]] [[@doctodo var_description:_now]]
     */
    protected static $_now;

    /**
     * Sets the current time of the application.
     *
     * @param varies $now Either the int of the exact time or it could be a relative string
     *
     * @return int Unix timestamp
     */
    public static function now($now)
    {
        self::$_now = $now;

        return true;
    }

    /**
     * Get the current time.
     *
     * @return int Unix timestamp
     */
    public static function time()
    {
        if (!is_null(self::$_now)) {
            if (is_int(self::$_now)) {
                return self::$_now;
            }

            return strtotime(self::$_now);
        }

        return time();
    }

    /**
     * Format date.
     *
     *                       @see php:date
     *
     * @param string $format date format
     * @param int    $time   Unix timestamp (optional)
     *
     * @return string formatted date
     *
     * @see php:date
     */
    public static function date($format, $time = null)
    {
        if (is_null($time)) {
            $time = self::time();
        }

        return date($format, $time);
    }

    /**
     * Strtotime with fallback on overriden application time.
     *
     *                     @see php:strtotime
     *
     * @param string $str  strtotime string
     * @param int    $time Unix timestamp (optional)
     *
     * @return string formatted date
     *
     * @see php:strtotime
     */
    public static function strtotime($str, $time = null)
    {
        if (is_null($time)) {
            $time = self::time();
        }

        return strtotime($str, $time);
    }

    /**
     * Gets the first second of a day.
     *
     * @param string $day  date string
     * @param int    $time Unix timestamp (optional)
     *
     * @return string Unix timestamp
     */
    public static function startOfDay($day, $time = null)
    {
        return self::strtotime($day . ' 00:00:00', $time);
    }

    /**
     * Gets the last second of a day.
     *
     * @param string $day  date string
     * @param int    $time Unix timestamp (optional)
     *
     * @return string Unix timestamp
     */
    public static function endOfDay($day, $time = null)
    {
        return self::strtotime($day . ' 23:59:59', $time);
    }

    /**
     * Is the date in the past?
     *
     * @param varies $date
     * @param int    $time (optional)
     *
     * @return bool Is the time in the past or not
     */
    public static function inPast($date, $time = null)
    {
        if (is_null($time)) {
            $time = self::time();
        }
        if (!is_int($date)) {
            $date = self::strtotime($date);
        }

        return $time > $date;
    }

    /**
     * Is the date in the future?
     *
     * @param varies $date
     * @param int    $time (optional)
     *
     * @return bool Is the time in the past or not
     */
    public static function inFuture($date, $time = null)
    {
        if (is_null($time)) {
            $time = self::time();
        }
        if (!is_int($date)) {
            $date = self::strtotime($date);
        }

        return $time < $date;
    }

    /**
     * Is it now.
     *
     * @param varies $date
     * @param int    $time (optional)
     *
     * @return bool Is the time in the past or not
     */
    public static function isPresent($date, $time = null)
    {
        if (is_null($time)) {
            $time = self::time();
        }
        if (!is_int($date)) {
            $date = self::strtotime($date);
        }

        return $time === $date;
    }

    /**
     * [[@doctodo method_description:isToday]].
     *
     * @return [[@doctodo return_type:isToday]] [[@doctodo return_description:isToday]]
     */
    public static function isToday($date, $time = null)
    {
        if (is_null($time)) {
            $time = self::time();
        }
        if (!is_int($date)) {
            $date = self::strtotime($date);
        }

        return date("Y-m-d", $time) === date("Y-m-d", $date);
    }

    /**
     * [[@doctodo method_description:relativeDate]].
     *
     * @param varies  $mdate         date to compare
     * @param int     $time          Unix timestamp         (optional)
     * @param unknown $showTime      (optional)
     * @param unknown $defaultFormat (optional)
     *
     * @return string Formatted relative date
     */
    public static function relativeDate($mdate, $time = null, $showTime = false, $defaultFormat = 'F j, Y \a\t g:i A')
    {
        if (!is_int($mdate)) {
            $mdate = self::strtotime($mdate);
        }
        $pre = '';
        $post = ' ago';
        if (is_null($time)) {
            $time = self::time();
        }
        $diff = $time - $mdate;
        $tomorrowString = 'yesterday';
        $today = self::date("Y-m-d", $mdate);
        $tomorrowDate = self::date("Y-m-d", self::strtotime("-1 day"));
        if ($diff < 0) {
            $pre = 'in ';
            $post = '';
            $tomorrowString = 'tomorrow';
            $tomorrowDate = self::date("Y-m-d", self::strtotime("+1 day"));
        } elseif ($diff == 0) {
            return 'now';
        }
        $diff = abs($diff);
        if ($diff > 172800) { // two days

            return self::date($defaultFormat, $mdate);
        } elseif ($today === $tomorrowDate) { // yesterday
            if ($showTime) {
                return $tomorrowString . ' at ' . self::date('g:iA', $mdate);
            } else {
                return $tomorrowString;
            }
        } elseif ($diff > 3600) { // hours
            $unit = 'hour';
            $n = round($diff / 3600);
        } elseif ($diff > 60) { // minutes
            $unit = 'minute';
            $n = round($diff / 60);
        } else { // seconds
            $unit = 'second';
            $n = $diff;
        }
        if ($n > 1) {
            $unit .= 's';
        }

        return $pre . $n . ' ' . $unit . $post;
    }

    /**
     * Nice time difference.
     *
     * @param string $d1           Date string
     * @param string $d2           Date string
     * @param int    $limitPeriods Number of periods to show (optional)
     *
     * @return string Nice duration
     */
    public static function niceTimeDiff($d1, $d2, $limitPeriods = 2)
    {
        if (!is_int($d1)) {
            $d1 = self::strtotime($d1);
        }
        if (!is_int($d2)) {
            $d2 = self::strtotime($d2);
        }
        if (empty($d1) or empty($d2)) {
            return false;
        }
        $max = max($d1, $d2);
        $min = min($d1, $d2);
        $diff = $max - $min;

        return self::niceDuration($diff, $limitPeriods);
    }

    /**
     * [[@doctodo method_description:shortDuration]].
     *
     * @return [[@doctodo return_type:shortDuration]] [[@doctodo return_description:shortDuration]]
     */
    public static function shortDuration($seconds)
    {
        if ($seconds > (60*60)) { // hours
            return round($seconds/(60*60)) . 'h';
        } elseif ($seconds > 60) { // minutes
            return round($seconds/(60)) . 'm';
        } elseif ($seconds >= 1) { // seconds
            return round($seconds) . 's';
        } else {
            return round($seconds*100, 1) . 'ms';
        }
    }
    /**
     * Get the human string of a duration.
     *
     * @param int  $seconds      Number of seconds
     * @param int  $limitPeriods (optional)
     * @param bool $zeros        Show zeros    (optional; default false)
     *
     * @return string Nice human duration
     */
    public static function niceDuration($seconds, $limitPeriods = 7, $zeros = false)
    {
        // Define time periods
        $periods = [
            'years'     => 31556926,
            'months'    => 2629743,
            //'weeks'     => 604800,
            'days'      => 86400,
            'hours'     => 3600,
            'minutes'   => 60,
            'seconds'   => 1,
        ];

        // Break into periods
        $seconds = (float) $seconds;
        $segments = [];
        foreach ($periods as $period => $value) {
            if ($limitPeriods >= 1) {
                $count = floor($seconds / $value);
                $seconds = $seconds % $value;
            } else {
                $count = round($seconds / $value);
                $seconds = 0;
            }
            if (($count == 0 && !$zeros) or $limitPeriods < 1) {
                continue;
            }
            $segments[strtolower($period)] = $count;

            $limitPeriods--;
        }

        // Build the string
        $string = [];
        foreach ($segments as $key => $value) {
            $segment_name = substr($key, 0, -1);
            $segment = $value . ' ' . $segment_name;
            if ($value != 1) {
                $segment .= 's';
            }
            $string[] = $segment;
        }

        return implode(', ', $string);
    }
}
