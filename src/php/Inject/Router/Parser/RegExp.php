<?php
/*
 * Created by Martin Wernståhl on 2011-07-24.
 * Copyright (c) 2011 Martin Wernståhl.
 * All rights reserved.
 */

namespace Inject\Router\Parser;

use \Inject\Router\Util\StringScanner;

class RegExp
{
	public static $capture_index = 0;
	
	protected static $regex_modifiers = array(
		'PCRE_CASELESS'       => 'i',
		'PCRE_MULTILINE'      => 'm',
		'PCRE_DOTALL'         => 's',
		'PCRE_EXTENDED'       => 'x',
		'PREG_REPLACE_EVAL'   => 'e',
		'PCRE_ANCHORED'       => 'A',
		'PCRE_DOLLAR_ENDONLY' => 'D',
		'S'                   => 'S', 
		'PCRE_UNGREEDY'       => 'U',
		'PCRE_EXTRA'          => 'X',
		'PCRE8'               => 'u'
	);
	
	protected $pattern;
	
	protected $delimiter = '/';
	
	/**
	 * An array of used modifier flags for the regular expression,
	 * Flag name => used flag
	 * 
	 * @var array(string => string)
	 */
	protected $modifiers = array();
	
	/**
	 * A list of capture names.
	 */
	protected $capture_names = array();
	
	// ------------------------------------------------------------------------
	
	public function __construct($pattern)
	{
		$pattern = $this->parseDelimitersModifiers($pattern);
		
		// Unescape delimiter chars as we have removed them:
		$pattern = str_replace('\\'.$this->delimiter, $this->delimiter, $pattern);
		
		$this->pattern = new RegExp\Pattern();
		
		// Reset capture index before parsing
		self::$capture_index = 0;
		
		$this->pattern->parse(new StringScanner($pattern), $this->getUnescaper());
		
		// $this->pattern->compress($this->getUnescaper());
		
		$this->collectData($this->pattern);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Extracts the delimiters and PREG modifiers of the pattern string and will
	 * return the pattern itself.
	 * 
	 * @param  string
	 * @return string
	 */
	protected function parseDelimitersModifiers($str)
	{
		$start     = $str[0];
		
		if(preg_match('/^[a-zA-Z0-9]|\\\\/', $start))
		{
			throw new \Exception('Invalid PREG delimiter, must not be alphanumeric or backslash');
		}
		
		$end_pos   = strrpos($str, $start);
		
		if($end_pos == false)
		{
			// No delimiters
			return new \Exception('No ending delimiter found in regexp "'.$str.'".');
		}
		
		$modifiers = substr($str, $end_pos + 1);
		
		if( ! empty($modifiers))
		{
			// Determine if we have invalid delimiters
			$invalid = array_diff(str_split($modifiers), static::$regex_modifiers);
			
			if(count($invalid) > 0)
			{
				// The chosen character is not a delimiter
				throw new \Exception('Invalid PREG modifiers ('.implode(', ', $invalid).') after delimiter "'.$start.'" found in regexp "'.$str.'".');
			}
		}
		
		// Set used delimiters
		$this->delimiter = $start;
		
		// Set used REGEX modifiers
		$this->modifiers = array_intersect(static::$regex_modifiers, str_split($modifiers));
		
		// Remove delimiters and modifiers
		return substr($str, 1, $end_pos - 1);
	}
	
	/**
	 * Iterates through the regular expression and collects data from the parsed nodes.
	 * 
	 * Currently only collects capture names.
	 */
	protected function collectData($pattern)
	{
		if(is_array($pattern))
		{
			foreach($pattern as $p)
			{
				$this->collectData($p);
			}
		}
		else
		{
			foreach($pattern->getParts() as $part)
			{
				if(is_object($part))
				{
					if($part instanceof RegExp\Capture)
					{
						$this->capture_names[] = $part->getName();
						
						$this->collectData($part->getParts());
					}
					elseif($part instanceof RegExp\Pattern)
					{
						$this->collectData($part->getParts());
					}
				}
			}
		}
	}
	
	/**
	 * Returns a list of the used captures.
	 * 
	 * @return array
	 */
	public function getCaptures()
	{
		return $this->capture_names;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Returns a list of the used named-captures.
	 * 
	 * @return array
	 */
	public function getNamedCaptures()
	{
		return array_filter($this->capture_names, function($elem)
		{
			return ! is_int($elem);
		});
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Returns the Regular Expression representation of this pattern.
	 * 
	 * @return string
	 */
	public function getPattern()
	{
		return $this->delimiter.str_replace($this->delimiter, '\\'.$this->delimiter,
			$this->pattern->toPattern($this->getEscaper())).$this->delimiter.
			implode('', $this->modifiers);
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Returns a function which will escape strings for use in this regular expression.
	 * 
	 * @return Closure
	 */
	public function getEscaper()
	{
		$delim = $this->delimiter;
		
		return function($str) use($delim)
		{
			return preg_quote($str, $delim);
		};
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Returns a function which will unescape the provided partial-Regular Expression string.
	 *
	 * @return Closure
	 */
	public function getUnescaper()
	{
		// Build the regular expression to facillitate the replace
		$pattern = '/\\\\(['.preg_quote('.\\+*?[^]$(){}=!<>|:-', '/');
		
		if($this->delimiter != null)
		{
			$pattern .= preg_quote($this->delimiter, '/');
		}
		
		$pattern .= '])/';
		
		return function($str) use($pattern)
		{
			return preg_replace_callback($pattern, function($matches)
			{
				return $matches[1];
			}, $str);
		};
	}
	
	// ------------------------------------------------------------------------

	/**
	 * 
	 * 
	 * @return 
	 */
	public function __toString()
	{
		return 'RegExp: Delim: '.$this->delimiter.' Modifiers: '.implode('', $this->modifiers)."\n".$this->pattern->__toString();
	}
}
