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
    ->fixers(array('operators_spaces', 'indentation', 'linefeed', 'trailing_spaces', 'unused_use', 'ordered_use', 'return', 'php_closing_tag', 'braces', 'extra_empty_lines', 'function_declaration', 'controls_spaces', 'eof_ending', 'elseif', 'short_array_syntax', 'phpdoc_indent', 'phpdoc_params', 'function_call_space', 'lowercase_constants', 'method_argument_space', 'single_line_after_imports', 'remove_leading_slash_use', 'spaces_cast', 'phpdoc_order', 'concat_with_spaces'))
    ->finder($finder);

?>