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
			$this->updaterMicroapp($step);
			return;
		}

		if (isset($params['rollback']))
		{
			$this->updaterMicroapp('rollback');
			return;
		}

		$this->start();
	}

	public function start()
	{
		ee('Updater/Runner')->run();

		// TODO: Abstract into helper method to run other eecms CLI commands?
		system('php '.SYSPATH.'ee/eecms.phar upgrade --microapp --no-bootstrap');
	}

	public function updaterMicroapp($step = NULL)
	{
		$runner = new EllisLab\ExpressionEngine\Updater\Service\Updater\Runner();

		if ( ! $step)
		{
			$step = $runner->getFirstStep();
		}

		$runner->runStep($step);

		if (($next_step = $runner->getNextStep()) !== FALSE)
		{
			$cmd = 'php '.SYSPATH.'ee/eecms.phar upgrade --microapp --step="'.$next_step.'"';

			if (strpos($next_step, 'updateDatabase') === FALSE)
			{
				$cmd .= ' --no-bootstrap';
			}

			system($cmd);
		}
	}
}

// EOF
