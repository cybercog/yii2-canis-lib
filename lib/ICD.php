<?php
/**
 * ICD [[@doctodo class_description:ICD]]
 * library/ICD.php.
 *
 * @author Jacob Morrison <jacob@canis.io>
 */
class ICD extends \yii\helpers\VarDumper
{
    /**
     * @var [[@doctodo var_type:_var]] [[@doctodo var_description:_var]]
     */
    protected $_var;
    /**
     * @var [[@doctodo var_type:_exclude]] [[@doctodo var_description:_exclude]]
     */
    protected $_exclude = [];
    /**
     * @var [[@doctodo var_type:_backtrace]] [[@doctodo var_description:_backtrace]]
     */
    protected $_backtrace;
    /**
     * @var [[@doctodo var_type:_format]] [[@doctodo var_description:_format]]
     */
    protected $_format = 'auto';
    /**
     * @var [[@doctodo var_type:_depth]] [[@doctodo var_description:_depth]]
     */
    protected $_depth = 10;
    /**
     * @var [[@doctodo var_type:_skipSteps]] [[@doctodo var_description:_skipSteps]]
     */
    protected $_skipSteps = 2;
    /**
     * @var [[@doctodo var_type:_showSteps]] [[@doctodo var_description:_showSteps]]
     */
    protected $_showSteps = 8;

    /**
     * @var [[@doctodo var_type:_output]] [[@doctodo var_description:_output]]
     */
    protected $_output = false;
    /**
     * @var [[@doctodo var_type:_die]] [[@doctodo var_description:_die]]
     */
    protected $_die = false;

    /**
     * Constructor.
     *
     * @param [[@doctodo param_type:var]] $var      [[@doctodo param_description:var]]
     * @param array                       $settings [[@doctodo param_description:settings]] [optional]
     */
    public function __construct($var, $settings = [])
    {
        $backtrace = debug_backtrace();
        $this->_var = $var;

        foreach ($settings as $k => $v) {
            $kk = '_' . $k;
            $this->$kk = $v;
        }

        $this->_backtrace = array_slice($backtrace, $this->_skipSteps);
    }
    /**
     * [[@doctodo method_description:d]].
     *
     * @param [[@doctodo param_type:var]] $var      [[@doctodo param_description:var]]
     * @param array                       $settings [[@doctodo param_description:settings]] [optional]
     *
     * @return [[@doctodo return_type:d]] [[@doctodo return_description:d]]
     */
    public static function d($var, $settings = [])
    {
        return new static($var, $settings);
    }

    /**
     * [[@doctodo method_description:btnice]].
     *
     * @param [[@doctodo param_type:backtrace]] $backtrace [[@doctodo param_description:backtrace]] [optional]
     * @param array                             $settings  [[@doctodo param_description:settings]] [optional]
     *
     * @return [[@doctodo return_type:btnice]] [[@doctodo return_description:btnice]]
     */
    public static function btnice($backtrace = null, $settings = [])
    {
        if (is_null($backtrace)) {
            $backtrace = debug_backtrace();
        }
        $nice = [];
        foreach ($backtrace as $bt) {
            if (!isset($bt['file'])) {
                $bt['file'] = '?';
            }

            if (!isset($bt['line'])) {
                $bt['line'] = '#';
            }
            $nice[] = $bt['file'] . ':' . $bt['function'] . ':' . $bt['line'];
        }

        return $nice;
    }

    /**
     * [[@doctodo method_description:btdiff]].
     *
     * @param [[@doctodo param_type:a]] $a      [[@doctodo param_description:a]]
     * @param [[@doctodo param_type:b]] $b      [[@doctodo param_description:b]]
     * @param boolean                   $return [[@doctodo param_description:return]] [optional]
     *
     * @return [[@doctodo return_type:btdiff]] [[@doctodo return_description:btdiff]]
     */
    public static function btdiff($a, $b, $return = true)
    {
        $diff = [];
        $n = 0;
        $a = static::btnice($a);
        $b = static::btnice($b);
        if (count($a) > count($b)) {
            $baseDesc = 'a';
            $compDesc = 'b';
            $base = $a;
            $comp = $b;
        } else {
            $baseDesc = 'b';
            $compDesc = 'a';
            $base = $b;
            $comp = $a;
        }
        while (count($base) > 0) {
            $cb = array_pop($base);
            if (count($comp) > 0) {
                $cc = array_pop($comp);
            } else {
                $cc = null;
            }
            if ($cb !== $cc) {
                $diff[$n] = [
                    $baseDesc = $cb,
                    $compDesc = $cc,
                ];
            }
            $n++;
        }
        if ($return) {
            return $diff;
        } else {
            static::d($diff);
        }
    }

    /**
     * [[@doctodo method_description:exclude]].
     *
     * @param [[@doctodo param_type:exclude]] $exclude [[@doctodo param_description:exclude]]
     *
     * @return [[@doctodo return_type:exclude]] [[@doctodo return_description:exclude]]
     */
    public function exclude($exclude)
    {
        $this->_exclude = $exclude;

        return $this;
    }

    /**
     * [[@doctodo method_description:html]].
     *
     * @return [[@doctodo return_type:html]] [[@doctodo return_description:html]]
     */
    public function html()
    {
        $this->_format = 'html';

        return $this;
    }

    /**
     * [[@doctodo method_description:fatal]].
     *
     * @return [[@doctodo return_type:fatal]] [[@doctodo return_description:fatal]]
     */
    public function fatal()
    {
        $this->_die = true;

        return $this;
    }

    /**
     * [[@doctodo method_description:safe]].
     *
     * @return [[@doctodo return_type:safe]] [[@doctodo return_description:safe]]
     */
    public function safe()
    {
        $this->_die = false;

        return $this;
    }

    /**
     * [[@doctodo method_description:plaintext]].
     *
     * @return [[@doctodo return_type:plaintext]] [[@doctodo return_description:plaintext]]
     */
    public function plaintext()
    {
        $this->_format = 'plaintext';

        return $this;
    }

    /**
     * Get format.
     *
     * @return [[@doctodo return_type:getFormat]] [[@doctodo return_description:getFormat]]
     */
    public function getFormat()
    {
        if ($this->_format === 'auto') {
            if (PHP_SAPI !== 'cli') {
                return 'html';
            }

            return 'plaintext';
        }

        return $this->_format;
    }

    /**
     * [[@doctodo method_description:outputHtml]].
     */
    public function outputHtml()
    {
        echo '<div style="display: block; margin: 5px; padding: 5px; background-color: #fff; border: 1px solid black; z-index: 999999999; position:relative;">';
        echo '<h3 style="font-size: 14px; margin: 3px">' . $this->_backtrace[0]['file'] . ':' . $this->_backtrace[1]['function'] . ':' . $this->_backtrace[0]['line'] . '</h3>';
        $backtrace = array_slice($this->_backtrace, 1, $this->_showSteps);
        foreach ($backtrace as $bt) {
            if (!isset($bt['file'])) {
                continue;
            }
            echo '<div style="font-size: 12px; margin: 1px">' . $bt['file'] . ':' . $bt['function'] . ':' . $bt['line'] . '</div>';
        }
        echo '<hr />';
        echo self::dump($this->_var, $this->_depth, true);
        echo '</div>';
    }

    /**
     * [[@doctodo method_description:outputPlaintext]].
     */
    public function outputPlaintext()
    {
        echo str_repeat('=', 100) . "\n";
        echo $this->_backtrace[0]['file'] . ':' . $this->_backtrace[1]['function'] . ':' . $this->_backtrace[0]['line'] . "\n";
        echo str_repeat('-', 100) . "\n";
        echo self::dump($this->_var, $this->_depth, false);
        echo str_repeat('=', 100) . "\n";
    }

    /**
     * [[@doctodo method_description:output]].
     *
     * @return [[@doctodo return_type:output]] [[@doctodo return_description:output]]
     */
    public function output()
    {
        if ($this->_output) {
            return;
        }
        if ($this->getFormat() === 'html') {
            $this->outputHtml();
        } else {
            $this->outputPlaintext();
        }
        $this->_output = true;
    }

    /**
     * [[@doctodo method_description:__destruct]].
     */
    public function __destruct()
    {
        $this->output();
        if ($this->_die) {
            exit;
        }
    }
}
