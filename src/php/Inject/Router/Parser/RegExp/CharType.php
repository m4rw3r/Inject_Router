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
 * Represents a Regular Expression generic character type, ("\d", "\D", "\s", "\S", "\w" or "\W").
 */
class CharType implements PartInterface
{
	protected $part;
	
	public function __construct($part)
	{
		if( ! preg_match('/^\\\\[dDsSwW]$/', $part))
		{
			throw new \Exception("Unrecognized char-type \"$part\".");
		}
		
		$this->part = $part;
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
		return $this->part;
	}
}	