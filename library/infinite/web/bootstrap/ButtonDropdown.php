<?php
namespace infinite\web\bootstrap;

use infinite\helpers\Html;
use yii\bootstrap\Button;

class ButtonDropdown extends \yii\bootstrap\ButtonDropdown
{
    /**
     * @var array the HTML attributes for the icon in the button label.
     *
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $iconOptions = [];

    public $containerOptions = [];

    public function run()
    {
        Html::addCssClass($this->containerOptions, 'btn-group');
        echo Html::beginTag('div', $this->containerOptions);
        echo "\n".$this->renderButton();
        echo "\n".$this->renderDropdown();
        echo "\n".Html::endTag('div');
        $this->registerPlugin('button');
    }

    /**
     * Generates the button dropdown.
     *
     * @return string the rendering result.
     */
    protected function renderButton()
    {
        Html::addCssClass($this->options, 'btn');
        $label = $this->label;
        if ($this->encodeLabel) {
            $label = Html::encode($label);
        }
        if (!isset($this->iconOptions['class'])) {
            $this->iconOptions['class'] = 'caret';
        }
        if ($this->split) {
            $options = $this->options;
            $this->options['data-toggle'] = 'dropdown';
            Html::addCssClass($this->options, 'dropdown-toggle');
            $splitButton = Button::widget([
                'label' => Html::tag('span', '', $this->iconOptions),
                'encodeLabel' => false,
                'options' => $this->options,
                'view' => $this->getView(),
            ]);
        } else {
            $label .= ' '.Html::tag('span', '', $this->iconOptions);
            $options = $this->options;
            if (!isset($options['href'])) {
                $options['href'] = '#';
            }
            Html::addCssClass($options, 'dropdown-toggle');
            $options['data-toggle'] = 'dropdown';
            $splitButton = '';
        }

        return Button::widget([
            'tagName' => $this->tagName,
            'label' => $label,
            'options' => $options,
            'encodeLabel' => false,
            'view' => $this->getView(),
        ])."\n".$splitButton;
    }
}
