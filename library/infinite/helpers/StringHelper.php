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
 * StringHelper [[@doctodo class_description:infinite\helpers\StringHelper]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class StringHelper extends \yii\helpers\StringHelper
{
    /**
     * [[@doctodo method_description:parseInstructions]].
     *
     * @return [[@doctodo return_type:parseInstructions]] [[@doctodo return_description:parseInstructions]]
     */
    public static function parseInstructions()
    {
        return [];
    }

    /**
     * [[@doctodo method_description:neighborWordCombos]].
     *
     * @return [[@doctodo return_type:neighborWordCombos]] [[@doctodo return_description:neighborWordCombos]]
     */
    public static function neighborWordCombos($parts)
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
     * [[@doctodo method_description:humanFilesize]].
     *
     * @param integer $decimals [[@doctodo param_description:decimals]] [optional]
     * @link http://php.net/manual/en/function.filesize.php#106569
     * @return [[@doctodo return_type:humanFilesize]] [[@doctodo return_description:humanFilesize]]
     */
    public static function humanFilesize($bytes, $decimals = 1)
    {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }

    /**
     * [[@doctodo method_description:parseText]].
     *
     * @param array $variables [[@doctodo param_description:variables]] [optional]
     *
     * @return [[@doctodo return_type:parseText]] [[@doctodo return_description:parseText]]
     */
    public static function parseText($text, $variables = [])
    {
        if (is_object($text)) {
            return $text;
        }
        preg_match_all("/\%\%([^\%]+)\%\%/i", $text, $extracted);
        $replace = [];
        $parseInstructionSet = static::parseInstructions();
        if (!empty($extracted)) {
            foreach ($extracted[0] as $k => $v) {
                $key = '/' . $v . '/';
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

    /**
     * [[@doctodo method_description:compareStrings]].
     *
     * @param array $weights [[@doctodo param_description:weights]] [optional]
     *
     * @return [[@doctodo return_type:compareStrings]] [[@doctodo return_description:compareStrings]]
     */
    public static function compareStrings($str1, $str2, $weights = [])
    {
        if ($str1 === $str2) {
            return 100;
        }
        $score = 0;
        $total = 0;

        // set options
        $defaultWeights = [
            'similar_text' => 70,
            'levenshtein' => 30,
        ];
        $weights = array_merge($defaultWeights, $weights);

        if (strlen($str2) > strlen($str1)) {
            list($str1, $str2) = [$str2, $str1];
        }
        $maxLength = strlen($str1);

        if (empty($maxLength)) {
            return 100;
        }

        // calculate
        similar_text($str1, $str2, $perc);
        $score += (($perc / 100) * $weights['similar_text']);
        $total += $weights['similar_text'];

        if (strlen($str1) < 255 && strlen($str2) < 255) {
            $levScore = ($maxLength - levenshtein($str1, $str2)) / $maxLength;
            $score += ($levScore * $weights['levenshtein']);
            $total += $weights['levenshtein'];
        }

        return ($score / $total) * 100;
    }

    /**
     * [[@doctodo method_description:spellNumber]].
     *
     * @return [[@doctodo return_type:spellNumber]] [[@doctodo return_description:spellNumber]]
     */
    public static function spellNumber($number)
    {
        $a = new \NumberFormatter(Yii::$app->language, \NumberFormatter::SPELLOUT);

        return $a->format($number);
    }
}
