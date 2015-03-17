<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\web\assetBundles;

/**
 * FontAwesomeAsset [[@doctodo class_description:canis\web\assetBundles\FontAwesomeAsset]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class FontAwesomeAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@vendor/fortawesome/font-awesome';
    /**
     * @inheritdoc
     */
    public $css = [
        'css/font-awesome.min.css',
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->publishOptions['beforeCopy'] = function ($from, $to) {
            $acceptable = ['css', 'fonts'];

            return in_array(basename($from), $acceptable) || in_array(basename(dirname($from)), $acceptable);
        };
        parent::init();
    }
}
