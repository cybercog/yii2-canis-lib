<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\helpers;

use teal\web\View;
use Yii;
use yii\base\InvalidParamException;

/**
 * Html [[@doctodo class_description:teal\helpers\Html]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Html extends \yii\helpers\Html
{
    // public static function onLoadJsBlock($script, $key = null) {
    // 	if (Yii::$app->request->isAjax) {
    // 		echo self::script($script);
    // 	} else {
    // 		self::registerJsBlock($script, View::POS_READY, $key);
    // 	}
    // }

    /**
     * [[@doctodo method_description:registerJsBlock]].
     *
     * @param [[@doctodo param_type:script]] $script   [[@doctodo param_description:script]]
     * @param integer                        $position [[@doctodo param_description:position]] [optional]
     * @param [[@doctodo param_type:key]]    $key      [[@doctodo param_description:key]] [optional]
     *
     * @return [[@doctodo return_type:registerJsBlock]] [[@doctodo return_description:registerJsBlock]]
     */
    public static function registerJsBlock($script, $position = View::POS_READY, $key = null)
    {
        return Yii::$app->controller->view->registerJs($script, $position, $key);
    }

    /**
     * [[@doctodo method_description:addSubAttribute]].
     *
     * @param [[@doctodo param_type:attribute]]    $attribute    [[@doctodo param_description:attribute]]
     * @param [[@doctodo param_type:subattribute]] $subattribute [[@doctodo param_description:subattribute]]
     *
     * @throws InvalidParamException [[@doctodo exception_description:InvalidParamException]]
     * @return [[@doctodo return_type:addSubAttribute]] [[@doctodo return_description:addSubAttribute]]
     *
     */
    public static function addSubAttribute($attribute, $subattribute)
    {
        if (!preg_match('/(^|.*\])(\w+)(\[.*|$)/', $attribute, $matches)) {
            throw new InvalidParamException('Attribute name must contain word characters only.');
        }
        $prefix = $matches[1];
        $attribute = $matches[2];

        return $prefix . "[{$attribute}]" . $subattribute;
    }

    /**
     * [[@doctodo method_description:addPreAttribute]].
     *
     * @param [[@doctodo param_type:attribute]]    $attribute    [[@doctodo param_description:attribute]]
     * @param [[@doctodo param_type:preattribute]] $preattribute [[@doctodo param_description:preattribute]]
     *
     * @throws InvalidParamException [[@doctodo exception_description:InvalidParamException]]
     * @return [[@doctodo return_type:addPreAttribute]] [[@doctodo return_description:addPreAttribute]]
     *
     */
    public static function addPreAttribute($attribute, $preattribute)
    {
        if (!preg_match('/(^|.*\])(\w+)(\[.*|$)/', $attribute, $matches)) {
            throw new InvalidParamException('Attribute name must contain word characters only.');
        }
        $prefix = $matches[1];
        $attribute = $matches[2];

        return $prefix . "[{$preattribute}]" . $attribute;
    }

    /**
     * [[@doctodo method_description:changeAttribute]].
     *
     * @param [[@doctodo param_type:attribute]]    $attribute    [[@doctodo param_description:attribute]]
     * @param [[@doctodo param_type:newAttribute]] $newAttribute [[@doctodo param_description:newAttribute]]
     *
     * @throws InvalidParamException [[@doctodo exception_description:InvalidParamException]]
     * @return [[@doctodo return_type:changeAttribute]] [[@doctodo return_description:changeAttribute]]
     *
     */
    public static function changeAttribute($attribute, $newAttribute)
    {
        if (!preg_match('/(^|.*\])(\w+)(\[.*|$)/', $attribute, $matches)) {
            throw new InvalidParamException('Attribute name must contain word characters only ' . $attribute . '.');
        }
        $prefix = $matches[1];

        return $prefix . $newAttribute;
    }

    /**
     * [[@doctodo method_description:pageHeader]].
     *
     * @param [[@doctodo param_type:title]] $title   [[@doctodo param_description:title]]
     * @param array                         $options [[@doctodo param_description:options]] [optional]
     *
     * @return [[@doctodo return_type:pageHeader]] [[@doctodo return_description:pageHeader]]
     */
    public static function pageHeader($title, $options = [])
    {
        if (!isset($options['htmlOptions'])) {
            $options['htmlOptions'] = [];
        }
        if (!isset($options['wrapperOptions'])) {
            $options['wrapperOptions'] = [];
        }
        if (!isset($options['level'])) {
            $options['level'] = 2;
        }
        if (!isset($options['wrapperTag'])) {
            $options['wrapperTag'] = 'div';
        }
        static::addCssClass($options['htmlOptions'], 'teal-page-title');
        static::addCssClass($options['wrapperOptions'], 'panelOptions');

        return static::tag($options['wrapperTag'], static::tag('h' . $options['level'], $title, $options['htmlOptions']), $options['wrapperOptions']);
    }

    /**
     * [[@doctodo method_description:buttonGroup]].
     *
     * @param [[@doctodo param_type:items]] $items       [[@doctodo param_description:items]]
     * @param array                         $htmlOptions [[@doctodo param_description:htmlOptions]] [optional]
     *
     * @return [[@doctodo return_type:buttonGroup]] [[@doctodo return_description:buttonGroup]]
     */
    public static function buttonGroup($items, $htmlOptions = [])
    {
        if (empty($items)) {
            return;
        }
        $o = [];
        static::addCssClass($htmlOptions, 'btn-group');
        $o[] = static::beginTag('div', $htmlOptions);
        foreach ($items as $item) {
            if (!isset($item['label'])) {
                continue;
            }
            if (!isset($item['htmlOptions'])) {
                $item['htmlOptions'] = [];
            }
            if (!isset($item['state'])) {
                $item['state'] = 'default';
            }
            static::addCssClass($item['htmlOptions'], 'btn btn-' . $item['state']);
            $o[] = static::a($item['label'], $item['url'], $item['htmlOptions']);
        }
        $o[] = static::endTag('div');

        return implode($o);
    }
}
