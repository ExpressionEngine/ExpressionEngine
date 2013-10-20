<?php
namespace EllisLab\ExpressionEngine\Module\Channel;

class ChannelModule extends Module {
	protected $di = NULL;

	public function __construct(Dependencies $di)
	{
		$this->di = new ChannelDependencies($di);
	}

	public function getTemplateInterpreter()
	{
		return new ChannelTemplateInterpreter($this->di);
	}

	public function install()
	{}

	public function uninstall()
	{}

	public function disable()
	{}





}
