<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\web\assetBundles;

/**
 * AssetBundle [[@doctodo class_description:teal\web\assetBundles\AssetBundle]].
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
