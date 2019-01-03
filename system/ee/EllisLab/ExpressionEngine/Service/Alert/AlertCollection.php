<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Service\Alert;

use \EE_Session;
use \EE_Lang;
use EllisLab\ExpressionEngine\Service\Alert\Alert;
use EllisLab\ExpressionEngine\Service\View\View;

/**
 * Alert Service Collection
 */
class AlertCollection {

	/**
	 * @var array $alerts An associative array of alerts by type
	 */
	private $alerts = array();

	/**
	 * @var EE_Session $session A session object for deferring and recalling
	 *   alerts
	 */
	private $session;

	/**
	 * @var View $view A view object for rendering Alerts
	 */
	private $view;

	/**
	 * @var EE_Lang $lang A EE_Lang object for loading language
	 */
	private $lang;

	/**
	 * Constructor: prepares the alerts data structure and loads any alerts from
	 * session data.
	 *
	 * @param EE_Session $session A session object (for deferring and recall)
	 * @param View $view A view object (for rendering Alerts)
	 * @param EE_Lang $lang A EE_Lang object for loading language
	 * @return void
	 */
	public function __construct(EE_Session $session, View $view, EE_Lang $lang)
	{
		$this->alerts = [
			'inline'   => [],
			'banner'   => [],
			'alert'   => []
		];
		$this->session = $session;
		$this->view = $view;
		$this->lang = $lang;

		$this->recallFromSession();
	}

	/**
	 * Restores the alerts array from the session data
	 * @return void
	 */
	private function recallFromSession()
	{
		foreach ($this->session->flashdata as $key => $value)
		{
			if (strpos($key, 'alert:') === 0)
			{
				list($keyword, $type, $name) = explode(':', $key, 3);

				$alert = $this->make($name, $type);
				$alert->title = $value['title'];
				$alert->body = $value['body'];

				switch ($value['severity'])
				{
					case 'error':
						$alert->asIssue();
						break;

					case 'success':
						$alert->asSuccess();
						break;

					case 'tip':
						$alert->asTip();
						break;

					case 'important':
						$alert->asWarning();
						break;
				}

				if ($value['can_close'])
				{
					$alert->canClose();
				}
				else
				{
					$alert->cannotClose();
				}

				if (isset($value['sub_alert']))
				{
					$sub_alert = $this->make($name, $value['sub_alert']['type']);
					$sub_alert->title = $value['sub_alert']['title'];
					$sub_alert->body = $value['sub_alert']['body'];
					switch ($value['sub_alert']['severity'])
					{
						case 'error':
							$sub_alert->asIssue();
							break;

						case 'success':
							$sub_alert->asSuccess();
							break;

						case 'tip':
							$sub_alert->asTip();
							break;

						case 'important':
							$sub_alert->asWarning();
							break;
					}
					$alert->setSubAlert($sub_alert);
				}

				$this->alerts[$type][$name] = $alert;
			}
		}
	}

	/**
	 * Defers rendering and displaying of the alert until the next CP request.
	 *
	 * @param Alert $alert The alert to defer
	 * @return void
	 */
	public function defer(Alert $alert)
	{
		$data = array(
			'title' => $alert->title,
			'body' => $alert->body,
			'severity' => $alert->severity,
			'can_close' => $alert->has_close_button
		);

		if ( ! is_null($alert->sub_alert))
		{
			$data['sub_alert'] = array(
				'type' => $alert->sub_alert->type,
				'title' => $alert->sub_alert->title,
				'body' => $alert->sub_alert->body,
				'severity' => $alert->sub_alert->severity
			);
		}

		$this->session->set_flashdata('alert:' . $alert->type . ':' . $alert->name, $data);
	}

	/**
	 * Saves the alert it may be renedered and displayed this request via the
	 * various get methods.
	 *
	 * @param Alert $alert The alert to defer
	 * @return void
	 */
	public function save(Alert $alert)
	{
		$this->alerts[$alert->type][$alert->name] = $alert;
	}

	/**
	 * Gets the rendered value of a named alert of a certain type.
	 *
	 * @param string $name The name of the alert
	 * @param string $type The type of the alert (inline, banner, or alert)
	 * @return string The rendered HTML of the alert
	 */
	public function get($name, $type = 'inline')
	{
		if (isset($this->alerts[$type][$name]))
		{
			return $this->alerts[$type][$name]->render();
		}

		return '';
	}

	/**
	 * Gets the rendered value of all banner alerts.
	 *
	 * @param string $name The name of the alert
	 * @return string The rendered HTML of the alert
	 */
	public function getAllBanners()
	{
		$return = '';
		foreach ($this->alerts['banner'] as $alert)
		{
			$return .= $alert->render();
		}
		return $return;
	}

	/**
	 * Gets the rendered value of all inline alerts.
	 *
	 * @param string $name The name of the alert
	 * @return string The rendered HTML of the alert
	 */
	public function getAllInlines()
	{
		$return = '';
		foreach ($this->alerts['inline'] as $alert)
		{
			$return .= $alert->render();
		}
		return $return;
	}

	/**
	 * Gets the rendered value of the standard alert.
	 *
	 * @param string $name The name of the alert
	 * @return string The rendered HTML of the alert
	 */
	public function getStandard()
	{
		$return = '';
		foreach ($this->alerts['alert'] as $alert)
		{
			$return .= $alert->render();
		}
		return $return;
	}

	/**
	 * Gets the rendered value of the floating alert.
	 * @deprecated use getStandard() instead.
	 *
	 * @return string The rendered HTML of the alert
	 */
	public function getAllAlerts()
	{
		ee()->logger->deprecated('4.2.1', 'getStandard()');
		return $this->getStandard();
	}

	/**
	 * Makes a new named alert of the specified type.
	 *
	 * @param string $name The name of the alert
	 * @param string $type The type of the alert (inline, banner, or alert)
	 * @return EllisLab\ExpressionEngine\Service\Alert\Alert An Alert
	 */
	public function make($name = '', $type = 'alert')
	{
		return new Alert($type, $name, $this, $this->view, $this->lang);
	}

	/**
	 * Makes a new named inline alert.
	 *
	 * @param string $name The name of the alert
	 * @return EllisLab\ExpressionEngine\Service\Alert\Alert An Alert
	 */
	public function makeInline($name = '')
	{
		return $this->make($name, 'inline');
	}

	/**
	 * Makes a new named banner alert.
	 *
	 * @param string $name The name of the alert
	 * @return EllisLab\ExpressionEngine\Service\Alert\Alert An Alert
	 */
	public function makeBanner($name = '')
	{
		return $this->make($name, 'banner');
	}

	/**
	 * Makes a new named standard alert.
	 *
	 * @param string $name The name of the alert
	 * @return EllisLab\ExpressionEngine\Service\Alert\Alert An Alert
	 */
	public function makeStandard($name = '')
	{
		return $this->make($name, 'alert');
	}

	/**
	 * Makes a new named floating alert.
	 * @deprecated use makeStandard() instead.
	 *
	 * @param string $name The name of the alert
	 * @return EllisLab\ExpressionEngine\Service\Alert\Alert An Alert
	 */
	public function makeAlert($name = '')
	{
		ee()->logger->deprecated('4.2.1', 'makeStandard()');
		return $this->makeStandard($name);
	}

	public function makeDeprecationNotice()
	{
		$alert = $this->makeStandard('deprecation-notice')
			->asWarning();

		if ($this->session->userdata('group_id') == 1 && ee()->config->item('enable_devlog_alerts') == 'y')
		{
			$count = ee('Model')->get('DeveloperLog')
				->filter('viewed', 'n')
				->count();

			if ($count)
			{
				$lang_key = ($count == 1) ? 'developer_one_log' : 'developer_logs';

				$this->lang->loadfile('admin');
				$url = ee('CP/URL', 'logs/developer');
				$alert->withTitle(lang('deprecation_notice'))
					->addToBody(sprintf(lang($lang_key), $count, $url));
			}
		}

		return $alert;
	}
}

// EOF
