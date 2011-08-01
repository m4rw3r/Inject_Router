<?php
/*
 * Created by Martin Wernståhl on 2011-07-24.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Router\Parser\RegExp;

use \Closure;
use \Inject\Router\Util\StringScanner;

/**
 * Representation of a Regular Expression character-class ("[abcd]"), a wildcard (".")
 * or a generic character type ("\d", "\D", "\s", "\S", "\w" or "\W").
 */
class CharClass implements PartInterface
{
	protected $parts = array();
	
	/**
	 * 
	 */
	protected $inverted = false;
	
	public function __construct($part = null, $inverted = false)
	{
		$this->inverted = $inverted;
		
		empty($part) OR $this->parts[] = $part;
	}
	
	/**
	 * Returns true if this Regular Expression part should be wrapped in
	 * brackets or not.
	 * 
	 * If the pattern only contains a wildcard (".") or a generic character type
	 * ("\d", "\D", "\s", "\S", "\w" or "\W").
	 * 
	 * @return boolean
	 */
	public function useBrackets()
	{
		return $this->inverted OR count($this->parts) != 1 OR
		       $this->parts[0] != '.' && ! preg_match('/^\\\\[dDsSwW]$/', $this->parts[0]);
	}
	
	public function isInverted()
	{
		return $this->inverted;
	}
	
	public function getParts()
	{
		return $this->parts;
	}
	
	public function parse(StringScanner $str, Closure $unescaper)
	{
		while( ! $str->isEmpty())
		{
			// TODO: Support ranges
			if($str->scan('\]'))
			{
				return;
			}
			else if($text = $str->scan('(?:\\\\)?.'))
			{
				$this->parts[] = $unescaper($text);
			}
			else
			{
				throw new \Exception("Could not parse ".$str->getRest().", unexpected character in a character class.");
			}
		}
		
		throw new \Exception("Could not parse ".$str->getRest().", unexpected end of character class.");
	}
	
	public function toPattern(Closure $escaper)
	{
		if( ! $this->useBrackets())
		{
			// Only one parts which should not be escaped
			return reset($this->parts);
		}
		else
		{
			$str = array();
			
			foreach($this->parts as $part)
			{
				if(is_string($part))
				{
					$str[] = $escaper($part);
				}
				else
				{
					$str[] = $part->toPattern($escaper);
				}
			}
			
			$str = implode('', $str);
			
			return $this->inverted ? '[^'.$str.']' : '['.$str.']';
		}
	}
}