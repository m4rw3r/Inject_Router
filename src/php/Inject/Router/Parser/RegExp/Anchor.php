<?php
/*
 * Created by Martin Wernståhl on 2011-07-24.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Router\Parser\RegExp;

use \Closure;

/**
 * An anchor pointing to a position in a string.
 * 
 * TODO: Add support for \1, \2 etc. anchors
 */
class Anchor
{
	/**
	 * A starting anchor.
	 */
	const START = 'start';
	
	/**
	 * An ending anchor.
	 */
	const END = 'end';
	
	protected $part;
	
	protected $text;
	
	public function __construct($part, $text)
	{
		$this->part = $part;
		$this->text = $text;
	}
	
	public function getPart()
	{
		return $this->part;
	}
	
	public function toPattern(Closure $escaper)
	{
		return $this->text;
	}
}