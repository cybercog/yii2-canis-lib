<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\web\assetBundles;

if (!defined('CANIS_APP_GOOGLE_API_BROWSER_KEY')) {
    define('CANIS_APP_GOOGLE_API_BROWSER_KEY', 'NO_API_KEY_SPECIFIED');
}

/**
 * GoogleMapsAsset [[@doctodo class_description:canis\web\assetBundles\GoogleMapsAsset]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class GoogleMapsAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $js = [
        'https://maps.googleapis.com/maps/api/js?key=' . CANIS_APP_GOOGLE_API_BROWSER_KEY,
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }
}
