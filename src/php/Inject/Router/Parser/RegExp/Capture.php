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
	protected $name = 0;
	
	public function setName($name)
	{
		$this->name = $name;
	}
	
	/**
	 * Returns the name of this capture, false if no name.
	 */
	public function getName()
	{
		return $this->name;
	}
	
	public function toPattern(Closure $escaper)
	{
		if(is_int($this->name))
		{
			return '('.parent::toPattern($escaper).')';
		}
		else
		{
			return '(?<'.$this->name.'>'.parent::toPattern($escaper).')';
		}
	}
}
