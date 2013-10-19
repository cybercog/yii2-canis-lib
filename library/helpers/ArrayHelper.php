<?php
/**
 * library/helpers/ArrayHelper.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\helpers;

class ArrayHelper extends \yii\helpers\ArrayHelper {
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

	public static function cartesian($arrays, $first = true) {
		if (empty($arrays)) { return array(); }
		$arrayKeys = array_keys($arrays);
		$firstKey  = $arrayKeys[0];
		$arrayLength = count($arrays);
		if (!$first && $arrayLength === 1) { return $arrays[$firstKey]; }
		$restKeys = array_slice($arrayKeys, 1, $arrayLength);
		$restKeyValues = array();
		foreach ($restKeys as $v) {
			$restKeyValues[$v] = $arrays[$v];
		}
		$rest = self::cartesian($restKeyValues, false);
		$result = array();
		if (empty($rest)) {
			foreach ($arrays[$firstKey] as $primary) {
				$rp = array();
				if (is_numeric($firstKey)) {
					array_push($rp, $primary);
				} else {
					$rp[$firstKey] = $primary;
				}
				$result[] = $rp;
			}
		}elseif (empty($arrays[$firstKey])) {
			foreach ($rest as $key => $variation) {
				if (is_array($variation)) {
					$rp = $variation;
				} else {
					$rp = array();
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
					$rpb = array();
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

	public static function permutations($array, $min = 1, $max = false){
		$results = array(array( ));
		$ignoreKeys = array();
    	foreach ($array as $key => $element) {
	        foreach ($results as $rkey => $combination) {
	        	$newSet = array_merge(array($key => $element), $combination);
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


?>
