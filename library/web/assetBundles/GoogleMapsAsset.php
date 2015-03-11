<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\web\assetBundles;

if (!defined('TEAL_APP_GOOGLE_API_BROWSER_KEY')) {
    define('TEAL_APP_GOOGLE_API_BROWSER_KEY', 'NO_API_KEY_SPECIFIED');
}

/**
 * GoogleMapsAsset [[@doctodo class_description:teal\web\assetBundles\GoogleMapsAsset]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class GoogleMapsAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $js = [
        'https://maps.googleapis.com/maps/api/js?key=' . TEAL_APP_GOOGLE_API_BROWSER_KEY,
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }
}
