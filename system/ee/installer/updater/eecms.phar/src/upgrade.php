<?php

$updater_boot = SYSPATH.'/ee/updater/boot.php';
if (file_exists($updater_boot))
{
	require_once $updater_boot;
}

class Command {

	public function __construct($params = [])
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

		$this->start();
	}

	public function start()
	{
		ee()->load->library('el_pings');
		$version_file = ee()->el_pings->get_version_info();
		$to_version = $version_file[0][0];

		if (version_compare(APP_VER, $to_version, '>='))
		{
			exit('ExpressionEngine '.APP_VER.' is already up-to-date!');
		}

		echo "There is a new version of ExpressionEngine available: " . $to_version . "\n";
		echo "Would you like to upgrade? (y/n): ";
		$stdin = trim(fgets(STDIN));
		if ( ! in_array($stdin, ['yes', 'y']))
		{
			exit;
		}

		// Preflight checks, download and unpack update
		ee('Updater/Runner')->run();

		// Launch into microapp
		runCommandExternally('upgrade --microapp --no-bootstrap');
	}

	public function updaterMicroapp($step = NULL)
	{
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
			$cmd = 'upgrade --microapp --step="'.$next_step.'"';

			// We can't rely on loading EE during these steps
			if ($next_step == 'updateFiles' OR $next_step == 'rollback')
			{
				$cmd .= ' --no-bootstrap';
			}

			runCommandExternally($cmd);
		}
	}
}

// EOF
