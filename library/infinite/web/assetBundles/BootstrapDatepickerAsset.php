<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\assetBundles;

/**
 * BootstrapDatepickerAsset [[@doctodo class_description:infinite\web\assetBundles\BootstrapDatepickerAsset]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * @since 2.0
 */
class BootstrapDatepickerAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@vendor/eternicode/bootstrap-datepicker';
    /**
     * @inheritdoc
     */
    //public $css = ['css/datepicker.css'];
    /**
     * @inheritdoc
     */
    public $js = ['js/bootstrap-datepicker.js'];
    /**
     * @inheritdoc
     */
    public $depends = ['yii\web\JqueryAsset', 'yii\bootstrap\BootstrapAsset'];

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->publishOptions['beforeCopy'] = function ($from, $to) {
            $acceptable = ['css', 'js'];

            return in_array(basename($from), $acceptable) || in_array(basename(dirname($from)), $acceptable);
        };
        parent::init();
    }
}
