<?php

namespace Inject\Router;

use \Inject\Router\Parser\RegExp;

class CompilingDynamic
{
	protected $route_map = array();
	
	protected $callbacks = array();
	
	public function __construct($methods = array('GET', 'POST', 'PUT', 'DELETE'))
	{
		foreach($methods as $m)
		{
			// Make sure it is uppercase
			$m = strtoupper($m);
			
			$this->route_map[$m] = new Multimap();
		}
	}
	
	public function connect($methods, $pattern, $function)
	{
		try
		{
			$regexp = new RegExp($pattern);
			
			$path = new RegExpPath($regexp);
		}
		catch(\Exception $e)
		{
			throw new \Exception("Not parseable Regular Expression");
		}
		
		$path_parts = $path->getPathChunks('$url');
		
		$this->callbacks[] = $function;
		
		$i = count($this->callbacks) - 1;
		
		foreach(array_map('strtoupper', (Array)$methods) as $method)
		{
			$this->route_map[$method]->set($path_parts, $i);
		}
	}
	
	public function exportData()
	{
		$arr = array();
		
		foreach($this->route_map as $method => $graph)
		{
			var_dump($graph);
			
			$arr[] = "'".$method.'\' => function($url)
	{
		$matches = array();
		
'.$graph->toCode(2).'
		
		return false;
	}';
		}
		
		return "array(".implode("\n\t", $arr)."\n}";
	}
	
	public function build($clear_map = true)
	{
		foreach($this->route_map as $method => $graph)
		{
			$this->graph[$method] = eval('return function($url){$matches = array();'.$graph->toCode().'};');
		}
		
		$clear_map && $this->route_map = array();
	}
	
	public function __invoke($env)
	{
		if( ! empty($this->graph[$env['REQUEST_METHOD']]))
		{
			if($data = $this->graph[$env['REQUEST_METHOD']]($env['PATH_INFO']))
			{
				$env['router.captures'] = $data[1];
				
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

/**
 * Creates an array of conditions from a RegExp object.
 */
class RegExpPath
{
	protected $pattern;
	
	protected $path = array();
	
	public function __construct(RegExp $exp)
	{
		$this->pattern = $exp;
	}
	
	public function getPathChunks($source_var)
	{
		return $this->buildTree($this->pattern->getPatternObject(), $source_var, 0);
	}
	
	public function buildTree($part, $source_var, $str_index)
	{
		if(is_string($part))
		{
			return 'isset('.$source_var.'['.$str_index.']) && '.$source_var.'['.$str_index.'] == "'.$part.'"';
		}
		else if(is_object($part))
		{
			if( ! $part->isLiteral())
			{
				return 'preg_match('.var_export('/'.$this->pattern->getPatternObject()->toPattern(function($str)
				{
					return preg_quote($str, '/');
				}).'/', true).' '.$source_var.', $matches)';
			}
			else
			{
				$arr = array();
				
				foreach($part->getParts() as $p)
				{
					if(is_object($p) && ! $p->isLiteral())
					{
						$arr[] = 'preg_match('.var_export('/'.$this->pattern->getPatternObject()->toPattern(function($str)
						{
							return preg_quote($str, '/');
						}).'/', true).', '.$source_var.', $matches)';
						
						break;
					}
					
					$arr[] = $this->buildTree($p, $source_var, $str_index);
					
					$str_index++;
				}
				
				return $arr;
			}
		}
	}
}

class Multimap
{
	protected $keys = array();
	protected $values = array();
	
	public function __construct($keys = array(), $values = array())
	{
		$this->keys = $keys;
		$this->values = $values;
	}
	
	public function set(array $keys, $value)
	{
		if(empty($this->keys) && empty($this->values))
		{
			$this->keys = $keys;
			$this->values[] = $value;
			
			return;
		}
		
		$min = min(count($this->keys), count($keys));
		
		for($i = 0; $i < $min; $i++)
		{
			if($this->keys[$i] != $keys[$i])
			{
				break;
			}
		}
		
		$rest_this  = array_slice($this->keys, $i);
		$rest_new   = array_slice($keys, $i);
		$this->keys = array_slice($this->keys, 0, $i);
		
		if( ! empty($rest_this))
		{
			$tmp = $this->values;
			
			$this->values = array();
			
			foreach($tmp as $k => $v)
			{
				empty($tmp) OR $this->setRelativeKey(is_numeric($k) ? $rest_this : array_merge(array($k), $rest_this), $v);
			}
		}
		
		$this->setRelativeKey($rest_new, $value);
	}
	
	public function setRelativeKey($keys, $value)
	{
		$key = array_shift($keys);
		
		if(empty($key))
		{
			$this->values[] = $value;
		}
		else
		{
			empty($this->values[$key]) && $this->values[$key] = new Multimap($keys);
			
			$this->values[$key]->set($keys, $value);
		}
	}
	
	public function toCode($indent = 0)
	{
		$ind = str_repeat("\t", $indent);
		
		$str = array();
		
		// We have conditions common to all the values
		if( ! empty($this->keys))
		{
			// Increase the indentation one step to compensate
			$oind = $ind;
			$ind .= "\t";
			$indent++;
			
			$str[] = $oind.'if('.implode(' && ', $this->keys).")\n$oind{";
		}
		
		// Create if-clauses for all the conditions first
		foreach($this->values as $key => $val)
		{
			if(is_numeric($key))
			{
				continue;
			}
			
			$str[] = $ind.'if('.$key.")\n$ind{";
			
			$str[] = $val instanceof Multimap ? $val->toCode($indent + 1) : $ind."\t".var_export($val, true);
			
			$str[] = "$ind}";
		}
		
		// Extract values, ie. values with numeric keys
		$data = array_intersect_key($this->values, array_flip(array_filter(array_keys($this->values), 'is_numeric')));
		
		// If we have data, add a return which lists the values and also the $matches var
		empty($data) OR $str[] = $ind.'return array(array('.implode(', ', $data).'), $matches);';
		
		// Wrapping if
		if( ! empty($this->keys))
		{
			$str[] = "$oind}";
		}
		
		return implode("\n", $str);
	}
}

