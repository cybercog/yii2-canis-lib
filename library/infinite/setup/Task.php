<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\setup;

/**
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class Task extends \infinite\base\Object
{
    /**
     */
    protected $_setup;
    /**
     */
    public $errors = [];
    /**
     */
    public $fieldErrors = [];
    /**
     */
    public $input = [];
    /**
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
     */
    public function getSetup()
    {
        return $this->_setup;
    }

    /**
     * Get id.
     */
    public function getId()
    {
        return self::baseClassName();
    }

    /**
     *
     */
    public function skip()
    {
        return true;
    }

    /**
     *
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
     */
    abstract public function test();
    /**
     */
    abstract public function run();
    /**
     * Get fields.
     */
    public function getFields()
    {
        return false;
    }
    /**
     * Get verification.
     */
    public function getVerification()
    {
        return false;
    }

    /**
     * @param unknown $template
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
     *
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
