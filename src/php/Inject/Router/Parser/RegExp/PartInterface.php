<?php
/*
 * Created by Martin Wernståhl on 2011-07-24.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Router\Parser\RegExp;

use \Closure;
use \Inject\Router\Util\StringScanner;

interface PartInterface
{
	/**
	 * Attempts to parse this part from the StringScanner instance, does not
	 * usually parse the start of the pattern.
	 * 
	 * @param  StringScanner
	 * @param  Closure  A function which will unescape provided strings
	 * @return void
	 */
	public function parse(StringScanner $str, Closure $unescaper);
	
	/**
	 * Returns a list of the contained parts.
	 * 
	 * @return array
	 */
	public function getParts();
	
	/**
	 * Returns the partial Regular Expression pattern this object represents.
	 * 
	 * @param  Closure  A function which will escape strings for use in the expression
	 * @return string
	 */
	public function toPattern(Closure $escaper);
	
	/**
	 * Shows a compressed debug representation of this object and its children.
	 * 
	 * @return string
	 */
	//public function __toString();
}