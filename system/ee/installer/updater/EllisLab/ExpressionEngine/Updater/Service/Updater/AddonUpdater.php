<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Updater\Service\Updater;

use EllisLab\ExpressionEngine\Updater\Service\Updater\SteppableTrait;

/**
 * Database updater for one-click updater
 *
 * Runs the ud_x_xx_xx.php files needed to complete the update
 */
class AddonUpdater {

	use SteppableTrait;

	protected $from_version;

	/**
	 * Construct class
	 *
	 * @param	string		$from_version	Version we are updating from
	 * @return  void
	 */
	public function __construct($from_version)
	{
		$this->from_version = $from_version;
	}

	/**
	 * runs upgrader for each installed addon
	 * @return array
	 */
	public function updateAddons()
	{

		$addons = ee('Addon')->all();

		$results = [];

		foreach ($addons as $name => $info)
		{
			$info = ee('Addon')->get($name);

			// If it's built in, we'll skip it
			if ($info->get('built_in')) {
				continue;
			}

			// If it doesn't have an upgrader, there's nothing to do
			if( ! $info->hasUpgrader() ) {
				continue;
			}

			try {

				$success = $this->processAddon($name, $info);

			} catch (\Exception $e) {

				$success = false;

			}

			$results[$name] = $success;

		}

		return $results;

	}

	// PRIVATE FUNCTIONS
	/**
	 * runs the upgrader class for an addon
	 * @param  string $name [name of addon]
	 * @param  stdClass $info [info on class of addon]
	 * @return mixed
	 */
	private function processAddon($name, $info)
	{

		$upgrader = $info->getUpgraderClass();

		$result = (new $upgrader)->upgrade($this->from_version);

		// If we are updating from the CLI, we can ensure that all addons get updated at each step
		// This is a great deal slower, but makes life easier
		if(defined('CLI_UPDATE_FORCE_ADDON_UPDATE') && CLI_UPDATE_FORCE_ADDON_UPDATE) {

			$updater = $info->getInstallerClass();

			$updater->update($this->from_version);

		}

		return $result;

	}

}