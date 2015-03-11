<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\web\assetBundles;

/**
 * TimeAgoAsset [[@doctodo class_description:teal\web\assetBundles\TimeAgoAsset]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class TimeAgoAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@bower/smart-time-ago/lib';
    /**
     * @inheritdoc
     */
    public $js = ['timeago.js'];
}
