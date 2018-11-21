<?php

$updater_boot = SYSPATH.'/ee/updater/boot.php';
if (file_exists($updater_boot))
{
	require_once $updater_boot;
}

class Command {

	/**
	 * Constructor
	 *
	 * @param array $params CLI arguments as parsed by parseArguments()
	 */
	public function __construct($params = [])
	{
		set_error_handler(array($this, 'showError'));

		try
		{
			if (isset($params['microapp']))
			{
				$step = (isset($params['step'])) ? $params['step'] : NULL;
				return $this->updaterMicroapp($step);
			}

			if (isset($params['rollback']))
			{
				return $this->updaterMicroapp('rollback');
			}

			$this->start( ! isset($params['y']));
		}
		catch (\Exception $e)
		{
			$this->showError($e->getCode(), $e->getMessage());
			exit;
		}
	}

	/**
	 * Kicks off a new upgrade
	 */
	public function start($interactive = TRUE)
	{
		ee()->load->library('el_pings');
		$version_file = ee()->el_pings->get_version_info(TRUE);
		$to_version = $version_file['latest_version'];

		if (version_compare(ee()->config->item('app_version'), $to_version, '>='))
		{
			exit('ExpressionEngine '.APP_VER.' is already up-to-date!');
		}

		echo "There is a new version of ExpressionEngine available: " . $to_version . "\n";

		if ($interactive)
		{
			echo "Would you like to upgrade? (y/n): ";
			$stdin = trim(fgets(STDIN));
			if ( ! in_array($stdin, ['yes', 'y']))
			{
				exit;
			}
		}

		// Preflight checks, download and unpack update
		ee('Updater/Runner')->run();

		// Launch into microapp
		runCommandExternally('upgrade --microapp --no-bootstrap');
	}

	/**
	 * Runs a step through the updater microapp
	 *
	 * @param string $step The name of the step to run
	 */
	public function updaterMicroapp($step = NULL)
	{
		if ( ! class_exists('EllisLab\ExpressionEngine\Updater\Service\Updater\Runner'))
		{
			exit('Cannot rollback, updater microapp not found.');
		}

		$runner = new EllisLab\ExpressionEngine\Updater\Service\Updater\Runner();

		if ( ! $step)
		{
			$step = $runner->getFirstStep();
		}

		$runner->runStep($step);

		// Perform each step as its own command so we can control the scope of
		// files loaded into the app's memory
		if (($next_step = $runner->getNextStep()) !== FALSE)
		{
			if ($next_step == 'rollback')
			{
				return runCommandExternally('upgrade --rollback');
			}

			$cmd = 'upgrade --microapp --step="'.$next_step.'"';

			// We can't rely on loading EE during these steps
			if ($next_step == 'updateFiles')
			{
				$cmd .= ' --no-bootstrap';
			}

			runCommandExternally($cmd);
		}
	}

	/**
	 * Custom PHP error handler
	 */
	public function showError($code, $error, $file = NULL, $line = NULL)
	{
		if (error_reporting() === 0)
		{
			return;
		}

		$message = "We could not complete the update because an error has occured:\n\033[0m";
		$message .= strip_tags($error);

		if ($file && $line)
		{
			$message .= "\n\n".$file.':'.$line;
		}

		stdout($message, CLI_STDOUT_FAILURE);
		exit;
	}
}

// EOF
