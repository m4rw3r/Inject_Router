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
 * An anchor pointing to a position in a string.
 * 
 * TODO: Add support for \1, \2 etc. anchors
 */
class Anchor implements PartInterface
{
	/**
	 * A starting anchor.
	 */
	const START = 'START';
	
	/**
	 * An ending anchor.
	 */
	const END = 'END';
	
	protected $type;
	
	protected $text;
	
	public function __construct($type, $text)
	{
		$this->type = $type;
		$this->text = $text;
	}
	
	public function getType()
	{
		return $this->type;
	}
	
	public function parse(StringScanner $str, Closure $unescaper)
	{
		throw new \Exception(__METHOD__.' should not be called.');
	}
	
	public function getParts()
	{
		return array();
	}
	
	public function toPattern(Closure $escaper)
	{
		return $this->text;
	}
}