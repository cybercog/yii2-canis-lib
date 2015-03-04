<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\assetBundles;

if (!defined('INFINITE_APP_GOOGLE_API_BROWSER_KEY')) {
    define('INFINITE_APP_GOOGLE_API_BROWSER_KEY', 'NO_API_KEY_SPECIFIED');
}

/**
 * FontAwesomeAsset [@doctodo write class description for FontAwesomeAsset].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class GoogleMapsAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $js = [
        'https://maps.googleapis.com/maps/api/js?key=' . INFINITE_APP_GOOGLE_API_BROWSER_KEY,
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }
}
