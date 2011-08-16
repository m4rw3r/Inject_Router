<?php

// xdebug_start_trace(__DIR__.'/trace/trace_'.microtime(true));

$prjdir = realpath(__dir__.'/..');

$paths = array(
	'./src/php',
	$prjdir.'/Inject_ClassTools/src/php',
	get_include_path()
);

set_include_path(implode(PATH_SEPARATOR, $paths));
error_reporting(E_ALL | E_STRICT | E_DEPRECATED);
setlocale(LC_CTYPE, 'C');

require 'Inject/ClassTools/Autoloader/Generic.php';

$loader = new \Inject\ClassTools\Autoloader\Generic();
$loader->register();

use Inject\Router\CompilingDynamic;

$rtr = new CompilingDynamic();

$rtr->connect('GET', '+/$+', function()
	{
		return '+/$+';
	}
);

$rtr->connect('GET', '+/user$+', function()
	{
		return '+/user$';
	}
);

$rtr->connect('GET', '+/user(?:/(?<id>\d+))?$+', function($env)
	{
		var_dump($env);
		
		return '+/user'.(empty($env['route.captures']) ? '$' : '/'.$env['route.captures']['id']);
	}
);

$rtr->connect('GET', '+/user-profile/(\d+)$+', function()
	{
		return 'Profile for user, but we did not capture the id into a named capture.';
	}
);

// Dump the generated code
var_dump($rtr->exportData());

// Build the route graph (ie. eval() generated closure)
$rtr->build();

// Test routing!
var_dump($rtr(array('REQUEST_METHOD' => 'GET', 'PATH_INFO' => '/user/45')));

