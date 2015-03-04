<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\helpers;

use Yii;
use jom\SubnationalDivisions;

/**
 * Locations [@doctodo write class description for Locations].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Locations extends \infinite\base\Component
{
    /**
     * __method_countryList_description__.
     *
     * @return __return_countryList_type__ __return_countryList_description__
     */
    public static function countryList()
    {
        $englishPath = Yii::getAlias("@vendor/umpirsky/country-list/country/cldr/en/country.php");
        $phpCountriesPath = Yii::getAlias("@vendor/umpirsky/country-list/cldr/country/".Yii::$app->language."/country.php");
        if (file_exists($phpCountriesPath)) {
            return include $phpCountriesPath;
        } elseif (file_exists($englishPath)) {
            return include $englishPath;
        }

        return false;
    }

    /**
     * __method_allSubnationalDivisions_description__.
     *
     * @param boolean $shortName __param_shortName_description__ [optional]
     * @param boolean $flat      __param_flat_description__ [optional]
     *
     * @return __return_allSubnationalDivisions_type__ __return_allSubnationalDivisions_description__
     */
    public static function allSubnationalDivisions($shortName = false, $flat = false)
    {
        $subdivisions = SubnationalDivisions::getAll($flat);

        return $subdivisions;
    }
}
