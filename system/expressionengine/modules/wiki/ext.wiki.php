<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Wiki_ext {

	var $name = 'Wiki';
	var $version = '2.3';
	var $settings_exist = 'n';
	var $docs_url = 'http://ellislab.com/expressionengine/user-guide/modules/wiki/index.html';
	var $required_by = array('module');

	private $EE;

	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Handle hook call
	 */
	function files_after_delete($deleted_rows)
	{
		$names = array();

		foreach ($deleted_rows as $row)
		{
			$names[] = $row->file_name;
		}

		ee()->db->where_in('file_name', $names);
		ee()->db->delete('wiki_uploads');

		// Clear wiki cache
		ee()->functions->clear_caching('db');
	}


	// --------------------------------------------------------------------

	/**
	 * Activate Extension
	 */
	function activate_extension()
	{
		return TRUE;
		//show_error('This extension is automatically installed with the wiki module');
	}

	// --------------------------------------------------------------------

	/**
	 * Update Extension
	 */
	function update_extension($current = FALSE)
	{
		return TRUE;
		//show_error('This extension is automatically updated with the wiki module');
	}

	// --------------------------------------------------------------------

	/**
	 * Disable Extension
	 */
	function disable_extension()
	{
		show_error('This extension is automatically deleted with the wiki module');
	}

		// --------------------------------------------------------------------

	/**
	 * Disable Extension
	 */
	function uninstall_extension()
	{
		return TRUE;
		//show_error('This extension is automatically deleted with the wiki module');
	}

}

/* End of file ext.wiki.php */
/* Location: ./system/expressionengine/extensions/ext.wiki.php */