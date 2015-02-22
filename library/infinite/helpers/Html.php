<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\helpers;

use Yii;
use yii\base\InvalidParamException;

use infinite\web\View;
/**
 * Html [@doctodo write class description for Html]
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
     * __method_registerJsBlock_description__
     * @param __param_script_type__ $script __param_script_description__
     * @param integer $position __param_position_description__ [optional]
     * @param __param_key_type__ $key __param_key_description__ [optional]
     * @return __return_registerJsBlock_type__ __return_registerJsBlock_description__
     */
    public static function registerJsBlock($script, $position = View::POS_READY, $key = null)
    {
        return Yii::$app->controller->view->registerJs($script, $position, $key);
    }

    /**
     * __method_addSubAttribute_description__
     * @param __param_attribute_type__ $attribute __param_attribute_description__
     * @param __param_subattribute_type__ $subattribute __param_subattribute_description__
     * @return __return_addSubAttribute_type__ __return_addSubAttribute_description__
     * @throws InvalidParamException __exception_InvalidParamException_description__
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
     * __method_addPreAttribute_description__
     * @param __param_attribute_type__ $attribute __param_attribute_description__
     * @param __param_preattribute_type__ $preattribute __param_preattribute_description__
     * @return __return_addPreAttribute_type__ __return_addPreAttribute_description__
     * @throws InvalidParamException __exception_InvalidParamException_description__
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
     * __method_changeAttribute_description__
     * @param __param_attribute_type__ $attribute __param_attribute_description__
     * @param __param_newAttribute_type__ $newAttribute __param_newAttribute_description__
     * @return __return_changeAttribute_type__ __return_changeAttribute_description__
     * @throws InvalidParamException __exception_InvalidParamException_description__
     */
    public static function changeAttribute($attribute, $newAttribute)
    {
        if (!preg_match('/(^|.*\])(\w+)(\[.*|$)/', $attribute, $matches)) {
            throw new InvalidParamException('Attribute name must contain word characters only '. $attribute.'.');
        }
        $prefix = $matches[1];

        return $prefix . $newAttribute;
    }

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
        static::addCssClass($options['htmlOptions'], 'infinite-page-title');
        static::addCssClass($options['wrapperOptions'], 'panelOptions');
        return static::tag($options['wrapperTag'], static::tag('h'.$options['level'], $title, $options['htmlOptions']), $options['wrapperOptions']);
    }


    public static function buttonGroup($items, $htmlOptions = [])
    {
        if (empty($items)) { return; }
        $o = [];
        static::addCssClass($htmlOptions, 'btn-group');
        $o[] = static::beginTag('div', $htmlOptions);
        foreach ($items as $item) {
            if (!isset($item['label'])) { continue; }
            if (!isset($item['htmlOptions'])) {
                $item['htmlOptions'] = [];
            }
            if (!isset($item['state'])) {
                $item['state'] = 'default';
            }
            static::addCssClass($item['htmlOptions'], 'btn btn-'.$item['state']);
            $o[] = static::a($item['label'], $item['url'], $item['htmlOptions']);
        }
        $o[] = static::endTag('div');
        return implode($o);
    }
}
