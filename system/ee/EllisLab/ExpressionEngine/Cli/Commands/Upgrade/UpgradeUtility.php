<?php

namespace EllisLab\ExpressionEngine\Cli\Commands\Upgrade;

use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;

class UpgradeUtility
{

	public static function run()
	{
		self::install_modules();
		self::remove_installer_directory();
	}

	protected static function install_modules()
	{

		$required_modules = [
			'channel',
			'comment',
			'consent',
			'member',
			'stats',
			'rte',
			'file',
			'filepicker',
			'relationship',
			'search'
		];

		ee()->load->library('addons');
		ee()->addons->install_modules($required_modules);

		$consent = ee('Addon')->get('consent');
		$consent->installConsentRequests();

	}

	protected static function remove_installer_directory()
	{

		$installerPath = SYSPATH . 'ee/installer';

		if(is_dir($installerPath)) {
			rmdir($installerPath);
		}

	}

}