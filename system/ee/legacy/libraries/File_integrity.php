<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * ExpressionEngine Core File Integrity Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class File_integrity {

	var $emailed = array();
	var $checksums = array();

	/**
	 * Check all bootstrap files
	 *
	 * @access	public
	 */
	function check_bootstrap_files($return_site_id = FALSE)
	{
		ee()->load->model('site_model');
		$sites = ee()->site_model->get_site();
		$sites = $sites->result_array();

		$bootstraps = array();

		// Retrieve all of the bootstrap files
		foreach($sites as $site)
		{
			if ( ! isset($site['site_bootstrap_checksums']))
			{
				continue;
			}

			$data = base64_decode($site['site_bootstrap_checksums']);

			if ( ! is_string($data) OR substr($data, 0, 2) != 'a:')
			{
				continue;
			}

			$data = unserialize($data);

			if ( ! isset($data['emailed']) OR ! is_array($data['emailed']))
			{
				$data['emailed'] = array();
			}

			$bootstraps[$site['site_id']] = $data;
		}


		$altered = array();
		$removed = array();
		$update_db = array();

		// Check them all
		foreach($bootstraps as $site_id => $checksums)
		{
			foreach($checksums as $path => $checksum)
			{
				if ($path == 'emailed')
				{
					continue;
				}

				if ( ! file_exists($path))
				{
					$removed[$site_id][] = $path;
				}
				else
				{
					$this->checksums[$site_id][$path] = $checksum;

					$current = md5_file($path);

					if ($current != $checksum)
					{
						if ($return_site_id)
						{
							$altered[$site_id][] = $path;
						}
						else
						{
							$altered[] = $path;
						}
					}
					elseif (($email_key = array_search($path, $checksums['emailed'])) !== FALSE)
					{
						// they were emailed about it and restored it without
						// hitting the 'accept changes' link on the homepage

						unset($bootstraps[$site_id]['emailed'][$email_key]);
						$update_db[] = $site_id;
					}
				}
			}

			$this->emailed = array_unique(array_merge($this->emailed, $bootstraps[$site_id]['emailed']));
		}

		// Remove obsolete files from the db
		foreach($removed as $site_id => $paths)
		{
			foreach($paths as $path)
			{
				if (isset($bootstraps[$site_id][$path]))
				{
					unset($bootstraps[$site_id][$path]);
					$update_db[] = $site_id;
				}
			}
		}

		// Update the db if we detected changes
		foreach(array_unique($update_db) as $site_id)
		{
			$this->_update_config($bootstraps[$site_id], $site_id);
		}

		// Any changes? report them
		if (count($altered))
		{
			return $altered;
		}

		return FALSE;
	}

	// --------------------------------------------------------------------

	/**
	 * Add a bootstrap file we didn't know about, or explicitly update
	 * a checksum (when accepting a change).
	 *
	 * @access	public
	 */
	function send_site_admin_warning($altered)
	{
		$affected_paths = array_diff($altered, $this->emailed);

		if (count($affected_paths))
		{
			ee()->load->library('notifications');
			ee()->notifications->send_checksum_notification($affected_paths);

			// add them to the existing emailed and update
			$affected_paths = array_unique(array_merge($this->emailed, $affected_paths));

			foreach($this->checksums as $site_id => $checksums)
			{
				$checksum_paths = array_keys($checksums);

				// did we send emails for this site id?
				$old_emailed = array_intersect($checksum_paths, $this->emailed);
				$new_emailed = array_intersect($checksum_paths, $affected_paths);

				if (count($new_emailed))
				{
					$checksums['emailed'] = array_unique(array_merge($old_emailed, $new_emailed));
					$this->_update_config($checksums, $site_id);
				}
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Add a bootstrap file we didn't know about, or explicitly update
	 * a checksum (when accepting a change).
	 *
	 * @access	public
	 */
	function create_bootstrap_checksum($path = '', $site_id = '')
	{
		$checksums	= ee()->config->item('site_bootstrap_checksums');
		$site_id	= ($site_id != '') ? $site_id : ee()->config->item('site_id');

		if (REQ == 'CP' && $path && file_exists($path))
		{
			$checksums				= $this->checksums[$site_id];		// should already have called check_bootstrap_files
			$checksums[$path]		= md5_file($path);
			$checksums['emailed']	= array();

			$this->_update_config($checksums, $site_id);
		}
		elseif (REQ != 'CP' && ! array_key_exists(FCPATH.SELF, $checksums))
		{
			$checksums[FCPATH.SELF] = md5_file(FCPATH.SELF);

			$this->_update_config($checksums, $site_id);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Update the bootstrap column in the db
	 *
	 * @access	private
	 */
	function _update_config($checksums, $site_id)
	{
		if ($site_id == ee()->config->item('site_id'))
		{
			ee()->config->config['site_bootstrap_checksums'] = $checksums;
		}

		ee()->db->query(ee()->db->update_string('exp_sites',
									  array('site_bootstrap_checksums' => base64_encode(serialize($checksums))),
									  "site_id = '".ee()->db->escape_str($site_id)."'"));
	}
}

// END File_integrity class

// EOF
