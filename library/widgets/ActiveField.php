<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\widgets;

use teal\helpers\Html;

/**
 * ActiveField [[@doctodo class_description:teal\widgets\ActiveField]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ActiveField extends \yii\widgets\ActiveField
{
    /**
     * @var [[@doctodo var_type:inputGroupHtmlOptions]] [[@doctodo var_description:inputGroupHtmlOptions]]
     */
    public $inputGroupHtmlOptions = ['class' => 'input-group'];
    /**
     * @var [[@doctodo var_type:inputGroupPrefix]] [[@doctodo var_description:inputGroupPrefix]]
     */
    public $inputGroupPrefix = false;
    /**
     * @var [[@doctodo var_type:inputGroupPostfix]] [[@doctodo var_description:inputGroupPostfix]]
     */
    public $inputGroupPostfix = false;

    /**
     * @inheritdoc
     */
    public function render($content = null)
    {
        if ($content === null) {
            if (!isset($this->parts['{input}'])) {
                $this->parts['{input}'] = Html::activeTextInput($this->model, $this->attribute, $this->inputOptions);
            }
        }
        if ($this->inputGroupPrefix || $this->inputGroupPostfix) {
            $input = $this->parts['{input}'];
            $this->parts['{input}'] = Html::beginTag('div', $this->inputGroupHtmlOptions);
            if ($this->inputGroupPrefix) {
                $this->parts['{input}'] .= Html::tag('span', $this->inputGroupPrefix, ['class' => 'input-group-addon']);
            }
            $this->parts['{input}'] .= $input;

            if ($this->inputGroupPostfix) {
                $this->parts['{input}'] .= Html::tag('span', $this->inputGroupPostfix, ['class' => 'input-group-addon']);
            }
            $this->parts['{input}'] .= Html::endTag('div');
        }

        return parent::render($content);
    }

    /**
     * @inheritdoc
     */
    public function checkboxList($items, $options = [])
    {
        $options = array_merge($this->inputOptions, $options);

        return parent::checkboxList($items, $options);
    }

    /**
     * @inheritdoc
     */
    public function radioList($items, $options = [])
    {
        $options = array_merge($this->inputOptions, $options);

        return parent::radioList($items, $options);
    }
}
