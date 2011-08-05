<?php

namespace Inject\Router;

use \Inject\Router\Parser\RegExp;

class CompilingDynamic
{
	protected $route_map = array();
	
	protected $callbacks = array();
	
	public function __construct()
	{
		$this->route_map = array(
			'GET'     => new Multimap(),
			/*'POST'    => new Multimap(),
			'PUT'     => new Multimap(),
			'DELETE'  => new Multimap(),
			'HEAD'    => new Multimap(),
			'TRACE'   => new Multimap(),
			'OPTIONS' => new Multimap(),
			'CONNECT' => new Multimap()*/
		);
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
	
	public function build()
	{
		foreach($this->route_map as $method => $graph)
		{
			$this->graph[$method] = eval('return function($url){$matches = array();'.$graph->toCode().'};');
		}
		
		$this->route_map = array();
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

class Condition
{
	protected $expression;
	
	protected $nested = array();
	
	public function __construct($expression)
	{
		$this->expression = $expression;
	}
	
	public function add($part)
	{
		$this->nested[] = $part;
	}
	
	public function toCode($indent = 0)
	{
		$ind = str_repeat("\t", $indent);
		
		return $ind.'if('.$this->expression.')
'.$ind.'{
'.$ind.'	'.$this->buildNested($indent + 1).'
'.$ind.'}';
	}
	
	public function buildNested($indent)
	{
		$arr = array();
		$ind = str_repeat("\t", $indent);
		
		foreach($this->nested as $part)
		{
			if(is_string($part))
			{
				$arr[] = $part;
			}
			else
			{
				$arr[] = $part->toCode($indent);
			}
		}
		
		return implode("\n\n", $arr);
	}
}

class ExpressionKey
{
	protected $keys = array();
	
	protected $value = array();
	
	public function __construct($keys = array(), $value = array())
	{
		$this->keys = $keys;
		$this->value = $value;
	}
	
	public function set(array $keys, $value)
	{
		if(empty($this->keys))
		{
			$this->keys = $keys;
			$this->value[] = $value;
			
			return;
		}
		
		for($i = 0, $c = count($keys); $i < $c; $i++)
		{
			if(empty($this->keys[$i]))
			{
				$rest  = array_slice($keys, $i);
				$first = array_shift($rest);
				
				if( ! empty($this->value[$first]))
				{
					return $this->value[$first]->set($rest);
				}
				else
				{
					$this->value[$first] = new ExpressionKey($rest, array($value));
				}
				
				return;
			}
			elseif($this->keys[$i] != $keys[$i])
			{
				$rest_this = array_slice($this->keys, $i);
				$rest_new  = array_slice($keys, $i);
				
				$this->keys = array_slice($this->keys, 0, $i);
				
				$first_this = array_shift($rest_this);
				$first_new  = array_shift($rest_new);
				
				$tmp = $this->value;
				var_dump($tmp);
				
				$this->value = array();
				
				$this->value[$first_this] = new ExpressionKey($rest_this, $tmp);
				$this->value[$first_new]  = new ExpressionKey($rest_new, array($value));
				
				return;
			}
		}
		
		if(count($this->keys) > $i)
		{
			$rest_this = array_slice($this->keys, $i);
			
			$first_this = array_shift($rest_this);
			
			$tmp = $this->value;
			
			$this->value = array();
			
			$this->value[$first_this] = new ExpressionKey($rest_this, $tmp);
			$this->value[]            = $value;
		}
		else
		{
			$this->value[] = $value;
		}
	}
	
	public function toCode($indent = 0)
	{
		$ind = str_repeat("\t", $indent);
		$arr = array_map(function($key, $elem) use($ind, $indent)
		{
			if(is_object($elem) && $elem instanceof ExpressionKey)
			{
				$elem = $elem->toCode($indent + 1);
			}
			
			if( ! is_numeric($key))
			{
				return $ind.'if('.$key.")\n$ind{\n".$elem."\n$ind}";
			}
			else
			{
				return $elem;
			}
		}, array_keys($this->value), array_values($this->value));
		
		if(empty($this->keys))
		{
			return implode("\n\n", $arr);
		}
		else
		{
			return $ind.'if('.implode(' && ', $this->keys).")\n$ind{\n".implode("\n\n", $arr)."\n$ind}";
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
		
		$tmp = $this->values;
		
		$this->values = array();
		
		empty($tmp) OR $this->setRelativeKey($rest_this, $tmp, true);
		$this->setRelativeKey($rest_new, $value);
	}
	
	public function setRelativeKey($keys, $value, $copy = false)
	{
		$key = array_shift($keys);
		
		if(empty($key))
		{
			$this->values[] = $value;
		}
		elseif( ! empty($this->values[$key]))
		{
			$this->values[$key]->set($keys, $value);
		}
		else
		{
			var_dump($keys);
			$this->values[$key] = new Multimap($keys, $copy ? $value : array($value));
		}
	}
	
	public function toCode($indent = 0)
	{
		$ind = str_repeat("\t", $indent);
		
		$str = array();
		
		if( ! empty($this->keys))
		{
			$oind = $ind;
			$ind .= "\t";
			$indent++;
			
			$str[] = $oind.'if('.implode(' && ', $this->keys).")\n$oind{";
		}
		
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
		
		$data = array_intersect_key($this->values, array_flip(array_filter(array_keys($this->values), 'is_numeric')));
		
		empty($data) OR $str[] = $ind.'return array(array('.implode(', ', $data).'), $matches);';
		
		if( ! empty($this->keys))
		{
			$str[] = "$oind}";
		}
		
		return implode("\n", $str);
	}
}

class ExpressionGraph
{
	protected $keys = array();
	
	protected $values = array();
	
	public function set(array $keys, $value)
	{
		$key = array_shift($keys);
		
		foreach((Array) $key as $k)
		{
			if(empty($this->keys[$k]))
			{
				$this->keys[$k] = new ExpressionGraph();
			}
			
			if(empty($keys))
			{
				$this->keys[$k]->add($value);
			}
			else
			{
				$this->keys[$k]->set($keys, $value);
			}
		}
	}
	
	public function countConditions()
	{
		return count(array_keys($this->keys));
	}
	
	public function compile()
	{
		$arr = array();
		
		foreach($this->keys as $cond => $value)
		{
			if($value->countConditions() > 1)
			{
				
			}
		}
		
		foreach($this->values as $v)
		{
			
		}
	}
	
	public function add($value)
	{
		$this->values[] = $value;
	}
}
