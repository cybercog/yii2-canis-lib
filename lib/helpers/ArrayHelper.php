<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\helpers;

/**
 * ArrayHelper [[@doctodo class_description:teal\helpers\ArrayHelper]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ArrayHelper extends \yii\helpers\ArrayHelper
{
    /**
     * [[@doctodo method_description:fingerprint]].
     *
     * @param [[@doctodo param_type:item]] $item [[@doctodo param_description:item]]
     *
     * @return [[@doctodo return_type:fingerprint]] [[@doctodo return_description:fingerprint]]
     */
    public static function fingerprint($item)
    {
        if (is_array($item)) {
            ksort($item);
            $fingers = [];
            foreach ($item as $k => $i) {
                $fingers[$k] = static::fingerprint($i);
            }

            return md5(json_encode($fingers));
        } elseif (is_object($item)) {
            if ($item instanceof \yii\db\ActiveRecord) {
                return $item->primaryKey;
            }
        }

        return md5(json_encode($item));
    }

    /**
     * @inheritdoc
     */
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
     * 	Extends ArrayHelper's map capability by letting it map to its parent object.
     *
     * 	@see \yii\helpers\ArrayHelper:map
     *
     * @param [[@doctodo param_type:array]] $array [[@doctodo param_description:array]]
     * @param [[@doctodo param_type:from]]  $from  [[@doctodo param_description:from]]
     * @param [[@doctodo param_type:to]]    $to    [[@doctodo param_description:to]] [optional]
     * @param [[@doctodo param_type:group]] $group [[@doctodo param_description:group]] [optional]
     *
     * @return [[@doctodo return_type:map]] [[@doctodo return_description:map]]
     */
    public static function map($array, $from, $to = null, $group = null)
    {
        if (is_null($to)) {
            $to = function ($element, $defaultValue) { return $element; };
        }

        return parent::map($array, $from, $to, $group);
    }

    /**
     * [[@doctodo method_description:cartesian]].
     *
     * @param [[@doctodo param_type:arrays]] $arrays [[@doctodo param_description:arrays]]
     * @param boolean                        $first  [[@doctodo param_description:first]] [optional]
     *
     * @return [[@doctodo return_type:cartesian]] [[@doctodo return_description:cartesian]]
     */
    public static function cartesian($arrays, $first = true)
    {
        if (empty($arrays)) {
            return [];
        }
        $arrayKeys = array_keys($arrays);
        $firstKey  = $arrayKeys[0];
        $arrayLength = count($arrays);
        if (!$first && $arrayLength === 1) {
            return $arrays[$firstKey];
        }
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

    /**
     * [[@doctodo method_description:permutations]].
     *
     * @param [[@doctodo param_type:array]] $array [[@doctodo param_description:array]]
     * @param integer                       $min   [[@doctodo param_description:min]] [optional]
     * @param boolean                       $max   [[@doctodo param_description:max]] [optional]
     *
     * @return [[@doctodo return_type:permutations]] [[@doctodo return_description:permutations]]
     */
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
            if ($min !== false and count($result) < $min) {
                unset($results[$k]);
            } elseif ($max !== false and count($result) > $max) {
                unset($results[$k]);
            }
        }

        return array_values($results);
    }
}
