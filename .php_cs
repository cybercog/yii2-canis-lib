<?php
$vendorPath = dirname(dirname(__DIR__));
include $vendorPath .DIRECTORY_SEPARATOR . 'autoload.php';

if (!isset($path)) {
	$path = __DIR__;
}
if (!isset($docBlockSettings)) {
	$docBlockSettings = [];
}
if (!isset($docBlockSettings['package'])) {
	$docBlockSettings['package'] = 'infinite-core';
}
if (!isset($docBlockSettings['author'])) {
	$docBlockSettings['author'] = 'Jacob Morrison <email@ofjacob.com>';
}
if (!isset($docBlockSettings['since'])) {
	$docBlockSettings['since'] = '1.0';
	if (is_file($path . DIRECTORY_SEPARATOR . 'VERSION')) {
		$docBlockSettings['since'] = file_get_contents($path . DIRECTORY_SEPARATOR . 'VERSION');
	}
}


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
// $config->addCustomFixer(new infinite\cs\ShortArrayFixer());
//$config->addCustomFixer(new infinite\cs\DocBlockGenerator());
//'doc_block_gen', 
return $config
    ->fixers(array('indentation', 'linefeed', 'trailing_spaces', 'unused_use', 'phpdoc_params', 'return', 'php_closing_tag', 'braces', 'extra_empty_lines', 'function_declaration', 'controls_spaces', 'eof_ending', 'elseif', 'short_array_syntax', 'phpdoc_indent', 'phpdoc_params'))
    ->finder($finder);

?>