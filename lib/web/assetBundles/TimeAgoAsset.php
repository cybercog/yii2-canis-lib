<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\web\assetBundles;

/**
 * TimeAgoAsset [[@doctodo class_description:canis\web\assetBundles\TimeAgoAsset]].
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
