<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\assetBundles;

class AssetBundle extends \yii\web\AssetBundle
{
    public function init()
    {
        parent::init();
        if (defined('YII_ENV') && YII_ENV === 'dev') {
            $this->publishOptions['forceCopy'] = true;
        }
    }
}
