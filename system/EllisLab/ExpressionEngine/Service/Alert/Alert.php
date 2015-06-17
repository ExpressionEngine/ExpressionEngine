<?php
namespace EllisLab\ExpressionEngine\Service\Alert;

use Serializable;
use BadMethodCallException;
use InvalidArgumentException;
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
 * ExpressionEngine Alert Class
 *
 * @package		ExpressionEngine
 * @category	Service
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Alert {

	public $title;
	public $body = '';

	protected $has_close_button = FALSE;
	protected $name;
	protected $severity;
	protected $sub_alert;
	protected $type;

	private $collection;
	private $view;

	public function __construct($type = 'standard', $name = '', AlertCollection $collection, View $view)
	{
		$this->type = $type;
		$this->name = $name;
		$this->collection = $collection;
		$this->view = $view;
		return $this;
	}

	public function __get($name)
	{
		if (property_exists($this, $name))
		{
			return $this->$name;
		}

		throw new InvalidArgumentException("No such property: '{$name}' on ".get_called_class());
	}

	public function addToBody($item, $class = NULL)
	{
		if ($class)
		{
			$class = ' class="' . $class . '"';
		}

		if (is_array($item))
		{
			$this->body .= '<ul>';
			foreach ($item as $i)
			{
				$this->body .= '<li>' . $i . '</li>';
			}
			$this->body .= '</ul>';
		}
		else
		{
			$this->body .= '<p' . $class . '>' . $item . '</p>';
		}
		return $this;
	}

	public function addSeparator()
	{
		$this->body .= '<hr>';
		return $this;
	}

	public function asIssue()
	{
		$this->severity = 'issue';
		$this->cannotClose();
		return $this;
	}

	public function asSuccess()
	{
		$this->severity = 'success';
		$this->canClose();
		return $this;
	}

	public function asWarning()
	{
		$this->severity = 'warn';
		$this->canClose();
		return $this;
	}

	public function withTitle($title)
	{
		$this->title = $title;
		return $this;
	}

	public function canClose()
	{
		$this->has_close_button = TRUE;
		return $this;
	}

	public function cannotClose()
	{
		$this->has_close_button = FALSE;
		return $this;
	}

	public function setSubAlert(Alert $alert)
	{
		$this->sub_alert = $alert;
		return $this;
	}

	public function render()
	{
		return $this->view->ee_view('_shared/alert', array('alert' => $this), TRUE);
	}

	public function defer()
	{
		$this->collection->defer($this);
		return $this;
	}

	public function now()
	{
		$this->collection->save($this);
		return $this;
	}
}
// EOF