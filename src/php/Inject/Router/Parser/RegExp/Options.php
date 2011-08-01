<?php
/*
 * Created by Martin Wernståhl on 2011-07-24.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Router\Parser\RegExp;

use \Closure;

/**
 * A (?...) part from the regex.
 */
class Options extends Pattern
{
	// TODO: Support flags
	
	public function toPattern(Closure $escaper)
	{
		return '(?:'.parent::toPattern($escaper).')';
	}
}
