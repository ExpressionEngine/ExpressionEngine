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

		$this->start();
	}

	public function start()
	{
		try
		{
			ee('Updater/Runner')->run();
		}
		catch (\Exception $e)
		{
			// TODO: Log to file
			echo $e->getMessage();
			exit;
		}

		// TODO: Abstract into helper method to run other eecms CLI commands?
		system('php eecms.phar upgrade --microapp --no-bootstrap');
	}

	public function updaterMicroapp($step = NULL)
	{
		$runner = new EllisLab\ExpressionEngine\Updater\Service\Updater\Runner();

		if ( ! $step)
		{
			$step = $runner->getFirstStep();
		}

		try
		{
			$runner->runStep($step);
		}
		catch (\Exception $e)
		{
			// TODO: try to get logger, log message
			echo $e->getMessage();
		}

		if (($next_step = $runner->getNextStep()) !== FALSE)
		{
			$cmd = 'php eecms.phar upgrade --microapp --step="'.$next_step.'"';

			if (strpos($next_step, 'updateDatabase') === FALSE)
			{
				$cmd .= ' --no-bootstrap';
			}

			system($cmd);
		}
	}
}

// EOF
