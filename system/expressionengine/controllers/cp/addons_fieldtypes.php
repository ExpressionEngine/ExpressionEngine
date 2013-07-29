<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Fieldtype Administration Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Addons_fieldtypes extends CP_Controller {


	/**
	 * Fieldtype Listing
	 *
	 * @access	public
	 */
	function index()
	{
		if ( ! $this->cp->allowed_group('can_access_addons', 'can_access_fieldtypes'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('api');
		$this->load->library('table');
		$this->api->instantiate('channel_fields');

		$this->view->cp_page_title = lang('addons_fieldtypes');

		$this->jquery->tablesorter('.mainTable', '{
        	textExtraction: "complex",
			widgets: ["zebra"]
		}');

		$fieldtypes = $this->api_channel_fields->fetch_all_fieldtypes();
		$installed_fts = array();

		// Get installed field types
		$this->load->library('addons');
		$installed_fts = $this->addons->get_installed('fieldtypes');

		foreach($installed_fts as $ft_name => $data)
		{
			$installed_fts[$ft_name] = $data['has_global_settings'];
		}

		$vars['table_headings'] = array(
										lang('fieldtype_name'),
										lang('version'),
										lang('status'),
										lang('action')
										);

		$vars['fieldtypes'] = array();
		$names = array();
		$data = array();
		$ftcount = 1;

		foreach ($fieldtypes as $fieldtype => $ft_info)
		{
			if ($fieldtype == 'hidden')
			{
				continue;
			}

			// Name and Version
			$name = $ft_info['name'];
			$names[$ftcount] = strtolower($name);
			$version = $ft_info['version'];

			// Installed
			$installed = (isset($installed_fts[$fieldtype]));

			if ($installed && $installed_fts[$fieldtype] == 'y')
			{
				$name = '<a href="'.BASE.AMP.'C=addons_fieldtypes'.AMP.'M=global_settings'.AMP.'ft='.strtolower($fieldtype).'"><strong>'.$name.'</strong></a>';
			}

			// Show installation status
			$status = $installed ? 'installed' : 'not_installed';
			$in_status = str_replace(" ", "&nbsp;", lang($status));
			$show_status = $installed ? '<span class="go_notice">'.$in_status.'</span>' : '<span class="notice">'.$in_status.'</span>';


			// Proper link to install or uninstall
			$show_action = $installed ? 'uninstall' : 'install';
			$show_action = '<a class="less_important_link" href="'.BASE.AMP.'C=addons_fieldtypes'.AMP.'M='.$show_action.AMP.'ft='.$fieldtype.'" title="'.lang($show_action).'">'.lang($show_action).'</a>';

			// Add to the view array
			$data[$ftcount] = array(
				$name,
				$version,
				$show_status,
				$show_action
			);

			$ftcount++;
		}

		// Let's order by name just in case
		asort($names);

		$id = 0;
		foreach ($names as $k => $v)
		{
			$vars['fieldtypes'][$id] = $data[$k];
			$id++;
		}

		$this->cp->set_breadcrumb(BASE.AMP.'C=addons', lang('addons'));

		$this->cp->render('addons/fieldtypes', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Install a Fieldtype
	 *
	 * @access	public
	 */
	function install()
	{
		if ( ! $this->cp->allowed_group('can_access_addons', 'can_access_fieldtypes'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! $ft = $this->input->get('ft'))
		{
			show_error(lang('unauthorized_access'));
		}

		$ft = $this->security->sanitize_filename(strtolower($ft));

		$this->load->library('addons/addons_installer');

		if ($this->addons_installer->install($ft, 'fieldtype'))
		{
			$cp_message = 'Fieldtype installed: '.$ft;

			$this->session->set_flashdata('message_success', $cp_message);
			$this->functions->redirect(BASE.AMP.'C=addons_fieldtypes');
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Uninstall a Fieldtype
	 *
	 * @access	public
	 */
	function uninstall()
	{
		if ( ! $this->cp->allowed_group('can_access_addons', 'can_access_fieldtypes'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! $ft = $this->input->get('ft'))
		{
			show_error(lang('unauthorized_access'));
		}

		$ft = $this->security->sanitize_filename(strtolower($ft));

		if ($this->input->post('doit') == 'y')
		{
			$this->load->library('addons/addons_installer');

			if ($this->addons_installer->uninstall($ft, 'fieldtype'))
			{
				$cp_message = 'Fieldtype uninstalled: '.$ft;

				$this->session->set_flashdata('message_success', $cp_message);
				$this->functions->redirect(BASE.AMP.'C=addons_fieldtypes');
			}
		}

		$this->view->cp_page_title = lang('delete_fieldtype');

		return $this->cp->render('addons/fieldtype_delete_confirm', array('form_action' => 'C=addons_fieldtypes'.AMP.'M=uninstall'.AMP.'ft='.$ft));
	}

	// --------------------------------------------------------------------

	/**
	 * Fieldtype Settings Page
	 *
	 * @access	public
	 */
	function global_settings()
	{
		if ( ! $this->cp->allowed_group('can_access_addons', 'can_access_fieldtypes'))
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! $ft = $this->input->get('ft'))
		{
			show_error(lang('unauthorized_access'));
		}

		$this->load->library('api');
		$this->load->library('addons');

		$this->api->instantiate('channel_fields');

		$installed = $this->addons->get_installed('fieldtypes');

		if ( ! isset($installed[$ft]) OR ! $this->api_channel_fields->include_handler($ft))
		{
			show_error(lang('unauthorized_access'));
		}

		// Grab existing settings if we have any
		$settings = array();

		if (isset($installed[$ft]['settings']) && $installed[$ft]['settings'])
		{
			$settings = unserialize(base64_decode($installed[$ft]['settings']));
		}

		// Instantiate class
		$FT = $this->api_channel_fields->setup_handler($ft, TRUE);

		// Update if version changed
		$version = $installed[$ft]['version'];

		if ($FT->info['version'] > $version && method_exists($FT, 'update') && $FT->update($version) !== FALSE)
		{
			if ($this->api_channel_fields->apply('update', array($version)) !== FALSE)
			{
				$this->db->update('fieldtypes', array('version' => $FT->info['version']), array('name' => $ft));
			}
		}

		$FT->settings = $settings;

		// Saving!
		if (count($_POST))
		{
			$settings = $this->api_channel_fields->apply('save_global_settings');
			$settings = base64_encode(serialize($settings));
			$this->db->update('fieldtypes', array('settings' => $settings), array('name' => $ft));

			$this->session->set_flashdata('message_success', lang('global_settings_saved'));
			$this->functions->redirect(BASE.AMP.'C=addons_fieldtypes');
		}

		$vars = array(
			'_ft_settings_body'	=> $this->api_channel_fields->apply('display_global_settings'),
			'_ft_name'			=> $ft
		);
		$this->view->cp_page_title = $FT->info['name'];
		$this->cp->render('addons/fieldtype_global_settings', $vars);
	}
}

// END Addons_fieldtypes class

/* End of file addons_fieldtypes.php */
/* Location: ./system/expressionengine/controllers/cp/addons_fieldtypes.php */
