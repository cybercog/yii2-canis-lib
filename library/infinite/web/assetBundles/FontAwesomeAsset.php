<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\assetBundles;

/**
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
