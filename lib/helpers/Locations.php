<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\helpers;

use jom\SubnationalDivisions;
use Yii;

/**
 * Locations [[@doctodo class_description:canis\helpers\Locations]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Locations extends \canis\base\Component
{
    /**
     * [[@doctodo method_description:countryList]].
     *
     * @return [[@doctodo return_type:countryList]] [[@doctodo return_description:countryList]]
     */
    public static function countryList()
    {
        $englishPath = Yii::getAlias("@vendor/umpirsky/country-list/country/cldr/en/country.php");
        $phpCountriesPath = Yii::getAlias("@vendor/umpirsky/country-list/cldr/country/" . Yii::$app->language . "/country.php");
        if (file_exists($phpCountriesPath)) {
            return include $phpCountriesPath;
        } elseif (file_exists($englishPath)) {
            return include $englishPath;
        }

        return false;
    }

    /**
     * [[@doctodo method_description:allSubnationalDivisions]].
     *
     * @param boolean $shortName [[@doctodo param_description:shortName]] [optional]
     * @param boolean $flat      [[@doctodo param_description:flat]] [optional]
     *
     * @return [[@doctodo return_type:allSubnationalDivisions]] [[@doctodo return_description:allSubnationalDivisions]]
     */
    public static function allSubnationalDivisions($shortName = false, $flat = false)
    {
        $subdivisions = SubnationalDivisions::getAll($flat);

        return $subdivisions;
    }
}
