<?php
/**
 * library/setup/Task.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\setup;

abstract class Task extends \infinite\base\Object
{
    protected $_setup;
    public $errors = array();
    public $fieldErrors = array();
    public $input = array();

    public function __construct($setup)
    {
        $this->_setup = $setup;
    }

    public function getSetup()
    {
        return $this->_setup;
    }

    public function getId()
    {
        return self::baseClassName();
    }

    public function loadInput($input)
    {
        $error = false;
        $this->input = array();
        foreach ($this->fields as $fieldset => $fields) {
            $this->input[$fieldset] = array();
            foreach ($fields['fields'] as $key => $settings) {
                $fieldId = 'field_'.$this->id. '_'.$fieldset .'_'. $key .'';

                $this->input[$fieldset][$key] = null;

                if (!empty($input[$fieldset][$key])) {
                    $this->input[$fieldset][$key] = $input[$fieldset][$key];
                } elseif(!empty($settings['required'])) {
                    $this->fieldErrors[$fieldId] = $settings['label'] .' is a required field';
                    $error = true;
                }

            }
        }
        return !$error;
    }

    abstract public function getTitle();
    abstract public function test();
    abstract public function run();
    public function getFields()
    {
        return false;
    }
    public function getVerification()
    {
        return false;
    }

    /**
     *
     *
     * @param unknown $template
     * @return unknown
     */
    public function templatize($template, $vars = array())
    {
        global $setup;
        $tmp = array();
        foreach ($setup->vars as $name => $value) {
            if (!is_string($value) and !is_numeric($value) and is_callable($value)) {
                $value = $value();
            }
            if (!is_array($value)) {
                $tmp["/\%\%". $name ."\%\%/i"] = preg_replace('#(\\$|\\\\)#', '\\\\$1', $value);
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

    public static function parseText($text, $variables = array())
    {
        if (is_object($text)) { return $text; }
        preg_match_all("/\%\%([^\%]+)\%\%/i", $text, $extracted);
        $replace = array();
        if (!empty($extracted)) {
            foreach ($extracted[0] as $k => $v) {
                $key = '/'.$v.'/';
                $parse = $extracted[1][$k];
                $replace[$key] = null;
                $instructions = explode('.', $parse);

                $placementItem = $variables;
                while (!empty($placementItem) AND is_array($placementItem) AND !empty($instructions)) {
                    $nextInstruction = array_shift($instructions);
                    if (isset($placementItem[$nextInstruction])) {
                        $placementItem = $placementItem[$nextInstruction];
                    } else {
                        $placementItem = null;
                    }
                }
                $replace[$key] = (string)$placementItem;
            }
        } else {
            return $text;
        }
        return trim(preg_replace(array_keys($replace), array_values($replace), $text));
    }
}
