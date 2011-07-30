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
 * Representation of a Character Class from a regex [abcd].
 */
class CharClass implements PartInterface
{
	protected $parts = array();
	
	protected $inverted = false;
	
	public function __construct($inverted = false, $part = null)
	{
		$this->inverted = $inverted;
		
		empty($part) OR $this->parts[] = $part;
	}
	
	public function isInverted()
	{
		return $this->inverted;
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
				throw new \Exception("Could not parse ".$str->getRest()." unexpected character in a character class.");
			}
		}
		
		throw new \Exception("Could not parse ".$str->getRest()." unexpected end of character class.");
	}
	
	public function toPattern(Closure $escaper)
	{
		$str = array();
		
		foreach($this->parts as $part)
		{
			if(is_string($part))
			{
				// TODO: Escape?
				$str[] = $part;
			}
			else
			{
				$str[] = $part->toPattern($escaper);
			}
		}
		
		$str = implode('', $str);
		
		if(count($this->parts) == 1)
		{
			return $str;
		}
		else
		{
			return '['.$str.']';
		}
	}
}