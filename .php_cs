<?php
if (!isset($path)) {
	$path = __DIR__;
}

$fixersPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'yii2-infinite-core' . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'infinite' . DIRECTORY_SEPARATOR . 'cs';
include $fixersPath . DIRECTORY_SEPARATOR . 'ShortArrayFixer.php';

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->notName('LICENSE')
    ->notName('README.md')
    ->notName('composer.*')
    ->notName('phpunit.xml*')
    ->notName('*.phar')
    ->exclude('vendor')
    ->in($path)
;

$config = Symfony\CS\Config\Config::create();
$config->addCustomFixer(new infinite\cs\ShortArrayFixer());

return $config
    ->fixers(array('short_array', 'indentation', 'linefeed', 'trailing_spaces', 'unused_use', 'phpdoc_params', 'return', 'php_closing_tag', 'braces', 'extra_empty_lines', 'function_declaration', 'controls_spaces', 'eof_ending', 'elseif'))
    ->finder($finder)
;

?>