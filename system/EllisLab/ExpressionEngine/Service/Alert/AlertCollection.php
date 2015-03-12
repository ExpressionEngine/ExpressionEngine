<?php
namespace EllisLab\ExpressionEngine\Service\Alert;

use \EE_Session;
use EllisLab\ExpressionEngine\Service\Alert\Alert;
use EllisLab\ExpressionEngine\Service\View\View;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Alert Collection Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class AlertCollection {

	private $alerts = array();
	private $session;
	private $view;

	public function __construct(EE_Session $session, View $view)
	{
		$this->alerts = array(
			'inline' => array(),
			'banner' => array(),
			'standard' => array()
		);
		$this->session = $session;
		$this->view = $view;

		$this->recallFromSession();
	}

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

				if ($value['can_close'])
				{
					$alert->canClose();
				}
				else
				{
					$alert->cannotClose();
				}

				switch ($value['severity'])
				{
					case 'issue':
						$alert->asIssue();
						break;

					case 'success':
						$alert->asSuccess();
						break;

					case 'warn':
						$alert->asWarning();
						break;
				}

				if (isset($value['sub_alert']))
				{
					$sub_alert = $this->make($name, $value['sub_alert']['type']);
					$sub_alert->title = $value['sub_alert']['title'];
					$sub_alert->body = $value['sub_alert']['body'];
					switch ($value['sub_alert']['severity'])
					{
						case 'issue':
							$sub_alert->asIssue();
							break;

						case 'success':
							$sub_alert->asSuccess();
							break;

						case 'warn':
							$sub_alert->asWarning();
							break;
					}
					$alert->setSubAlert($sub_alert);
				}

				$this->alerts[$type][$name] = $alert;
			}
		}
	}

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

	public function save(Alert $alert)
	{
		$this->alerts[$alert->type][$alert->name] = $alert;
	}

	public function get($name, $type = 'inline')
	{
		if (isset($this->alerts[$type][$name]))
		{
			return $this->alerts[$type][$name]->render();
		}

		return '';
	}

	public function getAllBanners()
	{
		$return = '';
		foreach ($this->alerts['banner'] as $alert)
		{
			$return .= $alert->render();
		}
		return $return;
	}

	public function getAllInlines()
	{
		$return = '';
		foreach ($this->alerts['inline'] as $alert)
		{
			$return .= $alert->render();
		}
		return $return;
	}

	public function getStandard()
	{
		$return = '';
		foreach ($this->alerts['standard'] as $alert)
		{
			$return .= $alert->render();
		}
		return $return;
	}

	public function make($name = '', $type = 'standard')
	{
		return new Alert($type, $name, $this, $this->view);
	}

	public function makeInline($name = '')
	{
		return $this->make($name, 'inline');
	}

	public function makeBanner($name = '')
	{
		return $this->make($name, 'banner');
	}

	public function makeStandard($name = '')
	{
		return $this->make($name, 'standard');
	}

}
// EOF