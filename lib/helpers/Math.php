<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\helpers;

use Yii;

/**
 * Math [[@doctodo class_description:canis\helpers\Math]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Math extends \yii\base\Object
{
    /**
     * [[@doctodo method_description:removeOutliers]].
     *
     * @param [[@doctodo param_type:dataset]] $dataset   [[@doctodo param_description:dataset]]
     * @param integer                         $magnitude [[@doctodo param_description:magnitude]] [optional]
     *
     * @return [[@doctodo return_type:removeOutliers]] [[@doctodo return_description:removeOutliers]]
     */
    public static function removeOutliers($dataset, $magnitude = 1)
    {
        $count = count($dataset);
        $mean = array_sum($dataset) / $count;
        $deviation = sqrt(array_sum(array_map(function ($x, $mean) { return pow($x - $mean, 2); }, $dataset, array_fill(0, $count, $mean))) / $count) * $magnitude;

        return array_filter($dataset, function ($x) use ($mean, $deviation) { return ($x <= $mean + $deviation && $x >= $mean - $deviation); });
    }
}
