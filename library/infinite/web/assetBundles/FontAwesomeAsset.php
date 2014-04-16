<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\assetBundles;

class FontAwesomeAsset extends AssetBundle
{
    public $sourcePath = '@vendor/fortawesome/font-awesome';
    public $css = [
        'css/font-awesome.min.css',
    ];

    public function init()
    {
        $this->publishOptions['beforeCopy'] = function ($from, $to) {
            $acceptable = ['css', 'fonts'];

            return in_array(basename($from), $acceptable) || in_array(basename(dirname($from)), $acceptable);
        };
        parent::init();
    }
}
