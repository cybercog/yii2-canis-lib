<?php
namespace infinite\helpers;

use Yii;

use jom\SubnationalDivisions;

class Locations extends \infinite\base\Component
{
	public static function countryList()
	{
		$englishPath = Yii::getAlias("@vendor/umpirsky/country-list/country/cldr/en/country.php");
		$phpCountriesPath = Yii::getAlias("@vendor/umpirsky/country-list/cldr/country/". Yii::$app->language ."/country.php");
		if (file_exists($phpCountriesPath)) {
			return include($phpCountriesPath);
		} elseif (file_exists($englishPath)) {
			return include($englishPath);
		}
		return false;
	}

	public static function allSubnationalDivisions($shortName = false, $flat = false)
	{
		$subdivisions = SubnationalDivisions::getAll($flat);
		return $subdivisions;
	}
}