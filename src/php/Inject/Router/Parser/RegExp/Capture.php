<?php
/*
 * Created by Martin Wernståhl on 2011-07-24.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Router\Parser\RegExp;

use \Closure;

/**
 * A capture.
 */
class Capture extends Pattern
{
	protected $index = 0;
	
	protected $name = false;
	
	public function __construct($index, $name = false)
	{
		$this->index = $index;
		$this->name  = $name;
	}
	
	public function setName($name)
	{
		$this->name = $name;
	}
	
	/**
	 * Returns the index this capture has in the regex.
	 * 
	 * @return int
	 */
	public function getIndex()
	{
		return $this->index;
	}
	
	/**
	 * Returns the name of this capture, false if no name.
	 * 
	 * @return string|false
	 */
	public function getName()
	{
		return $this->name;
	}
	
	public function isLiteral()
	{
		return false;
	}
	
	public function toPattern(Closure $escaper)
	{
		if( ! $this->name)
		{
			return '('.parent::toPattern($escaper).')';
		}
		else
		{
			return '(?<'.$this->name.'>'.parent::toPattern($escaper).')';
		}
	}
}
