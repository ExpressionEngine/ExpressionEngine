<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2010, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Home Page Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Addons extends CI_Controller {

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		// Can't access addons? Can't see this page!
		if ( ! $this->cp->allowed_group('can_access_addons'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->lang->loadfile('addons');
		$this->load->model('addons_model');
	}
	
	// --------------------------------------------------------------------

	/**
	 * Index function
	 *
	 * @access	public
	 * @return	void
	 */	
	function index()
	{
		if ( ! $this->cp->allowed_group('can_access_addons'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->cp->set_variable('cp_page_title', $this->lang->line('addons'));

		$this->load->vars(array('controller' => 'addons'));

		$this->javascript->compile();
		$this->load->view('_shared/overview');
	}
	
	// --------------------------------------------------------------------

	/**
	 * Package Editor
	 *
	 * Install and remove package components
	 * 
	 * @access	public
	 * @return	mixed
	 */	
	function package_settings()
	{
		if ( ! $this->cp->allowed_group('can_access_addons'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->library('addons');
		$this->load->library('table');
		$this->load->helper('form');
		
		$this->load->model('addons_model');
		$this->lang->loadfile('modules');
		
		$return = $this->input->get_post('return');
		$package = $this->input->get_post('package');
		
		if ( ! $package OR ! $this->addons->is_package($package))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->cp->set_variable('cp_page_title', $this->lang->line('package_settings'));
		
		$components = $this->addons->_packages[$package];
		
		if (isset($components['plugin']))
		{
			unset($components['plugin']);
		}
		
		if (count($_POST))
		{
			$install = array();
			$uninstall = array();
			
			foreach($components as $type => $info)
			{
				if ($new_state = $this->input->get_post('install_'.$type))
				{
					$installed_f = $type.'_installed';
					
					if (method_exists($this->addons_model, $installed_f))
					{
						$is_installed = $this->addons_model->$installed_f($package);
						
						if ($is_installed && ($new_state == 'uninstall'))
						{
							$uninstall[] = $type;
						}
						elseif ( ! $is_installed && ($new_state == 'install'))
						{
							$install[] = $type;
						}
					}
				}
			}

			$this->load->library('addons/addons_installer');
			
			$this->addons_installer->install($package, $install, FALSE);
			$this->addons_installer->uninstall($package, $uninstall, FALSE);
			$this->functions->redirect(BASE.AMP.'C='.$_GET['return']);
		}

		$vars = array();
		
		foreach($components as $type => $info)
		{
			$inst_func = $type.'_installed';
			$components[$type]['installed'] = $this->addons_model->$inst_func($package);
		}
		
		$vars['form_action'] = 'C=addons'.AMP.'M=package_settings'.AMP.'package='.$package.AMP.'return='.$return;
		$vars['package'] = ucfirst(str_replace('_', ' ', $package));
		$vars['components'] = $components;
		
		$this->javascript->compile();
		
		$this->load->view('addons/package_settings', $vars);
	}
}
// END CLASS

/* End of file addons.php */
/* Location: ./system/expressionengine/controllers/cp/addons.php */