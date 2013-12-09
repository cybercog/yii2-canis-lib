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
	static public function parseInstructions() {
		return [];
	}

	static public function parseText($text, $variables = array())
	{
		if (is_object($text)) { return $text; }
		preg_match_all("/\%\%([^\%]+)\%\%/i", $text, $extracted);
		$replace = array();
		$parseInstructionSet = static::parseInstructions();
		if (!empty($extracted)) {
			foreach ($extracted[0] as $k => $v) {
				$key = '/'.$v.'/';
				$parse = $extracted[1][$k];
				$replace[$key] = null;
				$instructions = explode('.', $parse);
				$top = array_shift($instructions);
				if (isset($parseInstructionSet[$top])) {
					$replace[$key] = $parseInstructionSet[$top]($instructions);
				} elseif (isset($variables[$top])) {
					$placementItem = $variables[$top];
					while (!empty($placementItem) && is_object($placementItem) && !empty($instructions)) {
						$nextInstruction = array_shift($instructions);
						if (isset($placementItem->{$nextInstruction})) {
							$placementItem = $placementItem->{$nextInstruction};
						} else {
							$placementItem = null;
						}
					}
					if (!is_null($placementItem)) {
						$replace[$key] = (string) $placementItem;
					}
				}
			}
		} else {
			return $text;
		}
		return trim(preg_replace(array_keys($replace), array_values($replace), $text));
	}
}
?>