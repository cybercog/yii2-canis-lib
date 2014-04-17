<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\helpers;

/**
 * StringHelper [@doctodo write class description for StringHelper]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class StringHelper extends \yii\helpers\StringHelper
{
    /**
     * __method_parseInstructions_description__
     * @return __return_parseInstructions_type__ __return_parseInstructions_description__
     */
    static public function parseInstructions()
    {
        return [];
    }

    /**
     * __method_neighborWordCombos_description__
     * @param __param_parts_type__ $parts __param_parts_description__
     * @return __return_neighborWordCombos_type__ __return_neighborWordCombos_description__
     */
    static public function neighborWordCombos($parts)
    {
        if (is_string($parts)) {
            $parts = explode(' ', $parts);
        }
        $newParts = [];
        $increaseBase = false;
        $lower = 0;
        $higher = count($parts);
        $i = 2;
        if ($i > $higher) {
            return $parts;
        }
        while ($lower < count($parts)-1) {
            if ($i === count($parts)) {
                $increaseBase = true;
            }
            $newParts[] = implode(' ', array_slice($parts, $lower, $i - $lower));
            if ($increaseBase) {
                $lower++;
            } else {
                $i++;
            }
        }

        return array_unique(array_merge($newParts, $parts));
    }

    /**
     * __method_parseText_description__
     * @param __param_text_type__ $text __param_text_description__
     * @param array $variables __param_variables_description__ [optional]
     * @return __return_parseText_type__ __return_parseText_description__
     */
    static public function parseText($text, $variables = [])
    {
        if (is_object($text)) { return $text; }
        preg_match_all("/\%\%([^\%]+)\%\%/i", $text, $extracted);
        $replace = [];
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
