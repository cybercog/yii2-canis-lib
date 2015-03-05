<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\helpers;

use infinite\web\View;
use Yii;
use yii\base\InvalidParamException;

/**
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
     *
     */
    public static function registerJsBlock($script, $position = View::POS_READY, $key = null)
    {
        return Yii::$app->controller->view->registerJs($script, $position, $key);
    }

    /**
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

        return static::tag($options['wrapperTag'], static::tag('h' . $options['level'], $title, $options['htmlOptions']), $options['wrapperOptions']);
    }

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
