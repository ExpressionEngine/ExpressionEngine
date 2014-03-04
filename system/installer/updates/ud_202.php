<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      EllisLab Dev Team
 * @copyright   Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license     http://ellislab.com/expressionengine/user-guide/license.html
 * @link        http://ellislab.com
 * @since       Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package     ExpressionEngine
 * @subpackage  Core
 * @category    Core
 * @author      EllisLab Dev Team
 * @link        http://ellislab.com
 */
class Updater {

	var $version_suffix = 'pb01';

    function Updater()
    {
        $this->EE =& get_instance();
    }

    function do_update()
    {

		// Modules now have a tab setting
		if (ee()->db->table_exists('relationships')
			AND ee()->db->field_exists('rel_type', 'relationships'))
		{
			ee()->db->set('rel_type', 'channel');
			ee()->db->where('rel_type', 'blog');
			ee()->db->update('relationships');
		}

		ee()->smartforge->drop_column('channels', 'show_url_title');
		ee()->smartforge->drop_column('channels', 'show_ping_cluster');
		ee()->smartforge->drop_column('channels', 'show_options_cluster');
		ee()->smartforge->drop_column('channels', 'show_forum_cluster');
		ee()->smartforge->drop_column('channels', 'show_show_all_cluster');
		ee()->smartforge->drop_column('channels', 'show_status_menu');
		ee()->smartforge->drop_column('channels', 'show_categories_menu');
		ee()->smartforge->drop_column('channels', 'show_date_menu');
		ee()->smartforge->drop_column('channels', 'show_pages_cluster');
		ee()->smartforge->drop_column('channels', 'show_author_menu');

		// Leftover trackback indication can go
		ee()->db->where('module_name', 'Trackback');
		ee()->db->delete('modules');

		// Email field size consistent with RFC2822 recommended header line limit of 78 (minus "from: ")
		ee()->smartforge->modify_column(
			'members',
			array(
				'email' => array(
					'name'			=> 'email',
					'type'			=> 'varchar',
					'constraint'	=> 72,
					'null'			=> FALSE
				),
			)
		);

		// If there is no action id, add it
		$values = array(
			'class'		=> 'channel',
			'method'	=> 'smiley_pop'
		);

		ee()->smartforge->insert_set('actions', $values, $values);


		$values	= array(
			'class'		=> 'channel',
			'method'	=> 'filemanager_endpoint'
		);

		ee()->smartforge->insert_set('actions', $values, $values);


		// If the action id is for the Weblog class, change it
		ee()->db->set('class', 'Channel');
		ee()->db->where('class', 'Weblog');
		ee()->db->update('actions');


		// If they have existing Pages, saved array needs to be updated to new format
        if (ee()->db->field_exists('site_pages', 'sites'))
		{
			ee()->db->select('site_id, site_pages, site_system_preferences');
        	ee()->db->where('site_pages !=', '');
        	$query = ee()->db->get('sites');

        	if ($query->num_rows() > 0)
        	{
				foreach ($query->result_array() as $row)
				{
					$system_prefs =  base64_decode($row['site_system_preferences']);
					$skip = FALSE;
					$encode_only = FALSE;

					// Note- to this point, pages may not have been encoded
					$old_pages = $this->array_stripslashes($row['site_pages']);

					if ( ! is_string($old_pages) OR substr($old_pages, 0, 2) != 'a:')
					{
						$skip = TRUE;

						// Try it base64 encoded
						$old_pages = base64_decode($row['site_pages']);
					}

					if ($skip == TRUE && (is_string($old_pages) && substr($old_pages, 0, 2) == 'a:'))
					{
						$skip = FALSE;
					}

					if ($skip)
					{
						continue;
					}
					else
					{
						$old_pages = unserialize($old_pages);

						if (isset($old_pages[$row['site_id']]['url']))
						{
							//  Site pages have already been updated, but may not be encoded
							$new_pages = $old_pages;
							$encode_only = TRUE;
						}
						else
						{
							$new_pages[$row['site_id']] = $old_pages;
						}
					}

					if ($encode_only == FALSE)
					{
						if ( ! is_string($system_prefs) OR substr($system_prefs, 0, 2) != 'a:')
						{
							$new_pages[$row['site_id']]['url'] = '';
						}
						else
						{
							$prefs = unserialize($system_prefs);

							$url = (isset($prefs['site_url'])) ? $prefs['site_url'].'/' : '/';
							$url .= (isset($prefs['site_index'])) ? $prefs['site_index'].'/' : '/';

							$new_pages[$row['site_id']]['url'] = preg_replace("#(^|[^:])//+#", "\\1/", $url);
						}
					}

					ee()->db->query("UPDATE exp_sites SET site_pages = '".base64_encode(serialize($new_pages))."' WHERE site_id = '".$row['site_id']."'");

					unset($new_pages);

				}
			}
		}

		ee()->smartforge->add_column(
			'password_lockout',
			array(
				'username' => array(
					'type'			=> 'varchar',
					'constraint'	=> 50,
					'null'			=> FALSE
				)
			),
			'user_agent'
		);


		// Drop enable image resize configuration option
		ee()->db->select('site_channel_preferences');
		$query = ee()->db->get('sites');

		foreach ($query->result() as $row)
		{
			$settings = unserialize(base64_decode($row->site_channel_preferences));

			if (isset($settings['enable_image_resizing']))
			{
				unset($settings['enable_image_resizing']);
			}

			ee()->db->set('site_channel_preferences', base64_encode(serialize($settings)));
			ee()->db->update('sites');
		}

		// Unlink files on the ee_version/ and ee_info accessory because they have
		// been being written with 644 permissions.  So people running on hosts that
		// have a single apache user on a server with specific FTP users in the apache
		// group will be unable to get rid of the file.  We'll use the apache user to
		// delete it here, for a fresh start.

		if (file_exists(APPPATH.'cache/expressionengine_info/version'))
		{
			@unlink(APPPATH.'cache/expressionengine_info/version');
		}

		if (file_exists(APPPATH.'cache/ee_version/current_version'))
		{
			@unlink(APPPATH.'cache/ee_version/current_version');
		}

		// Finished!
        return TRUE;
    }

    function array_stripslashes($vals)
     {
     	if (is_array($vals))
     	{
     		foreach ($vals as $key=>$val)
     		{
     			$vals[$key] = $this->array_stripslashes($val);
     		}
     	}
     	else
     	{
     		$vals = stripslashes($vals);
     	}

     	return $vals;
	}


}
/* END CLASS */

/* End of file ud_202.php */
/* Location: ./system/expressionengine/installer/updates/ud_202.php */