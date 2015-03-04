<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\setup;

/**
 * Task [@doctodo write class description for Task].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class Task extends \infinite\base\Object
{
    /**
     * @var __var__setup_type__ __var__setup_description__
     */
    protected $_setup;
    /**
     * @var __var_errors_type__ __var_errors_description__
     */
    public $errors = [];
    /**
     * @var __var_fieldErrors_type__ __var_fieldErrors_description__
     */
    public $fieldErrors = [];
    /**
     * @var __var_input_type__ __var_input_description__
     */
    public $input = [];
    /**
     * @var __var_skipComplete_type__ __var_skipComplete_description__
     */
    public $skipComplete = false;

    /**
     * @inheritdoc
     */
    public function __construct($setup)
    {
        $this->_setup = $setup;
    }

    /**
     * Get setup.
     *
     * @return __return_getSetup_type__ __return_getSetup_description__
     */
    public function getSetup()
    {
        return $this->_setup;
    }

    /**
     * Get id.
     *
     * @return __return_getId_type__ __return_getId_description__
     */
    public function getId()
    {
        return self::baseClassName();
    }

    /**
     * __method_skip_description__.
     *
     * @return __return_skip_type__ __return_skip_description__
     */
    public function skip()
    {
        return true;
    }

    /**
     * __method_loadInput_description__.
     *
     * @param __param_input_type__ $input __param_input_description__
     *
     * @return __return_loadInput_type__ __return_loadInput_description__
     */
    public function loadInput($input)
    {
        $error = false;
        $this->input = [];
        foreach ($this->fields as $fieldset => $fields) {
            $this->input[$fieldset] = [];
            foreach ($fields['fields'] as $key => $settings) {
                $fieldId = 'field_' . $this->id . '_' . $fieldset . '_' . $key . '';

                $this->input[$fieldset][$key] = null;

                if (!empty($input[$fieldset][$key])) {
                    $this->input[$fieldset][$key] = $input[$fieldset][$key];
                } elseif (!empty($settings['required'])) {
                    $this->fieldErrors[$fieldId] = $settings['label'] . ' is a required field';
                    $error = true;
                }
            }
        }

        return !$error;
    }

    /**
     * Get title.
     */
    abstract public function getTitle();
    /**
     * __method_test_description__.
     */
    abstract public function test();
    /**
     * __method_run_description__.
     */
    abstract public function run();
    /**
     * Get fields.
     *
     * @return __return_getFields_type__ __return_getFields_description__
     */
    public function getFields()
    {
        return false;
    }
    /**
     * Get verification.
     *
     * @return __return_getVerification_type__ __return_getVerification_description__
     */
    public function getVerification()
    {
        return false;
    }

    /**
     * __method_templatize_description__.
     *
     * @param unknown $template
     * @param array   $vars     __param_vars_description__ [optional]
     *
     * @return unknown
     */
    public function templatize($template, $vars = [])
    {
        global $setup;
        $tmp = [];
        foreach ($setup->vars as $name => $value) {
            if (!is_string($value) and !is_numeric($value) and is_callable($value)) {
                $value = $value();
            }
            if (!is_array($value)) {
                $tmp["/\%\%" . $name . "\%\%/i"] = preg_replace('#(\\$|\\\\)#', '\\\\$1', $value);
            }
        }
        $tmp["/\%\%.*\%\%/i"] = '';
        $template = preg_replace(
            array_keys($tmp),
            array_values($tmp),
            $template
        );

        return $template;
    }

    /**
     * __method_parseText_description__.
     *
     * @param __param_text_type__ $text      __param_text_description__
     * @param array               $variables __param_variables_description__ [optional]
     *
     * @return __return_parseText_type__ __return_parseText_description__
     */
    public static function parseText($text, $variables = [])
    {
        if (is_object($text)) {
            return $text;
        }
        preg_match_all("/\%\%([^\%]+)\%\%/i", $text, $extracted);
        $replace = [];
        if (!empty($extracted)) {
            foreach ($extracted[0] as $k => $v) {
                $key = '/' . $v . '/';
                $parse = $extracted[1][$k];
                $replace[$key] = null;
                $instructions = explode('.', $parse);

                $placementItem = $variables;
                while (!empty($placementItem) and is_array($placementItem) and !empty($instructions)) {
                    $nextInstruction = array_shift($instructions);
                    if (isset($placementItem[$nextInstruction])) {
                        $placementItem = $placementItem[$nextInstruction];
                    } else {
                        $placementItem = null;
                    }
                }
                $replace[$key] = (string) $placementItem;
            }
        } else {
            return $text;
        }

        return trim(preg_replace(array_keys($replace), array_values($replace), $text));
    }
}
