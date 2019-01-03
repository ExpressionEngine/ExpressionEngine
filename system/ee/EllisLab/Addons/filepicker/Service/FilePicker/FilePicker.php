<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Addons\FilePicker\Service\FilePicker;

use EllisLab\ExpressionEngine\Service\URL\URLFactory;
use EllisLab\ExpressionEngine\Model\File\File;

/**
 * FilePicker Service
 */
class FilePicker {

	const CONTROLLER = 'addons/settings/filepicker/modal';

	protected $url;
	protected $active;
	protected $image_id;
	protected $directories = 'all';

	public function __construct(URLFactory $url)
	{
		$this->url = $url;
	}

	/**
	 * Set the allowed directories
	 *
	 * @param String $dirs Allowed directories
	 * @return FilePicker
	 */
	public function setDirectories($dirs)
	{
		$this->directories = $dirs;
		return $this;
	}

	/**
	 * Get a CP\URL instance that points to the filepicker endpoint
	 *
	 * @return CP\URL
	 */
	public function getUrl()
	{
		$qs = array('directories' => $this->directories);

		if (is_numeric($this->directories))
		{
			$qs['directory'] = $this->directories;
		}

		return $this->url->make(static::CONTROLLER, $qs);
	}

	/**
	 * Get a new Link instance
	 *
	 * @param String $text The link text [optional]
	 * @return Link
	 */
	public function getLink($text = NULL)
	{
		$link = new Link($this);

		if (isset($text))
		{
			$link->setText($text);
		}

		return $link;
	}
}

// EOF
