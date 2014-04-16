<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\helpers;

class ArrayHelper extends \yii\helpers\ArrayHelper
{
    public static function getValue($array, $key, $default = null)
    {
        if (strstr($key, '.') !== false) {
            $path = explode('.', $key);
            $topValue = self::getValue($array, array_shift($path), $default);
            if (!is_object($topValue) && !is_array($topValue)) {
                return $default;
            } else {
                return parent::getValue($topValue, implode('.', $path));
            }
        }

        return parent::getValue($array, $key, $default);
    }

    /**
    * 	Extends ArrayHelper's map capability by letting it map to its parent object
    * 	@see \yii\helpers\ArrayHelper:map
    */
    public static function map($array, $from, $to = null, $group = null)
    {
        if (is_null($to)) {
            $to = function ($element, $defaultValue) { return $element; };
        }

        return parent::map($array, $from, $to, $group);
    }

    public static function cartesian($arrays, $first = true)
    {
        if (empty($arrays)) { return []; }
        $arrayKeys = array_keys($arrays);
        $firstKey  = $arrayKeys[0];
        $arrayLength = count($arrays);
        if (!$first && $arrayLength === 1) { return $arrays[$firstKey]; }
        $restKeys = array_slice($arrayKeys, 1, $arrayLength);
        $restKeyValues = [];
        foreach ($restKeys as $v) {
            $restKeyValues[$v] = $arrays[$v];
        }
        $rest = self::cartesian($restKeyValues, false);
        $result = [];
        if (empty($rest)) {
            foreach ($arrays[$firstKey] as $primary) {
                $rp = [];
                if (is_numeric($firstKey)) {
                    array_push($rp, $primary);
                } else {
                    $rp[$firstKey] = $primary;
                }
                $result[] = $rp;
            }
        } elseif (empty($arrays[$firstKey])) {
            foreach ($rest as $key => $variation) {
                if (is_array($variation)) {
                    $rp = $variation;
                } else {
                    $rp = [];
                    if (is_numeric($key)) {
                        if (is_numeric($restKeys[0])) {
                            array_push($rp, $variation);
                        } else {
                            $rp[$restKeys[0]] = $variation;
                        }
                    } else {
                        $rp[$key] = $variation;
                    }
                }
                $result[] = $rp;
            }
        } else {
            foreach ($rest as $key => $variation) {
                if (is_array($variation)) {
                    $rpb = $variation;
                } else {
                    $rpb = [];
                    if (is_numeric($key)) {
                        if (is_numeric($restKeys[0])) {
                            array_push($rpb, $variation);
                        } else {
                            $rpb[$restKeys[0]] = $variation;
                        }
                    } else {
                        $rpb[$key] = $variation;
                    }
                }
                foreach ($arrays[$firstKey] as $primary) {
                    $rp = $rpb;
                    if (is_numeric($firstKey)) {
                        array_push($rp, $primary);
                    } else {
                        $rp[$firstKey] = $primary;
                    }
                    $result[] = $rp;
                }
            }
        }

        return $result;
    }

    public static function permutations($array, $min = 1, $max = false)
    {
        $results = [[ ]];
        $ignoreKeys = [];
        foreach ($array as $key => $element) {
            foreach ($results as $rkey => $combination) {
                $newSet = array_merge([$key => $element], $combination);
                array_push($results, $newSet);
            }
        }

        foreach ($results as $k => $result) {
            if ($min !== false AND count($result) < $min) {
                unset($results[$k]);
            } elseif ($max !== false AND count($result) > $max) {
                unset($results[$k]);
            }
        }

        return array_values($results);
    }
}
