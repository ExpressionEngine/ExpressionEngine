<?php

namespace EllisLab\Addons\FilePicker\Service\FilePicker;

use EllisLab\ExpressionEngine\Service\URL\URLFactory;
use EllisLab\ExpressionEngine\Model\File\File;

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

	public function setDirectories($dirs)
	{
		$this->directories = $dirs;
	}

	public function getUrl()
	{
		$qs = array('directory' => $this->directories);

		return $this->url->make(static::CONTROLLER, $qs);
	}

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
