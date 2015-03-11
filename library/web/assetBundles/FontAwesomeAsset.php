<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\web\assetBundles;

/**
 * FontAwesomeAsset [[@doctodo class_description:teal\web\assetBundles\FontAwesomeAsset]].
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
