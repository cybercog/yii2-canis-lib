<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\widgets;

use infinite\helpers\Html;

/**
 * ActiveField [@doctodo write class description for ActiveField]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class ActiveField extends \yii\widgets\ActiveField
{
    public $inputGroupHtmlOptions = ['class' => 'input-group'];
    public $inputGroupPrefix = false;
    public $inputGroupPostfix = false;

    /**
    * @inheritdoc
    **/
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
