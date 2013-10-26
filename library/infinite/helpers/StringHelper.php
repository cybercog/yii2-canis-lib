<?php
/**
 * library/helpers/ArrayHelper.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\helpers;

class StringHelper extends \yii\helpers\StringHelper
{
	static public function parseText($text, $extra = array())
	{
		if (is_object($text)) { return $text; }
		preg_match_all("/\%\%([^\%]+)\%\%/i", $text, $extracted);
		$replace = array();
		if (!empty($extracted)) {
			foreach ($extracted[0] as $k => $v) {
				$key = '/'.$v.'/';
				$parse = $extracted[1][$k];
				$replace[$key] = null;
				if (isset($extra[$parse])) {
					$replace[$key] = $extra[$parse];
				}
				$instructions = explode('.', $parse);
				$top = array_shift($instructions);
				switch($top) {
					case 'type':
						if (count($instructions) >= 2) {
							$placementType = array_shift($instructions);
							$placementItem = Yii::app()->types->get($placementType);
							while (!empty($placementItem) AND is_object($placementItem) AND !empty($instructions)) {
								$nextInstruction = array_shift($instructions);
								if (isset($placementItem->{$nextInstruction})) {
									$placementItem = $placementItem->{$nextInstruction};
								} else {
									$placementItem = null;
								}
							}
							$replace[$key] = (string)$placementItem;
						}
					break;
				}
			}
		} else {
			return $text;
		}
		return trim(preg_replace(array_keys($replace), array_values($replace), $text));
	}
}
?>