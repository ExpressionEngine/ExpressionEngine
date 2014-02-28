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
	}

	function do_update()
	{
		$query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_template_groups");

		$num = $query->row('count') + 1;

		ee()->db->query("insert into exp_template_groups(group_name, group_order) values ('search', '$num')");

		$id = ee()->db->insert_id();

		$Q[] = "insert into exp_templates(group_id, template_name, template_data) values ('$id', 'index', '".addslashes(search_index())."')";
		$Q[] = "insert into exp_templates(group_id, template_name, template_data) values ('$id', 'results', '".addslashes(search_results())."')";
		$Q[] = "insert into exp_templates(group_id, template_name, template_type, template_data) values ('$id', 'search_css', 'css', '".addslashes(search_css())."')";

		// Define the table changes
		$Q[] = "ALTER TABLE exp_member_groups ADD COLUMN can_search char(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_member_groups ADD COLUMN search_flood_control mediumint(5) unsigned NOT NULL";
		$Q[] = "ALTER TABLE exp_member_groups ADD COLUMN can_moderate_comments char(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN search_excerpt int(4) unsigned NOT NULL";
		$Q[] = "ALTER TABLE exp_weblogs ADD COLUMN comment_moderate char(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_comments ADD COLUMN status char(1) NOT NULL default 'o'";
		$Q[] = "ALTER TABLE exp_referrers ADD COLUMN ref_ip varchar(16) default '0' NOT NULL";
		$Q[] = "ALTER TABLE exp_referrers ADD COLUMN ref_date int(10) unsigned default '0' NOT NULL";
		$Q[] = "ALTER TABLE exp_referrers ADD COLUMN ref_agent varchar(100) NOT NULL";
		$Q[] = "ALTER TABLE exp_templates ADD COLUMN php_parse_location char(1) NOT NULL default 'o'";

		// Fix DB typos
		$Q[] = "ALTER TABLE exp_member_homepage CHANGE COLUMN memeber_search_form member_search_form char(1) NOT NULL default 'n'";
		$Q[] = "ALTER TABLE exp_member_homepage CHANGE COLUMN memeber_search_form_order member_search_form_order int(3) unsigned NOT NULL default '0'";
		$Q[] = "UPDATE exp_actions SET method = 'retrieve_password' WHERE class = 'Member' AND method = 'retreive_password'";

		// Add keys to some tables
		$Q[] = "ALTER TABLE exp_weblog_titles ADD INDEX(weblog_id)";
		$Q[] = "ALTER TABLE exp_weblog_titles ADD INDEX(author_id)";
		$Q[] = "ALTER TABLE exp_category_posts ADD INDEX(entry_id)";
		$Q[] = "ALTER TABLE exp_category_posts ADD INDEX(cat_id)";
		$Q[] = "ALTER TABLE exp_weblogs ADD INDEX(cat_group)";
		$Q[] = "ALTER TABLE exp_weblogs ADD INDEX(status_group)";
		$Q[] = "ALTER TABLE exp_weblogs ADD INDEX(field_group)";

		// Search module
		$Q[] = "INSERT INTO exp_modules (module_name, module_version, has_cp_backend) VALUES ('Search', '1.0', 'n')";
		$Q[] = "INSERT INTO exp_actions (class, method) VALUES ('Search', 'do_search')";

		// Email module
		$Q[] = "INSERT INTO exp_modules (module_name, module_version, has_cp_backend) VALUES ('Email', '1.0', 'n')";
		$Q[] = "INSERT INTO exp_actions (class, method) VALUES ('Email', 'send_email')";


		$Q[] = "CREATE TABLE IF NOT EXISTS exp_search (
		 search_id varchar(32) NOT NULL,
		 search_date int(10) NOT NULL,
		 member_id int(10) unsigned NOT NULL,
		 ip_address varchar(16) NOT NULL,
		 total_results int(6) NOT NULL,
		 per_page tinyint(3) unsigned NOT NULL,
		 query text NOT NULL,
		 result_page varchar(70) NOT NULL,
		 PRIMARY KEY `search_id` (`search_id`)
		)";

		$Q[] = "CREATE TABLE IF NOT EXISTS exp_blacklisted (
		 blacklisted_type VARCHAR(20) NOT NULL,
		 blacklisted_value TEXT NOT NULL
		)";

		$Q[] = "CREATE TABLE IF NOT EXISTS exp_email_tracker (
		email_id int(10) unsigned NOT NULL auto_increment,
		email_date int(10) unsigned default '0' NOT NULL,
		sender_ip varchar(16) NOT NULL,
		sender_email varchar(75) NOT NULL ,
		sender_username varchar(50) NOT NULL ,
		number_recipients int(4) unsigned default '1' NOT NULL,
		PRIMARY KEY `email_id` (`email_id`)
		)";

		// Run the queries

		foreach ($Q as $sql)
		{
			ee()->db->query($sql);
		}

		/** -----------------------------------------
		/**  Update Member Groups with search prefs
		/** -----------------------------------------*/

		$query = ee()->db->query("SELECT group_id FROM exp_member_groups ORDER BY group_id");

		foreach ($query->result_array() as $row)
		{
			$flood = ($row['group_id'] == 1) ? '0' : '30';

			ee()->db->query("UPDATE exp_member_groups SET can_search = 'y', search_flood_control = '$flood' WHERE group_id = '".$row['group_id']."'");

			$st = ($row['group_id'] == 1) ? 'y' : 'n';

			ee()->db->query("UPDATE exp_member_groups SET can_moderate_comments = '$st' WHERE group_id = '".$row['group_id']."'");
		}


		/** -----------------------------------------
		/**  Fix pm member import problem
		/** -----------------------------------------*/

        // Do we have custom fields?

        $query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_member_data");

        $md_exists = ($query->row('count') > 0) ? TRUE : FALSE;

		// We need to run through the member table and add two fields if they are missing

		$query = ee()->db->query("SELECT member_id FROM exp_members");

		foreach ($query->result_array() as $row)
		{
			$member_id = $row['member_id'];

			$res = ee()->db->query("SELECT member_id FROM exp_member_homepage WHERE member_id = '$member_id'");

			if ($res->num_rows() == 0)
				ee()->db->query("INSERT INTO exp_member_homepage (member_id) VALUES ('$member_id')");

			if ($md_exists == TRUE)
			{
				$res = ee()->db->query("SELECT member_id FROM exp_member_data WHERE member_id = '$member_id'");

				if ($res->num_rows() == 0)
				{
					ee()->db->query("INSERT INTO exp_member_data (member_id) VALUES ('$member_id')");
				}
			}
		}


		/** -----------------------------------------
		/**  Update config file with new prefs
		/** -----------------------------------------*/

		$data = array(
						'word_separator'	=> '_',
						'license_number'	=> ''
					);

		ee()->config->_append_config_1x($data);

		return TRUE;
	}

}
// END CLASS



// -----------------------------------------
//  Search Templates
// -----------------------------------------

function search_index()
{
return <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{lang}" lang="{lang}">

<head>
<title>{lang:search}</title>

<meta http-equiv="content-type" content="text/html; charset={charset}" />

<link rel='stylesheet' type='text/css' media='all' href='{stylesheet=search/search_css}' />
<style type='text/css' media='screen'>@import "{stylesheet=search/search_css}";</style>

</head>
<body>

<div id="content">
<div class='header'><h1>{lang:search_engine}</h1></div>

<table class='breadcrumb' border='0' cellpadding='0' cellspacing='0' width='99%'>
<tr>
<td><span class="defaultBold">&nbsp; <a href="{homepage}">{site_name}</a>&nbsp;&#8250;&nbsp;&nbsp;{lang:search}</span></td>
</tr>
</table>

<div class='outerBorder'>
<div class='tablePad'>


{exp:search:advanced_form result_page="search/results" }


<table cellpadding='4' cellspacing='6' border='0' width='100%'>
<tr>
<td>

<fieldset class="fieldset">
<legend>{lang:search_by_keyword}</legend>

<input type="text" class="input" maxlength="100" size="40" name="keywords" style="width:100%;" />

<div class="default">
<select name="search_in">
<option value="titles" selected="selected">{lang:search_in_titles}</option>
<option value="entries" selected="selected">{lang:search_in_entries}</option>
<option value="everywhere" >{lang:search_everywhere}</option>
</select>

</div>

</fieldset>


</td><td>

<fieldset class="fieldset">
<legend>{lang:search_by_member_name}</legend>

<input type="text" class="input" maxlength="100" size="40" name="member_name" style="width:100%;" />
<div class="default"><input type="checkbox" class="checkbox" name="exact_match" value="y"  /> {lang:exact_name_match}</div>

</fieldset>

</td>
</tr>
</table>


<table cellpadding='4' cellspacing='6' border='0' width='100%'>
<tr>
<td valign="top" width="50%">


<table cellpadding='0' cellspacing='0' border='0'>
<tr>
<td valign="top">

<div class="defaultBold">{lang:weblogs}</div>

<select id="weblog_id[]" name='weblog_id[]' class='multiselect' size='12' multiple='multiple' onchange='changemenu(this.selectedIndex);'>
{weblog_names}
</select>

</td>
<td valign="top" width="16">&nbsp;</td>
<td valign="top">

<div class="defaultBold">{lang:categories}</div>

<select name='cat_id[]' size='12'  class='multiselect' multiple='multiple'>
<option value='all' selected="selected">{lang:any_category}</option>
</select>

</td>
</tr>
</table>


</td>
<td valign="top" width="50%">


<fieldset class="fieldset">
<legend>{lang:search_entries_from}</legend>

<select name="date" style="width:150px">
<option value="0" selected="selected">{lang:any_date}</option>
<option value="1" >{lang:today_and}</option>
<option value="7" >{lang:this_week_and}</option>
<option value="30" >{lang:one_month_ago_and}</option>
<option value="90" >{lang:three_months_ago_and}</option>
<option value="180" >{lang:six_months_ago_and}</option>
<option value="365" >{lang:one_year_ago_and}</option>
</select>

<div class="default">
<input type='radio' name='date_order' value='newer' class='radio' checked="checked" />&nbsp;{lang:newer}
<input type='radio' name='date_order' value='older' class='radio' />&nbsp;{lang:older}
</div>

</fieldset>

<div class="default"><br /></div>

<fieldset class="fieldset">
<legend>{lang:sort_results_by}</legend>

<select name="order_by">
<option value="date" >{lang:date}</option>
<option value="title" >{lang:title}</option>
<option value="most_comments" >{lang:most_comments}</option>
<option value="recent_comment" >{lang:recent_comment}</option>
</select>

<div class="default">
<input type='radio' name='sort_order' class="radio" value='desc' checked="checked" /> {lang:descending}
<input type='radio' name='sort_order' class="radio" value='asc' /> {lang:ascending}
</div>

</td>
</tr>
</table>


</td>
</tr>
</table>


<div class='searchSubmit'>

<input type='submit' value='{lang:search}' class='submit' />

</div>

{/exp:search:advanced_form}

<div class='copyright'><a href="http://ellislab.com/">Powered by ExpressionEngine</a></div>


</div>
</div>
</div>

</body>
</html>
EOF;
}
/* END */



function search_results()
{
return <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{lang}" lang="{lang}">

<head>
<title>{lang:search}</title>

<meta http-equiv="content-type" content="text/html; charset={charset}" />

<link rel='stylesheet' type='text/css' media='all' href='{stylesheet=search/search_css}' />
<style type='text/css' media='screen'>@import "{stylesheet=search/search_css}";</style>

</head>
<body>

<div id="content">
<div class='header'><h1>{lang:search_results}</h1></div>

<table class='breadcrumb' border='0' cellpadding='0' cellspacing='0' width='99%'>
<tr>
<td><span class="defaultBold">&nbsp; <a href="{homepage}">{site_name}</a>&nbsp;&#8250;&nbsp;&nbsp;<a href="{path=search/index}">{lang:search}</a>&nbsp;&#8250;&nbsp;&nbsp;{lang:search_results}</span></td>
<td align="center"><span class="defaultBold">{lang:keywords} {exp:search:keywords}</span></td>
<td align="right"><span class="defaultBold">{lang:total_search_results} {exp:search:total_results}</span></td>
</tr>
</table>

<div class='outerBorder'>
<div class='tablePad'>

<table border="0" cellpadding="6" cellspacing="1" width="100%">
<tr>
<td class="resultHead">{lang:title}</td>
<td class="resultHead">{lang:excerpt}</td>
<td class="resultHead">{lang:author}</td>
<td class="resultHead">{lang:date}</td>
<td class="resultHead">{lang:total_comments}</td>
<td class="resultHead">{lang:recent_comments}</td>
</tr>

{exp:search:search_results switch="resultRowOne|resultRowTwo"}

<tr>
<td class="{switch}" width="30%" valign="top"><b><a href="{auto_path}">{title}</a></b></td>
<td class="{switch}" width="30%" valign="top">{excerpt}</td>
<td class="{switch}" width="10%" valign="top"><a href="{member_path=member/index}">{author}</a></td>
<td class="{switch}" width="10%" valign="top">{entry_date format="%m/%d/%y"}</td>
<td class="{switch}" width="10%" valign="top">{comment_total}</td>
<td class="{switch}" width="10%" valign="top">{recent_comment_date format="%m/%d/%y"}</td>
</tr>

{/exp:search:search_results}

</table>


{if paginate}

<div class='paginate'>

<span class='pagecount'>{page_count}</span>&nbsp; {paginate}

</div>

{/if}


</td>
</tr>
</table>

<div class='copyright'><a href="http://ellislab.com/">Powered by ExpressionEngine</a></div>

</div>
</div>
</div>

</body>
</html>
EOF;
}
/* END */


function search_css()
{
return <<<EOF
body {
 margin:0;
 padding:0;
 font-family:Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:11px;
 color:#000;
 background-color:#fff;
}

a {
 text-decoration:none; color:#330099; background-color:transparent;
}
a:visited {
 color:#330099; background-color:transparent;
}
a:hover {
 color:#000; text-decoration:underline; background-color:transparent;
}

#content {
 left:				0px;
 right:				10px;
 margin:			10px 25px 10px 25px;
 padding:			8px 0 0 0;
}

.outerBorder {
 border:		1px solid #4B5388;
}

.header {
 margin:			0 0 14px 0;
 padding:			2px 0 2px 0;
 border:			1px solid #000770;
 background-color:	#797EB8;
 text-align:		center;
}

h1 {
 font-family:		Georgia, Times New Roman, Times, Serif, Arial;
 font-size: 		20px;
 font-weight:		bold;
 letter-spacing:	.05em;
 color:				#fff;
 margin: 			3px 0 3px 0;
 padding:			0 0 0 10px;
}


.copyright {
 text-align:        center;
 font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:         9px;
 color:             #999;
 margin-top:        15px;
 margin-bottom:     15px;
}

p {
 font-family:	Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:		11px;
 font-weight:	normal;
 color:			#000;
 background:	transparent;
 margin: 		6px 0 6px 0;
}

.searchSubmit {
 font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:         11px;
 color:             #000;
 text-align: center;
 padding:           6px 10px 6px 6px;
 border-top:        1px solid #4B5388;
 border-bottom:     1px solid #4B5388;
 background-color:  #C6C9CF;
}

.fieldset {
 border:        1px solid #999;
 padding: 10px;
}

.breadcrumb {
 margin:			0 0 10px 0;
 background-color:	transparent;
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:			10px;
}

.default, .defaultBold {
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:			11px;
 color:				#000;
 padding:			3px 0 3px 0;
 background-color:	transparent;
}

.defaultBold {
 font-weight:		bold;
}

.paginate {
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:			12px;
 font-weight: 		normal;
 letter-spacing:	.1em;
 padding:			10px 6px 10px 4px;
 margin:			0;
 background-color:	transparent;
}

.pagecount {
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:			10px;
 color:				#666;
 font-weight:		normal;
 background-color: transparent;
}

.tablePad {
 padding:			3px 3px 5px 3px;
 background-color:	#fff;
}

.resultRowOne {
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:			11px;
 color:				#000;
 padding:           6px 6px 6px 8px;
 background-color:	#DADADD;
}

.resultRowTwo {
 font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:         11px;
 color:             #000;
 padding:           6px 6px 6px 8px;
 background-color:  #eee;
}

.resultHead {
 font-family:		Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size: 		11px;
 font-weight: 		bold;
 color:				#000;
 padding: 			8px 0 8px 8px;
 border-bottom:		1px solid #999;
 background-color:	transparent;
}

form {
 margin:            0;
}
.hidden {
 margin:            0;
 padding:           0;
 border:            0;
}
.input {
 border-top:        1px solid #999999;
 border-left:       1px solid #999999;
 background-color:  #fff;
 color:             #000;
 font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:         11px;
 height:            1.6em;
 padding:           .3em 0 0 2px;
 margin-top:        6px;
 margin-bottom:     3px;
}
.textarea {
 border-top:        1px solid #999999;
 border-left:       1px solid #999999;
 background-color:  #fff;
 color:             #000;
 font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:         11px;
 margin-top:        3px;
 margin-bottom:     3px;
}
.select {
 background-color:  #fff;
 font-family:       Arial, Verdana, Sans-serif;
 font-size:         10px;
 font-weight:       normal;
 letter-spacing:    .1em;
 color:             #000;
 margin-top:        6px;
 margin-bottom:     3px;
}
.multiselect {
 border-top:        1px solid #999999;
 border-left:       1px solid #999999;
 background-color:  #fff;
 color:             #000;
 font-family:       Verdana, Geneva, Tahoma, Trebuchet MS, Arial, Sans-serif;
 font-size:         11px;
 margin-top:        3px;
 margin-bottom:     3px;
}
.radio {
 color:             #000;
 margin-top:        7px;
 margin-bottom:     4px;
 padding:           0;
 border:            0;
 background-color:  transparent;
}
.checkbox {
 background-color:  transparent;
 margin:            3px;
 padding:           0;
 border:            0;
}
.submit {
 background-color:  #fff;
 font-family:       Arial, Verdana, Sans-serif;
 font-size:         10px;
 font-weight:       normal;
 letter-spacing:    .1em;
 padding:           1px 3px 1px 3px;
 margin-top:        6px;
 margin-bottom:     4px;
 text-transform:    uppercase;
 color:             #000;
}
EOF;
}
/* END */



/* End of file ud_009.php */
/* Location: ./system/expressionengine/installer/updates/ud_009.php */