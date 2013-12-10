<?php
/**
 * library/ICD.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */

class ICD extends \yii\helpers\VarDumper
{
	protected $_var;
	protected $_exclude = [];
	protected $_backtrace;
	protected $_format = 'auto';
    protected $_depth = 10;
    protected $_skipSteps = 2;
    protected $_showSteps = 4;

	protected $_output = false;
	protected $_die = false;

	public function __construct($var, $settings = []) {
		$backtrace = debug_backtrace();
		$this->_var = $var;

        foreach ($settings as $k => $v) {
            $kk = '_'. $k;
            $this->$kk = $v;
        }

		$this->_backtrace = array_slice($backtrace, $this->_skipSteps);
	}
    public static function d($var, $settings = []) {
    	return new static($var, $settings);
    }

    public function exclude($exclude) {
    	$this->_exclude = $exclude;
    	return $this;
    }

    public function html() {
    	$this->_format = 'html';
    	return $this;
    }

    public function fatal() {
    	$this->_die = true;
    	return $this;
    }

    public function safe() {
    	$this->_die = false;
    	return $this;
    }

    public function plaintext() {
    	$this->_format = 'plaintext';
    	return $this;
    }

    public function getFormat() {
    	if ($this->_format === 'auto') {
    		if (isset($_REQUEST)) {
    			return 'html';
    		}
    		return 'plaintext';
    	}
    	return $this->_format;
    }

    public function outputHtml() {
    	echo '<div style="display: block; margin: 5px; padding: 5px; background-color: #fff; border: 1px solid black; z-index: 999999999; position:relative;">';
    	echo '<h3 style="font-size: 14px; margin: 3px">'.$this->_backtrace[0]['file'] .':'. $this->_backtrace[0]['function'].':'. $this->_backtrace[0]['line'].'</h3>';
        $backtrace = array_slice($this->_backtrace, 1, $this->_showSteps);
        foreach ($backtrace as $bt) {
            if (!isset($bt['file'])) { continue; }
            echo '<div style="font-size: 12px; margin: 1px">'.$bt['file'] .':'. $bt['function'].':'. $bt['line'].'</div>';
        }
        echo '<hr />';
    	echo self::dump($this->_var, $this->_depth, true);
    	echo '</div>';
    }

    public function outputPlaintext() {
    	echo str_repeat('=', 100) ."\n";
    	echo $this->_backtrace[0]['file'] .':'. $this->_backtrace[0]['line'] ."\n";
    	echo str_repeat('-', 100) ."\n";
    	echo self::dump($this->_var, $this->_depth, false);
    	echo str_repeat('=', 100) ."\n";
    }

    public function output() {
    	if ($this->_output) { return; }
    	if ($this->getFormat() === 'html') {
    		$this->outputHtml();
    	} else {
    		$this->outputPlaintext();
    	}
    	$this->_output = true;
    }

    public function __destruct() {
    	$this->output();
    	if ($this->_die) {
    		exit;
    	}
    }
}