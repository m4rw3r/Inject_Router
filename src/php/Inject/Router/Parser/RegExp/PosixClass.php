<?php
/*
 * Created by Martin Wernståhl on 2011-07-24.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Router\Parser\RegExp;

use \Closure;
use \Inject\Router\Util\StringScanner;

class POSIXClass implements PartInterface
{
	protected $name = '';
	
	public function parse(StringScanner $str, Closure $escaper)
	{
		if($str->scan('(\w+):\\]'))
		{
			$this->name = $str[1];
		}
		else
		{
			throw new \Exception("Could not parse ".$str->getRest().", not a POSIX character class");
		}
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function toPattern(Closure $escaper)
	{
		return '[:'.$this->name.':]';
	}
}