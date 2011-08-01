<?php

// xdebug_start_trace(__DIR__.'/trace/trace_'.microtime(true));

$prjdir = realpath(__dir__.'/..');

$paths = array(
	'./src/php',
	$prjdir.'/Inject_Stack/src/php',
	$prjdir.'/Inject_ClassTools/src/php',
	get_include_path()
);

set_include_path(implode(PATH_SEPARATOR, $paths));
error_reporting(E_ALL | E_STRICT | E_DEPRECATED);
setlocale(LC_CTYPE, 'C');

require 'Inject/ClassTools/Autoloader/Generic.php';

$loader = new \Inject\ClassTools\Autoloader\Generic();
$loader->register();

use Inject\Router\Parser\RegExp;

$r = new RegExp('+/user(?:(?<id>(\d)\+))?.+ui');

var_dump($r);

var_dump($r->getPattern());

var_dump(preg_match($r->getPattern(), '/user'));

var_dump($r->getNamedCaptures());