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

	protected $_output = false;
	protected $_die = false;

	public function __construct($var) {
		$backtrace = debug_backtrace();
		$this->_var = $var;
		$this->_backtrace = $backtrace[0];
	}
    public static function d($var) {

    	return new static($var);
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
    	echo '<h2 style="font-size: 18px;">'.$this->_backtrace['file'] .':'. $this->_backtrace['line'].'</h2>';
    	echo self::dump($this->_var, $this->_depth, true);
    	echo '</div>';
    }

    public function outputPlaintext() {
    	echo str_repeat('=', 100) ."\n";
    	echo $this->_backtrace['file'] .':'. $this->_backtrace['line'] ."\n";
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
    	if ($this->die) {
    		exit;
    	}
    }
}