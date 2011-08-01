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
 * Represents a Regular Expression alternation, ie. part1|part2
 */
class Alternation extends Pattern
{
	protected $first_parts = array();
	
	protected $parts = array();
	
	public function __construct(array $first = null, array $second = null)
	{
		empty($first)  OR $this->first_parts = $first;
		empty($second) OR $this->parts       = $second;
	}
	
	public function getParts()
	{
		return array(array($first_parts), array($parts));
	}
	
	public function toPattern(Closure $escaper)
	{
		$str1 = array();
		
		foreach($this->first_parts as $part)
		{
			if(is_string($part))
			{
				$str1[] = $escaper($part);
			}
			else
			{
				$str1[] = $part->toPattern($escaper);
			}
		}
		
		return implode('', $str1).'|'.parent::toPattern($escaper);
	}
}