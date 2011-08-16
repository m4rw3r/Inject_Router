<?php

namespace Inject\Router\CompilingDynamic;

interface RouteEntryInterface
{
	/**
	 * Returns a list of characters which should be matched statically,
	 * false equals end (ie. == ( ! isset($str[$offset]))) and object is
	 * custom condition object.
	 * 
	 * @return array(mixed)
	 */
	public function getKeyParts();
	
	/**
	 * The comment to add before the return statement when a match is found.
	 * 
	 * @return string
	 */
	public function getComment();
	
	/**
	 * Sets the id for this route entry.
	 * 
	 * @param  int
	 * @return void
	 */
	public function setId($id);
	
	/**
	 * Returns the id for this route entry, corresponds to a callback.
	 * 
	 * @return int
	 */
	public function getId();
	
	/**
	 * Returns some code which will be run if all the key parts matches,
	 * this code must return an array with data to be added to the captures array.
	 */
	public function getExtraCode();
}