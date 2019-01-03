<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\LivePreview;

/**
 * LivePreview Service
 */
class LivePreview {

	/**
	 * @var obj $session_delegate A Session object
	 */
	private $session_delegate;

	/**
	 * @var string $cache_class The class name to hand off to the Session cache
	 */
	private $cache_class = 'channel_entry';

	/**
	 * @var string $key The key to hand off to the Session cache
	 */
	private $key = 'live-preview';

	/**
	 * Constructor
	 *
	 * @param obj $session_delegate A Session object
	 */
	public function __construct($session_delegate)
	{
		$this->session_delegate = $session_delegate;
	}

	/**
	 * Do we have entry data?
	 *
	 * @return bool TRUE if it is, FALSE if it is not
	 */
	public function hasEntryData()
	{
		return ($this->getEntryData() !== FALSE);
	}

	/**
	 * Gets the entry data for the live preview.
	 *
	 * @return array|bool Array of entry data or FALSE if there is no preview data
	 */
	public function getEntryData()
	{
		return $this->session_delegate->cache($this->cache_class, $this->key, FALSE);
	}

	/**
	 * Sets the live preview data
	 *
	 * @param array $data The entry data
	 * @return void
	 */
	public function setEntryData($data)
	{
		$this->session_delegate->set_cache($this->cache_class, $this->key, $data);
	}
}

// EOF
