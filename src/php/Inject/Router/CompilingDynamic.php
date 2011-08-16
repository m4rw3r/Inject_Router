<?php

namespace Inject\Router;

use \Inject\Router\CompilingDynamic\RouteGraph;
use \Inject\Router\CompilingDynamic\RouteGraphKey;
use \Inject\Router\CompilingDynamic\RegExpEntry;
use \Inject\Router\Parser\RegExp;

class CompilingDynamic
{
	protected $route_map = array();
	
	protected $callbacks = array();
	
	protected $graph = array();
	
	public function __construct($methods = array('GET', 'POST', 'PUT', 'DELETE'))
	{
		foreach($methods as $m)
		{
			// Make sure it is uppercase
			$m = strtoupper($m);
			
			$this->route_map[$m] = new RouteGraph();
		}
	}
	
	public function connect($methods, $pattern, $function)
	{
		try
		{
			$regexp = new RegExp($pattern);
			
			$path = new RegExpEntry($regexp);
		}
		catch(\Exception $e)
		{
			throw new \Exception("Not parseable Regular Expression");
		}
		
		$this->callbacks[] = $function;
		
		$path->setId(count($this->callbacks) - 1);
		
		$key = $path->getKeyParts();
		
		foreach(array_map('strtoupper', (Array)$methods) as $method)
		{
			$this->route_map[$method]->add($key, $path);
		}
	}
	
	public function exportData()
	{
		$arr = array();
		
		foreach($this->route_map as $method => $graph)
		{
			$arr[] = "'".$method.'\' => function($url)
	{
		$matches = array();
		
'.$graph->toCode(2).'
		
		return false;
	}';
		}
		
		return "array(".implode(",\n\t", $arr)."\n)";
	}
	
	public function build($clear_map = true)
	{
		$this->graph = eval('return '.$this->exportData().';');
		
		$clear_map && $this->route_map = array();
	}
	
	public function __invoke($env)
	{
		if( ! empty($this->graph[$env['REQUEST_METHOD']]))
		{
			if($data = $this->graph[$env['REQUEST_METHOD']]($env['PATH_INFO']))
			{
				$env['route.captures'] = $data[1];
				
				foreach($data[0] as $index)
				{
					$call = $this->callbacks[$index];
					
					$ret = $call($env);
					
					if( ! isset($ret[1]['X-Cascade']) OR $ret[1]['X-Cascade'] != 'pass')
					{
						return $ret;
					}
				}
			}
		}
		
		return array(404, array(), '');
	}
}

