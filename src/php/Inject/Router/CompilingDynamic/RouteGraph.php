<?php

namespace Inject\Router\CompilingDynamic;

class RouteGraph
{
	/**
	 *
	 */
	public static $tab = "\t";
	
	/**
	 * List of child keys.
	 * 
	 * @var array(RouteGraphKey)
	 */
	public $children = array();
	
	/**
	 * List of entries which correspond to the previous conditions including the key
	 * of this RouteGraphKey.
	 * 
	 * @var array(RouteEntryInterface)
	 */
	public $values = array();
	
	/**
	 * The key to match for this RouteGraphKey, string is just a normal "==" or strpos()
	 * match, false == ( ! isset($str[$offset])) and a RouteEntry means a custom condition.
	 * 
	 * @var mixed
	 */
	public $key = null;
	
	/**
	 * Adds a RoueEntry on the specified key path.
	 * 
	 * @param  array
	 * @param  RouteEntry
	 * @return void
	 */
	public function add(array $key, RouteEntryInterface $value)
	{
		// TODO: Support multiple alternatives for each key ($k)
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
	
	/**
	 * Converts a list of keys to a series of conditions on the $url variable.
	 * 
	 * @param  array  The list of conditions
	 * @param  int    The current index in $url to start the matching
	 * @return string
	 */
	public function toCondition(array $keys, &$char_index)
	{
		$end = end($keys);
		
		// Ending with object or false means special logic
		if(is_object($end) OR $end === false)
		{
			array_pop($keys);
		}
		
		$arr = array();
		if( ! empty($keys))
		{
			if(count($keys) > 7)
			{
				// stripos faster
				$arr[] = 'stripos($url, "'.implode('', $keys).'", '.$char_index.') === '.$char_index;
				
				$char_index += strlen(implode('', $keys));
			}
			else
			{
				// use isset() + == as that is faster below 7 chars and also fails faster
				foreach($keys as $char)
				{
					$arr[] = 'isset($url['.$char_index.']) && $url['.$char_index.'] == "'.$char.'"';
					
					$char_index++;
				}
			}
		}
		
		if(is_object($end))
		{
			$arr[] = $end->getCondition('$url');
		}
		else if($end === false)
		{
			// End
			$arr[] = '( ! isset($url['.$char_index.']))';
		}
		
		return implode(" && ", $arr);
	}
	
	public function toCode($indent = 0, $char_index = 0)
	{
		$children = $this->children;
		$values   = $this->values;
		$keys     = $this->key === null ? array() : array($this->key);
		
		// Merge as many conditions as possible
		while(count($children) == 1 && empty($values))
		{
			$entry  = reset($children);
			
			$keys[]   = $entry->key;
			
			$children = $entry->children;
			$values   = $entry->values;
		}
		
		$str = array();
		$ind = str_repeat(static::$tab, $indent);
		
		// Add if() if we have conditions
		if( ! empty($keys))
		{
			$str[] = $ind.'if('.$this->toCondition($keys, $char_index).')';
			$str[] = $ind.'{';
			
			$indent++;
			$ind = str_repeat(static::$tab, $indent);
		}
		
		// Sort the conditions so that those with objects are first (they are more complicated)
		usort($children, function($a, $b)
		{
			return is_object($a->key) - is_object($b->key);
		});
		
		foreach($children as $child)
		{
			$str[] = $child->toCode($indent + 1, $char_index);
		}
		
		$ids      = array();
		$ecode    = array();
		$comments = array();
		
		foreach($values as $value)
		{
			$ids[]      = $value->getId();
			$comments[] = $value->getComment();
			
			// Add the extra code if there is
			$code  = $value->getExtraCode();
			empty($code) OR in_array($code, $ecode) OR $ecode[] = $code;
		}
		
		if( ! empty($ids))
		{
			switch(count($ecode))
			{
				case 0:
					$edata = 'array()';
					break;
				case 1:
					$edata = reset($ecode);
					break;
				default:
					$edata = 'array_merge('.implode(', ', $ecode).')';
			}
			
			foreach($comments as $comment)
			{
				$str[] = $ind.'// '.$comment;
			}
			
			$str[] = $ind.'return array(array('.implode(', ', $ids).'), '.$edata.');';
		}
		
		// Matching bracket for the added if()
		if( ! empty($keys))
		{
			$str[] = str_repeat(static::$tab, $indent - 1).'}';
		}
		
		return implode("\n", $str);
	}
}