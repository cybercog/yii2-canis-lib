<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\widgets;

use infinite\helpers\Html;

/**
 * LinkPager [@doctodo write class description for LinkPager]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class LinkPager extends \yii\widgets\LinkPager
{
    /**
     * @var __var_pageStateKey_type__ __var_pageStateKey_description__
     */
    public $pageStateKey = 'page';
    /**
     * @inheritdoc
     */
    protected function renderPageButton($label, $page, $class, $disabled, $active)
    {
        $options = ['class' => $class === '' ? null : $class];
        $linkOptions = $this->buildButtonAttributes($page);
        if ($active) {
            Html::addCssClass($options, $this->activePageCssClass);
        }
        if ($disabled) {
            Html::addCssClass($options, $this->disabledPageCssClass);

            return Html::tag('li', Html::tag('span', $label), $options);
        }

        return Html::tag('li', Html::a($label, $this->pagination->createUrl($page), $linkOptions), $options);
    }

    /**
     * __method_buildButtonAttributes_description__
     * @param __param_page_type__ $page __param_page_description__
     * @param array $options __param_options_description__ [optional]
     * @return __return_buildButtonAttributes_type__ __return_buildButtonAttributes_description__
     */
    public function buildButtonAttributes($page, $options = [])
    {
        $stateChange = [
            $this->pageStateKey => $page
        ];
        $options['data-handler'] = 'background';
        $options['data-state-change'] = json_encode($stateChange);

        return $options;
    }
}
