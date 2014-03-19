<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Updater {


	function Updater()
	{
		$this->EE =& get_instance();

		// Grab the config file
		if ( ! @include(ee()->config->config_path))
		{
			show_error('Your config'.EXT.' file is unreadable. Please make sure the file exists and that the file permissions to 666 on the following file: expressionengine/config/config.php');
		}

		if (isset($conf))
		{
			$config = $conf;
		}

		// Does the config array exist?
		if ( ! isset($config) OR ! is_array($config))
		{
			show_error('Your config'.EXT.' file does not appear to contain any data.');
		}

		$this->config =& $config;
	}

	function do_update()
	{
		// Safety.  Prevents a problem if the
		// version indicator was not updated
		if (isset($this->config['captcha_require_members']))
		{
			return TRUE;
		}

		// New Table Keys

		$Q[] = "ALTER TABLE exp_comments ADD INDEX(status)";
		$Q[] = "ALTER TABLE exp_weblogs ADD INDEX(is_user_blog)";
		$Q[] = "ALTER TABLE exp_weblog_data ADD INDEX(weblog_id)";
		$Q[] = "ALTER TABLE exp_weblog_titles ADD INDEX(status)";
		$Q[] = "ALTER TABLE exp_weblog_titles ADD INDEX(url_title)";
		$Q[] = "ALTER TABLE exp_weblog_titles ADD INDEX(entry_date)";
		$Q[] = "ALTER TABLE exp_weblog_titles ADD INDEX(expiration_date)";

		// New table alterations

		$Q[] = "ALTER TABLE exp_weblog_fields CHANGE COLUMN field_maxl field_maxl smallint(3) NOT NULL";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN comment_expiration int(4) unsigned NOT NULL default '0'";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN search_results_url varchar(80) NOT NULL";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN comment_url varchar(80) NOT NULL";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN tb_return_url varchar(80) NOT NULL";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN ping_return_url varchar(80) NOT NULL";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN show_url_title char(1) NOT NULL default 'y'";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN show_trackback_field char(1) NOT NULL default 'y'";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN show_ping_cluster char(1) NOT NULL default 'y'";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN show_options_cluster char(1) NOT NULL default 'y'";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN show_button_cluster char(1) NOT NULL default 'y'";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN show_author_menu char(1) NOT NULL default 'y'";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN show_status_menu char(1) NOT NULL default 'y'";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN show_categories_menu char(1) NOT NULL default 'y'";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN show_date_menu char(1) NOT NULL default 'y'";
		$Q[] = "ALTER TABLE exp_weblog_titles ADD COLUMN comment_expiration_date int(10) NOT NULL default '0'";
		$Q[] = "ALTER TABLE exp_members ADD COLUMN localization_is_site_default char(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_categories ADD COLUMN cat_description text NOT NULL";
		$Q[] = "ALTER TABLE exp_categories ADD COLUMN cat_order int(4) unsigned NOT NULL";
		$Q[] = "ALTER TABLE exp_templates ADD COLUMN save_template_file char(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_category_groups ADD COLUMN sort_order char(1) NOT NULL default 'a'";


		// Run the queries

		foreach ($Q as $sql)
		{
			ee()->db->query($sql);
		}


		/** -----------------------------------------
		/**  Update config file with new prefs
		/** -----------------------------------------*/

		$data = array(
						'captcha_require_members'	=> 'y',
						'require_ip_for_posting'	=> 'y',
						'new_posts_clear_caches'	=> 'y',
						'tmpl_display_mode'   		=>  'auto',
						'default_site_timezone'   	=>  '',
						'default_site_dst'   		=>  'n',
                   		'enable_js_calendar'		=>	'y',
						'save_tmpl_files'   		=>  'n',
						'tmpl_file_basepath'   		=>  '',
						'calendar_thumb_path'		=>	ee()->config->slash_item('site_url').'images/cp_images/calendar.gif',
						'cp_image_path'				=>	ee()->config->slash_item('site_url').'images/cp_images/',
						'redirect_submitted_links'	=>	'n',
						'site_404'					=>	'',
						'weblog_nomenclature'		=> 'weblog'
					);

		ee()->config->_append_config_1x($data);


		/** -----------------------------
		/**  Update categories
		/** -----------------------------*/

		$CO = new Category_Order;

		$CO->add_category_orders();


		/** -----------------------------
		/**  Update skin file
		/** -----------------------------*/

		update_skin();


		return TRUE;
	}
}
// END CLASS



class Category_Order {

	var $cat_array = array();

    /** ----------------------------------------
    /**  Constructor
    /** ----------------------------------------*/

    function Category_Order()
    {
    }
    /* END */


    /** --------------------------------
    /**  Fix Category Orders
    /** --------------------------------*/

    function add_category_orders()
    {
    	$query = ee()->db->query("SELECT group_id FROM exp_category_groups");

    	if ($query->num_rows() == 0)
    	{
    		return false;
    	}

    	/** --------------------------------
    	/**  Broken up by Category Group
    	/** --------------------------------*/
    	$update_array = array();

    	foreach ($query->result_array() as $row)
    	{
    		if ($data = $this->process_category_group($row['group_id']))
    		{
    			$update_array[$row['group_id']] = $data;
    		}
    	}

    	if (count($update_array) == 0)
    	{
    		return false;
    	}

    	/** --------------------------------
    	/**  Update Database with Orders
    	/** --------------------------------*/

    	foreach($update_array as $group_data)
    	{
    		foreach($group_data as $cat_id => $cat_data)
    		{
    			ee()->db->query("UPDATE exp_categories
    						SET cat_order = '{$cat_data['1']}'
    						WHERE cat_id = '{$cat_id}'");
    		}
    	}

    	return TRUE;
    }
    /* END */

    /** --------------------------------
    /**  Category Tree
    /** --------------------------------*/

    // This function and the next create a nested, hierarchical category tree

    function process_category_group($group_id)
    {
        $sql = "SELECT cat_name, cat_id, parent_id FROM exp_categories WHERE group_id ='$group_id' ORDER BY parent_id, cat_name";

        $query = ee()->db->query($sql);

        if ($query->num_rows() == 0)
        {
            return false;
        }

        foreach($query->result_array() as $row)
        {
            $this->cat_array[$row['cat_id']]  = array($row['parent_id'], '1', $row['cat_name']);
        }

    		$order = 0;

        foreach($this->cat_array as $key => $val)
        {
            if (0 == $val['0'])
            {
            	$order++;
            	$this->cat_array[$key]['1'] = $order;
				$this->process_subcategories($key);  // Sends parent_id
            }
        }

        return $this->cat_array;
    }
    /* END */



    /** --------------------------------
    /**  Process Subcategories
    /** --------------------------------*/

    function process_subcategories($parent_id)
    {
        $order = 0;

    	foreach($this->cat_array as $key => $val)
        {
            if ($parent_id == $val['0'])
            {
            	$order++;
            	$this->cat_array[$key]['1'] = $order;
				$this->process_subcategories($key);
			}
        }
    }
    /* END */

}
// END CLASS




	function update_skin()
	{
		$path = './member_skins/default'.EXT;

		if ( ! file_exists($path))
			return FALSE;

		if ( ! is_really_writable($path))
			return FALSE;

		include $path;

		$MS = new Member_skin;

		$class_methods = get_class_methods('Member_skin');

		$methods = array();

		foreach ($class_methods as $val)
		{
			if ($val == 'menu')
				$methods[$val] = menu();
			else
				$methods[$val] = stripslashes($MS->$val());
		}

		$methods['subscriptions_form'] 			= subscriptions_form();
		$methods['no_subscriptions_message']	= no_subscriptions_message();
		$methods['subscription_result_heading']	= subscription_result_heading();
		$methods['subscription_result_rows'] 	= subscription_result_rows();
		$methods['subscription_pagination'] 	= subscription_pagination();



		$str  = "<?php\n\n";
		$str .= '/*'."\n";
		$str .= '====================================================='."\n";
		$str .= ' ExpressionEngine - by EllisLab'."\n";
		$str .= '-----------------------------------------------------'."\n";
		$str .= ' http://ellislab.com/'."\n";
		$str .= '-----------------------------------------------------'."\n";
		$str .= ' Copyright (c) 2003 - 2014, EllisLab, Inc.'."\n";
		$str .= '====================================================='."\n";
		$str .= ' THIS IS COPYRIGHTED SOFTWARE'."\n";
		$str .= ' PLEASE READ THE LICENSE AGREEMENT'."\n";
		$str .= ' http://ellislab.com/expressionengine/user-guide/license.html'."\n";
		$str .= '====================================================='."\n";
		$str .= ' Purpose: Member Profile Skin Elements'."\n";
		$str .= '====================================================='."\n";
		$str .= '*/'."\n\n";
		$str .= "if ( ! defined('EXT')){\n\texit('Invalid file request');\n}\n\n";
		$str .= "class Member_skin {\n\n";

		foreach ($methods as $key => $val)
		{
			$str .= '//-------------------------------------'."\n";
			$str .= '//  '.$key."\n";
			$str .= '//-------------------------------------'."\n\n";

			$str .= 'function '.$key.'()'."\n{\nreturn <<< EOF\n";
			$str .= $val;
			$str .= "\nEOF;\n}\n// END\n\n\n\n\n";
		}

		$str .= "}\n";
		$str .= '// END CLASS'."\n";
		$str .= '?'.'>';


		if ( ! $fp = @fopen($path, FOPEN_WRITE_CREATE_DESTRUCTIVE))
		{
			return FALSE;
		}
			flock($fp, LOCK_EX);
			fwrite($fp, $str);
			flock($fp, LOCK_UN);
			fclose($fp);

		return TRUE;

	}




function menu()
{
return <<< EOF

<table border='0' cellspacing='0' cellpadding='0' style='width:100%'>
<tr>
<td class='outerBorder' style='width:24%' valign='top'>

<div class='tablePad'>


<div class='tableHeading'><h2>{lang:menu}</h2></div>

<div class='borderBot'><div class='profileHead'>{lang:personal_settings}</div></div>

<div class='profileMenuInner'>

<div class='menuItem'><a href='{path:profile}'>{lang:edit_profile}</a></div>

<div class='menuItem'><a href='{path:email}'>{lang:email_settings}</a></div>

<div class='menuItem'><a href='{path:username}'>{lang:username_and_password}</a></div>

<div class='menuItem'><a href='{path:localization}'>{lang:localization}</a></div>

</div>


<div class='borderTopBot'><div class='profileHead'>{lang:subscriptions}</div></div>

<div class='profileMenuInner'>

<div class='menuItem'><a href='{path:subscriptions}' >{lang:edit_subscriptions}</a></div>

</div>


<div class='borderTopBot'><div class='profileHead'>{lang:extras}</div></div>

<div class='profileMenuInner'>

<div class='menuItem'><a href='{path:notepad}' >{lang:notepad}</a></div>

</div>


</div>

</td>
<td style='width:1%'>&nbsp;</td>

EOF;
}
/* END */

//-------------------------------------
//  Subscriptions Page
//-------------------------------------

function subscriptions_form()
{
return <<< EOF

<script type="text/javascript">
<!--

function toggle(thebutton)
{
	if (thebutton.checked)
	{
	   val = true;
	}
	else
	{
	   val = false;
	}

	var len = document.target.elements.length;

	for (var i = 0; i < len; i++)
	{
		var button = document.target.elements[i];

		var name_array = button.name.split("[");

		if (name_array[0] == "toggle")
		{
			button.checked = val;
		}
	}

	document.target.toggleflag.checked = val;
}
//-->
</script>


<td class='outerBorder' style='width:76%' valign='top'>

<form method="post" action="{path:update_subscriptions}" name="target">

<div class='tableHeading'><h2>{lang:subscriptions}</h2></div>

<div class='tablePad'>

<table border='0' cellspacing='0' cellpadding='0' style='width:100%'>

{subscription_results}

</table>

</td>
</tr>
</table>

</form>

</div>

EOF;
}
/* END */




//-------------------------------------
//  No Subscriptions Message
//-------------------------------------

function no_subscriptions_message()
{
return <<< EOF

<tr><td class='tableCellOne'><div class='highlight'>{lang:no_subscriptions}</div></td></tr>

EOF;
}
/* END */




//-------------------------------------
//  Subscription Results Heading
//-------------------------------------

function subscription_result_heading()
{
return <<< EOF
<tr>
<td class='tableCellOne' width="56%"><b>{lang:title}</b></td>
<td class='tableCellOne' width="22%"><b>{lang:type}</b></td>
<td class='tableCellOne' width="22%"><b><input type="checkbox" name="toggleflag" value="" onclick="toggle(this);" />&nbsp;{lang:unsubscribe}</b></td>
</tr>
EOF;
}
/* END */




//-------------------------------------
//  Subscription Result Rows
//-------------------------------------

function subscription_result_rows()
{
return <<< EOF
<tr>
<td class='{class}'><a href="{path}">{title}</a></td>
<td class='{class}'>{type}</td>
<td class='{class}'><input type="checkbox" name="toggle[]" value="{id}" /></td>
</tr>
EOF;
}
/* END */




//-------------------------------------
//  Subscription Pagination
//-------------------------------------

function subscription_pagination()
{
return <<< EOF
<tr>
<td class='{class}'>{pagination}</td>
<td class='{class}'>&nbsp;</td>
<td class='{class}'><div class='smallPad'><input type="submit" name="submit" value="{lang:unsubscribe}" /></div></td>
</tr>
EOF;
}
/* END */




/* End of file ud_110.php */
/* Location: ./system/expressionengine/installer/updates/ud_110.php */