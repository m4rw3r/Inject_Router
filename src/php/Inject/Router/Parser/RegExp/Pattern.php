<?php
/*
 * Created by Martin Wernståhl on 2011-07-24.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Router\Parser\RegExp;

use \Closure;
use \Inject\Router\Parser\RegExp;
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
				$this->parts[] = new CharType($text);
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
					// (?<name>regex), (?P<name>regex) and (?'name'regex)
					if($str->scan('\\?(?:P)?(<|\')(\w+)(?:\1|>)'))
					{
						// Name, but named groups still have a capture index
						$regex = new Capture(++RegExp::$capture_index, $str[2]);
					}
					else
					{
						// No name, increment capture index
						$regex = new Capture(++RegExp::$capture_index);
					}
					
					$regex->parse($str, $unescaper);
					
					$this->parts[] = $regex;
				}
			}
			// Closing code for Capture or Options
			else if($str->scan('\\)'))
			{
				// TODO: How to count parenthesis?
				return;
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
			else if($str->check('\\{'))
			{
				$quant = new Quantifier();
				
				$quant->parse($str, $unescaper);
				
				$quant->setParts(array(array_pop($this->parts)));
				
				$this->parts[] = $quant;
			}
			// Pattern1|Pattern2
			else if($str->scan('\\|'))
			{
				$alt = new Alternation($this->parts);
				
				$alt->parse($str, $unescaper);
				
				$this->parts = array($alt);
				
				return;
			}
			// "." wildcard
			else if($str->scan('\\.'))
			{
				$this->parts[] = new Wildcard();
			}
			else if($str->scan('\\?'))
			{
				$count = new Quantifier();
				$count->setMax(1); // 0 is default min
				
				$count->setParts(array(array_pop($this->parts)));
				
				$this->parts[] = $count;
			}
			// 1 or more
			else if($str->scan('\\+'))
			{
				$count = new Quantifier();
				$count->setMin(1);
				
				$count->setParts(array(array_pop($this->parts)));
				
				$this->parts[] = $count;
			}
			// Any
			else if($str->scan('\\*'))
			{
				$count = new Quantifier(); // min = 0, max = infinity is default
				
				$count->setParts(array(array_pop($this->parts)));
				
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