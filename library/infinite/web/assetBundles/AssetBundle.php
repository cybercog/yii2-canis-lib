<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\assetBundles;

/**
 * AssetBundle [@doctodo write class description for AssetBundle].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class AssetBundle extends \yii\web\AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (defined('YII_ENV') && YII_ENV === 'dev') {
            $this->publishOptions['forceCopy'] = true;
        }
    }
}
