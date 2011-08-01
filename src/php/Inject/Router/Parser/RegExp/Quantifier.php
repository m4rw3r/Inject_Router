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
 * Pattern part multiplier, like {n}, *, +, etc.
 */
class Quantifier extends Pattern
{
	/**
	 * Smallest number of repetitions of the contained pattern, must be >= 0.
	 * 
	 * @var int
	 */
	protected $min = 0;
	
	/**
	 * Maximum number of repetitions of the contained pattern, null = infinity.
	 * 
	 * @var int|null
	 */
	protected $max = null;
	
	public function setMin($min)
	{
		$this->min = $min;
	}
	
	public function setMax($max)
	{
		$this->max = $max;
	}
	
	public function setParts(array $parts)
	{
		$this->parts = $parts;
	}
	
	/**
	 * Returns the minimum times the wrapped part can be repeated.
	 * 
	 * @return int
	 */
	public function getMin()
	{
		return $this->min;
	}
	
	/**
	 * Returns the maximum number of times the wrapped part can be
	 * repeated, null = infinity.
	 * 
	 * @return int|null
	 */
	public function getMax()
	{
		return $this->max;
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Parses a count definition ({n}, {n,} and{n,m}).
	 * 
	 * @return void
	 */
	public function parse(StringScanner $str, Closure $unescaper)
	{
		if($str->scan('\\{(\\d+)(,)?(\\d+)?'))
		{
			$this->setMin($str[1]);
			
			if(isset($str[2]))
			{
				if(isset($str[3]))
				{
					// {n,m}
					$this->setMax($str[3]);
				}
				else
				{
					// {n,}, no logic as infinity is default max
				}
			}
			else
			{
				$this->setMax($str[1]);
			}
			
			if( ! $str->scan('\\}'))
			{
				throw new \Exception('Missing closing curly brace at '.$str->getRest().'.');
			}
		}
		else
		{
			throw new \Exception("Faulty count definition in curly brace at ".$str->getRest().".");
		}
	}
	
	public function toPattern(Closure $escaper)
	{
		$str = parent::toPattern($escaper);
		
		if($this->max == null)
		{
			if($this->min == 0)
			{
				return $str.'*';
			}
			elseif($this->min == 1)
			{
				return $str.'+';
			}
			else
			{
				return $str.'{'.$this->min.',}';
			}
		}
		else
		{
			if($this->min == 0 && $this->max == 1)
			{
				return $str.'?';
			}
			elseif($this->min == $this->max)
			{
				return $str.'{'.$this->min.'}';
			}
			else
			{
				return $str.'{'.$this->min.','.$this->max.'}';
			}
		}
	}
}