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
 * Representation of a Regular Expression wildcard, ".".
 */
class Wildcard implements PartInterface
{
	public function __construct()
	{
		
	}
	
	public function parse(StringScanner $str, Closure $unescaper)
	{
		throw new \Exception(__METHOD__.' should not be called.');
	}
	
	public function getParts()
	{
		return array();
	}
	
	public function isLiteral()
	{
		return false;
	}
	
	public function toPattern(Closure $escaper)
	{
		return '.';
	}
}	