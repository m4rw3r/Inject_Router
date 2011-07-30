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
 * The basic Regular Expression pattern list.
 */
class Pattern implements PartInterface
{
	protected $parts = array();
	
	public function parse(StringScanner $str, Closure $unescaper)
	{
		while( ! $str->isEmpty())
		{
			if($text = $str->scan('\\\\[dDsSwWn]'))
			{
				$this->parts[] = new CharClass(false, $text);
			}
			else if($text = $str->scan('\\^|\\\\A'))
			{
				$this->parts[] = new Anchor(Anchor::START, $text);
			}
			else if($text = $str->scan('\$|\\\\Z'))
			{
				$this->parts[] = new Anchor(Anchor::END, $text);
			}
			// Start of optional secion or capture
			else if($text = $str->scan('\\('))
			{
				if($str->scan('\\?(:|=|!|<=|<!)'))
				{
					// Grouping
					$part = new Options($str[1]);
					
					$part->parse($str, $unescaper);
					
					$this->parts[] = $part;
				}
				else
				{
					// Normal capture
					$regex = new Capture();
					
					if($str->scan('\\?<(\w+)>'))
					{
						$regex->setName($str[1]);
					}
					
					$regex->parse($str, $unescaper);
					
					$this->parts[] = $regex;
				}
			}
			// Closing code for Capture or Options
			else if($str->scan('\\)'))
			{
				if($this instanceof Capture OR $this instanceof Options)
				{
					return;
				}
				else
				{
					throw new \Exception('Unexpected ending parenthesis at "'.$str->getRest().'".');
				}
			}
			// [:class:], [charclass]
			else if($str->scan('\\['))
			{
				if($str->scan(':'))
				{
					$char_class = new POSIXClass();
				}
				else
				{
					$char_class = new CharClass($str->scan('\\^') ? true : false);
				}
				
				$char_class->parse($str, $unescaper);
				
				$this->parts[] = $char_class;
			}
			// x{n}, x{n,}, x{n,m}
			else if($str->scan('\\{'))
			{
				$count = new Count();
				
				$count->parse($str, $unescaper);
				
				$count->setPart(array_pop($this->parts));
				
				$this->parts[] = $count;
			}
			// Pattern1|Pattern2
			else if($str->scan('\\|'))
			{
				$choice = new Choice();
				$choice->setFirstParts($this->parts);
				
				$pattern = new Pattern();
				$pattern->parse($str);
				
				$choice->setSecondPart($pattern->getParts());
				
				$this->parts = array($choice);
				
				return;
			}
			// "."
			else if($str->scan('\\.'))
			{
				$this->parts[] = new Wildcard();
			}
			else if($str->scan('\\?'))
			{
				$count = new Count();
				$count->setMax(1); // 0 is default min
				
				$count->setPart(array_pop($this->parts));
				
				$this->parts[] = $count;
			}
			// 1 or more
			else if($str->scan('\\+'))
			{
				$count = new Count();
				$count->setMin(1);
				
				$count->setPart(array_pop($this->parts));
				
				$this->parts[] = $count;
			}
			// Any
			else if($str->scan('\\*'))
			{
				$count = new Count(); // min = 0, max = infinity is default
				
				$count->setPart(array_pop($this->parts));
				
				$this->parts[] = $count;
			}
			// \x
			else if($text = $str->scan('\\\\.'))
			{
				$this->parts[] = $unescaper($text);
			}
			else if($text = $str->scan('.'))
			{
				$this->parts[] = $text;
			}
			else
			{
				throw new \Exception('Parse error at '.$str->getRest().'.');
			}
		}
	}
	
	public function compress(Closure $unescaper)
	{
		reset($this->parts);
		
		while($next = current($this->parts))
		{	
			if($next instanceof Pattern)
			{
				$next->compress($unescaper);
			}
			else if(is_string($next))
			{
				// Attempt to look at the previous element
				$prev = prev($this->parts);
				
				if(key($this->parts) === null)
				{
					// Reset, because we suddenly went outside the array
					reset($this->parts);
				}
				else if(is_string($prev))
				{
					// Yup, it is a string, concatenate with previous
					$this->parts[key($this->parts)] .= next($this->parts);
					
					// Remove concatenated element and step back to compensate
					unset($this->parts[key($this->parts)]);
					prev($this->parts);
				}
				else
				{
					// No string, move forward to compensate for backwards move
					next($this->parts);
				}
			}
			
			next($this->parts);
		}
	}
	
	public function getParts()
	{
		return $this->parts;
	}
	
	public function toPattern(Closure $escaper)
	{
		$str = array();
		
		foreach($this->parts as $part)
		{
			if(is_string($part))
			{
				$str[] = $escaper($part);
			}
			else
			{
				$str[] = $part->toPattern($escaper);
			}
		}
		
		return implode('', $str);
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __toString()
	{
		$str = array();
		
		foreach($this->parts as $p)
		{
			if(is_string($p))
			{
				$str[] = "String \"$p\"";
			}
			else
			{
				$str[] = $p->__toString();
			}
		}
		
		return implode("\n", $str);
	}
}