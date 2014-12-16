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
	public $description;

	protected $list = array();
	protected $name;
	protected $severity;
	protected $sub_alert;
	protected $type;

	private $collection;
	private $view;

	public function __construct($type = 'inline', $name = '', AlertCollection $collection, View $view)
	{
		$this->type = $type;
		$this->name = $name;
		$this->collection = $collection;
		$this->view = $view;
		return $this;
	}

	public function __set($name, $value)
	{
		$method = 'set__' . $name;
		if (method_exists($this, $method))
		{
			$this->$method($value);
		}
		else
		{
			throw new InvalidArgumentException("No such property: '{$name}' on ".get_called_class());
		}
	}

	public function __get($name)
	{
		$method = 'get__' . $name;
		if (method_exists($this, $method))
		{
			return $this->$method();
		}

		if (property_exists($this, $name))
		{
			return $this->$name;
		}

		throw new InvalidArgumentException("No such property: '{$name}' on ".get_called_class());
	}

	public function set__list(array $list)
	{
		$this->list = $list;
	}

	public function set__severity($severity)
	{
		$severity = strtolower($severity);
		if ( ! in_array($severity, array('warn', 'issue', 'success')))
		{
			throw new InvalidArgumentException("severity must be one of, 'warn', 'issue', or 'success', got '{$severity}' instead");
		}

		$this->severity = $severity;
	}

	public function asSuccess()
	{
		$this->severity = 'success';
		return $this;
	}

	public function asIssue()
	{
		$this->severity = 'issue';
		return $this;
	}

	public function asWarning()
	{
		$this->severity = 'warn';
		return $this;
	}

	public function set__sub_alert(Alert $alert)
	{
		$this->sub_alert = $alert;
	}

	public function render()
	{
		// @TODO post-merge of new-modals yank this line!
		return ee()->load->ee_view('_shared/alert', array('alert' => $this), TRUE);
		return $this->view->render(array('alert' => $this));
	}

	public function defer()
	{
		$this->collection->defer($this);
	}
}
// EOF