<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\widgets;

use canis\helpers\Html;

/**
 * LinkPager [[@doctodo class_description:canis\widgets\LinkPager]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class LinkPager extends \yii\widgets\LinkPager
{
    /**
     * @var [[@doctodo var_type:pageStateKey]] [[@doctodo var_description:pageStateKey]]
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
     * [[@doctodo method_description:buildButtonAttributes]].
     *
     * @param [[@doctodo param_type:page]] $page    [[@doctodo param_description:page]]
     * @param array                        $options [[@doctodo param_description:options]] [optional]
     *
     * @return [[@doctodo return_type:buildButtonAttributes]] [[@doctodo return_description:buildButtonAttributes]]
     */
    public function buildButtonAttributes($page, $options = [])
    {
        $stateChange = [
            $this->pageStateKey => $page,
        ];
        $options['data-handler'] = 'background';
        $options['data-state-change'] = json_encode($stateChange);

        return $options;
    }
}
