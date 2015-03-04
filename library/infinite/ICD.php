<?php
/**
 * ICD [@doctodo write class description for ICD].
 *
 * library/ICD.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 */
class ICD extends \yii\helpers\VarDumper
{
    /**
     * @var __var__var_type__ __var__var_description__
     */
    protected $_var;
    /**
     * @var __var__exclude_type__ __var__exclude_description__
     */
    protected $_exclude = [];
    /**
     * @var __var__backtrace_type__ __var__backtrace_description__
     */
    protected $_backtrace;
    /**
     * @var __var__format_type__ __var__format_description__
     */
    protected $_format = 'auto';
    /**
     * @var __var__depth_type__ __var__depth_description__
     */
    protected $_depth = 10;
    /**
     * @var __var__skipSteps_type__ __var__skipSteps_description__
     */
    protected $_skipSteps = 2;
    /**
     * @var __var__showSteps_type__ __var__showSteps_description__
     */
    protected $_showSteps = 8;

    /**
     * @var __var__output_type__ __var__output_description__
     */
    protected $_output = false;
    /**
     * @var __var__die_type__ __var__die_description__
     */
    protected $_die = false;

    /**
     * Constructor.
     *
     * @param __param_var_type__ $var      __param_var_description__
     * @param array              $settings __param_settings_description__ [optional]
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
     * __method_d_description__.
     *
     * @param __param_var_type__ $var      __param_var_description__
     * @param array              $settings __param_settings_description__ [optional]
     *
     * @return __return_d_type__ __return_d_description__
     */
    public static function d($var, $settings = [])
    {
        return new static($var, $settings);
    }

    /**
     * __method_btnice_description__.
     *
     * @param __param_backtrace_type__ $backtrace __param_backtrace_description__ [optional]
     * @param array                    $settings  __param_settings_description__ [optional]
     *
     * @return __return_btnice_type__ __return_btnice_description__
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
     * __method_btdiff_description__.
     *
     * @param __param_a_type__ $a      __param_a_description__
     * @param __param_b_type__ $b      __param_b_description__
     * @param boolean          $return __param_return_description__ [optional]
     *
     * @return __return_btdiff_type__ __return_btdiff_description__
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
     * __method_exclude_description__.
     *
     * @param __param_exclude_type__ $exclude __param_exclude_description__
     *
     * @return __return_exclude_type__ __return_exclude_description__
     */
    public function exclude($exclude)
    {
        $this->_exclude = $exclude;

        return $this;
    }

    /**
     * __method_html_description__.
     *
     * @return __return_html_type__ __return_html_description__
     */
    public function html()
    {
        $this->_format = 'html';

        return $this;
    }

    /**
     * __method_fatal_description__.
     *
     * @return __return_fatal_type__ __return_fatal_description__
     */
    public function fatal()
    {
        $this->_die = true;

        return $this;
    }

    /**
     * __method_safe_description__.
     *
     * @return __return_safe_type__ __return_safe_description__
     */
    public function safe()
    {
        $this->_die = false;

        return $this;
    }

    /**
     * __method_plaintext_description__.
     *
     * @return __return_plaintext_type__ __return_plaintext_description__
     */
    public function plaintext()
    {
        $this->_format = 'plaintext';

        return $this;
    }

    /**
     * Get format.
     *
     * @return __return_getFormat_type__ __return_getFormat_description__
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
     * __method_outputHtml_description__.
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
     * __method_outputPlaintext_description__.
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
     * __method_output_description__.
     *
     * @return __return_output_type__ __return_output_description__
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
     * __method___destruct_description__.
     */
    public function __destruct()
    {
        $this->output();
        if ($this->_die) {
            exit;
        }
    }
}
