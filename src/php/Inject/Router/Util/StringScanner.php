<?php
/*
 * Created by Martin Wernståhl on 2011-07-24.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Router\Util;

class StringScanner extends \ArrayObject
{
	protected $scanned = '';
	
	protected $rest = '';
	
	public function __construct($string)
	{
		$this->rest = $string;
	}
	
	/**
	 * Pops of the first character of the buffer and returns it.
	 * 
	 * @return string
	 */
	public function getch()
	{
		if(empty($rest))
		{
			return false;
		}
		
		$c          = $this->rest[0];
		$this->rest = substr($this->rest, 1);
		
		return $c;
	}
	
	/**
	 * Attempts to match $regex from the string-pointer, returns the matched string if
	 * the regex matches, captures will be stored in the internal array.
	 * 
	 * @param string  Partial regexp, without start or end (not including ^ and $)
	 */
	public function scan($regex)
	{
		if(preg_match("\1^".$regex."\1", $this->rest, $matches))
		{
			$this->exchangeArray($matches);
			$this->scanned  = $matches[0];
			
			$this->rest = substr($this->rest, strlen($matches[0]));
			
			return $matches[0];
		}
		
		return false;
	}
	
	/**
	 * @param string  Partial regexp, without start or end (not including ^ and $)
	 */
	public function scanUntil($regex)
	{
		if(preg_match("\1^[\w\W]*".$regex."\1", $this->rest, $matches))
		{
			$this->exchangeArray($matches);
			$this->scanned  = $matches[0];
			
			$this->rest = substr($this->rest, strlen($matches[0]));
			
			return $matches[0];
		}
		
		return false;
	}
	
	/**
	 * @param string  Partial regexp, without start or end (not including ^ and $)
	 */
	public function check($regex)
	{
		if(preg_match("\1^".$regex."\1", $this->rest, $matches))
		{
			$this->exchangeArray($matches);
			
			return $matches[0];
		}
		
		return false;
	}
	
	/**
	 * @param string  Partial regexp, without start or end (not including ^ and $)
	 */
	public function checkUntil($regex)
	{
		if(preg_match("\1^[\w\W]*".$regex."\1", $this->rest, $matches))
		{
			$this->exchangeArray($matches);
			
			return $matches[0];
		}
		
		return false;
	}
	
	/**
	 * Returns the next character to be read.
	 * 
	 * @return string
	 */
	public function peek($num = 1)
	{
		return substr($this->rest, 0, $num);
	}
	
	/**
	 * Returns the not-yet-parsed string.
	 * 
	 * @return string
	 */
	public function getRest()
	{
		return $this->rest;
	}
	
	/**
	 * Reverts the last scan action.
	 * 
	 * @return void
	 */
	public function unscan()
	{
		$this->rest = $this->scanned . $this->rest;
	}
	
	/**
	 * Returns true if this StringScanner's buffer is empty.
	 * 
	 * @return boolean
	 */
	public function isEmpty()
	{
		return empty($this->rest);
	}
}