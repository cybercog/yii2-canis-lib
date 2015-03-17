<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\web\assetBundles;

/**
 * AssetBundle [[@doctodo class_description:canis\web\assetBundles\AssetBundle]].
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
