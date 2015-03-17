<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\web\assetBundles;

/**
 * CanisRestDrawAsset [[@doctodo class_description:canis\web\assetBundles\CanisRestDrawAsset]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 2.0
 */
class CanisRestDrawAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@canis/assets/restDraw';
    /**
     * @inheritdoc
     */
    public $css = [
        'css/canis.restDraw.css',
    ];
    /**
     * @inheritdoc
     */
    public $js = [
        'js/canis.restDraw.js',
    ];
    /**
     * @inheritdoc
     */
    public $depends = [
        'canis\web\assetBundles\CanisAsset',
    ];
}
