<?php

namespace Inject\Router\CompilingDynamic;

class RouteGraphKey extends RouteGraph
{
	public function __construct($key)
	{
		$this->key = $key;
	}
	
	public function add(array $key, RouteEntryInterface $value)
	{
		if(empty($key))
		{
			$this->values[] = $value;
		}
		else
		{
			$k = array_shift($key);
			
			foreach($this->children as $child)
			{
				if($child->key == $k)
				{
					$child->add($key, $value);
					
					return;
				}
			}
			
			$this->children[] = $re = new RouteGraphKey($k);
			
			$re->add($key, $value);
		}
	}
}