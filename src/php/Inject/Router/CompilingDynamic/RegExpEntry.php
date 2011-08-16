<?php

namespace Inject\Router\CompilingDynamic;

use \Inject\Router\Parser\RegExp;

class RegExpEntry implements RouteEntryInterface
{
	protected $id;
	protected $code;
	protected $pattern;
	protected $parts = array();
	
	public function __construct(RegExp $exp)
	{
		$this->pattern = $exp;
	}
	
	public function getKeyParts()
	{
		return $this->extractStaticParts($this->pattern->getPatternObject()->getParts());
	}
	
	public function getComment()
	{
		return $this->pattern->getPattern();
	}
	
	public function setId($id)
	{
		$this->id = $id;
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	public function getExtraCode()
	{
		$keys = $this->pattern->getNamedCaptures();
		
		$keys = array_flip($keys);
		
		if( ! empty($keys))
		{
			return 'array_intersect_key($matches, '.var_export($keys, true).')';
		}
		else
		{
			return null;
		}
	}
	
	public function getCondition($varname)
	{
		return 'preg_match('.var_export($this->pattern->getPattern('/'), true).', '.$varname.', $matches)';
	}
	
	protected function extractStaticParts($parts)
	{
		$match = array();
		
		foreach($parts as $part)
		{
			if(self::isDynamic($part))
			{
				// End it
				$match[] = $this;
				
				break;
			}
			else if(is_string($part))
			{
				$match[] = $part;
			}
			else if($part instanceof RegExp\Alternation)
			{
				$alts = $part->getParts();
				
				// TODO: Does it handle these correctly?
				$match[] = array($this->extractStaticParts($alts[0][0]), $this->extractStaticParts($alts[0][1]));
			}
			else if($part instanceof RegExp\Anchor &&
			        $part->getType() == RegExp\Anchor::END)
			{
				// false == string end
				$match[] = false;
			}
			else if($part instanceof RegExp\Pattern)
			{
				$match = array_merge($match, $this->extractStaticParts($part->getParts()));
			}
			else
			{
				ob_start();
				var_dump($part);
				
				throw new \Exception('Cannot handle '.trim(ob_get_clean()));
			}
		}
		
		return $match;
	}
	
	protected static function isDynamic($part)
	{
		return $part instanceof RegExp\Capture    OR
		       $part instanceof RegExp\CharClass  OR
		       $part instanceof RegExp\CharType   OR
		       $part instanceof RegExp\PosixClass OR
		       $part instanceof RegExp\Quantifier OR
		       $part instanceof RegExp\Wildcard;
	}
	
	public function __toString()
	{
		return (String)$this->getId();
	}
}