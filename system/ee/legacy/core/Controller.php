<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\Core\LoaderFacade;
use EllisLab\ExpressionEngine\Service\Validation\Result as ValidationResult;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * CodeIgniter Application Controller Class
 *
 * This class object is the super class that every library in
 * CodeIgniter will be assigned to.
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/user_guide/general/controllers.html
 */
class Controller {

	private static $facade;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		log_message('debug', "Controller Class Initialized");
		ee()->set('__legacy_controller', $this);
	}

	/**
	 * Some controllers still use $this-> instead of ee()->
	 */
	public function __get($name)
	{
		$facade = self::$facade;
		return $facade->get($name);
	}

	/**
	 * Set the legacy facade
	 */
	public static function _setFacade($facade)
	{
		if (isset(self::$facade) && get_called_class() != 'EllisLab\ExpressionEngine\Controller\Error\FileNotFound')
		{
			throw new \Exception('Cannot change the facade after boot');
		}

		self::$facade = $facade;
	}
}

class_alias('Controller', 'CI_Controller');

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Controller
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class EE_Controller extends CI_Controller {

	function __construct()
	{
		parent::__construct();

		ee()->load->library('core');
		ee()->core->bootstrap();
		ee()->core->run_ee();
	}
}

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Control Panel Controller
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class CP_Controller extends EE_Controller {

	function __construct()
	{
		parent::__construct();
		ee()->core->run_cp();
	}

	/**
	 * Takes a model validation result object and checks for errors on the
	 * posted 'ee_fv_field' and returns an error message, or success message
	 * but only if the request was an AJAX request.
	 *
	 * @param EllisLab\ExpressionEngine\Service\Validation\Result $result A model validation result
	 * @return array|NULL NULL if the request was not via AJAX, otherwise an
	 *   an array with an error message or a success notification.
	 */
	protected function ajaxValidation(ValidationResult $result)
	{
		if (ee()->input->is_ajax_request())
		{
			$field = ee()->input->post('ee_fv_field');

			// Get the parent field name
			$field = preg_replace('/\[.+?\]/', '', $field);

			if ($result->hasErrors($field))
			{
				return array('error' => $result->renderError($field));
			}
			else
			{
				return array('success');
			}
		}

		return NULL;
	}

}

// EOF
