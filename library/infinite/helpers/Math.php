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
 * Math [[@doctodo class_description:infinite\helpers\Math]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Math extends \yii\base\Object
{
    /**
     * [[@doctodo method_description:removeOutliers]].
     *
     * @param integer $magnitude [[@doctodo param_description:magnitude]] [optional]
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
