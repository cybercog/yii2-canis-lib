<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\assetBundles;

/**
 * TimeAgoAsset [[@doctodo class_description:infinite\web\assetBundles\TimeAgoAsset]].
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
