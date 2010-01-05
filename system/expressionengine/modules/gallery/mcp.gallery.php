
<?php

/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2003 - 2010, EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/docs/license.html
=====================================================
 File: mcp.gallery.php
-----------------------------------------------------
 Purpose: Photo Gallery Module - CP
=====================================================
*/
if ( ! defined('EXT'))
{
	exit('Invalid file request');
}



class Gallery_mcp {

	var	$row_limit		= 12; // Used for pagination
	var $max_size		= 1280;  // Maximum allowed dimensions for resizing
	
	// Private
	
	var $categories		= array();
	var $prefs			= array();
	var $image_folder	= FALSE;
	var	$horizontal_nav	= TRUE;
	var $timeout		= '';
	var $gallery_id		= '';
	var $reserved_names = array('act', 'css');


	/** -------------------------------
	/**  Constructor
	/** -------------------------------*/
	
	function Gallery_mcp($switch = TRUE)
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		
		/** -------------------------------
		/**  Reserved forum name
		/** -------------------------------*/
		
		// If the forum module is installed we'll add it to the reserved word list

		if ($this->EE->config->item("forum_is_installed") == 'y' AND $this->EE->config->item("forum_trigger") != '')
		{
			$this->reserved_names[] = $this->EE->config->item("forum_trigger");
		}
				
		if ($this->EE->config->item("profile_trigger") != '')
		{
			$this->reserved_names[] = $this->EE->config->item("profile_trigger");
		}		
		
		/** -------------------------------
		/**  Assign Base Crumb
		/** -------------------------------*/
		
		$this->EE->dsp->crumb = $this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery', $this->EE->lang->line('gallery_image_galleries'));		
		
		/** -------------------------------
		/**  Fetch the gallery ID number
		/** -------------------------------*/
		
		$this->gallery_id = ( ! $this->EE->input->get_post('P')) ? FALSE : $this->EE->input->get_post('gallery_id');					
		
		/** -------------------------------
		/**  Set Exceptions
		/** -------------------------------*/
		// The "gallery_id" variable is required either as a GET or POST 
		// variable for every single page request, except these:
		
		$exceptions = array('create_new_gallery', 'gallery_prefs_form', 'prefs_submission_handler', 'new_gallery_step_two', 'color_picker');
		
		if ($this->EE->input->get_post('P') AND ! in_array($this->EE->input->get_post('P'), $exceptions))
		{
			if ($this->gallery_id != FALSE  AND ! is_numeric($this->gallery_id))
			{
				return $this->EE->dsp->no_access_message();
			}
		}

		/** -------------------------------
		/**  Authorize User Access
		/** -------------------------------*/
		if ($this->gallery_id != FALSE)
		{
			if ( ! $this->assign_gallery_preferences())
			{
				return $this->EE->dsp->no_access_message();
			}			
		}
		
		if ($this->gallery_id == FALSE && ! in_array($this->EE->input->get_post('P'), $exceptions))
		{
			return $this->index();
		}

/*		
		//  Determine the function needed
		if ($switch)
		{
			switch($this->EE->input->get_post('P'))
			{
				case	'manage_gallery'		:	$this->content_wrapper();
					break;
				case	'entry_form'			:	$this->entry_form();
					break;
				case	'insert_new_entry'		:	$this->insert_new_entry();
					break;
				case	'update_entry'			:	$this->update_entry();
					break;
				case 'delete_entry_conf'		:	$this->delete_entry_confirm();
					break;
				case 'delete_entry'				:	$this->delete_entry();
					break;
				case 'batch_entries'			:	$this->batch_entries();
					break;
				case 'view_comments'			:	$this->view_comments();
					break;
				case 'change_comment_status'	:	$this->change_comment_status();
					break;
				case 'edit_comment'				:	$this->edit_comment_form();
					break;
				case 'update_comment'			:	$this->update_comment();
					break;
				case 'del_comment'				:	$this->delete_comment();
					break;
				case 'file_browser'				:	$this->file_browser();
					break;
				case 'image_toolbox'			:	$this->image_toolbox();
					break;					
				case 'run_toolbox'				:	$this->run_toolbox();
					break;	
				case 'image_refresher'			:	$this->image_refresher();
					break;
				case	'category_manager'		:	
				case	'view_entries'			:	$this->category_manager();
					break;
				case	'edit_category'			:	$this->category_form();
					break;
				case	'view_files'			:	$this->view_files();
					break;
				case	'new_category'			:
				case	'update_category'		:	$this->category_submission_hander();
					break;
				case	'cat_order'				:	$this->change_category_order();
					break;
				case	'del_category_conf'		:	$this->delete_category_confirm();
					break;
				case	'del_category'			:	$this->delete_category();
					break;
				case	'global_cat_order'		:	$this->reorder_categories();
					break;
				case	'create_new_gallery'	:	$this->create_new_gallery();
					break;
				case 'new_gallery_step_two'		:	$this->create_new_gallery_step_two();
					break;
				case	'gallery_prefs_form'	:	$this->gallery_prefs_form();
					break;
				case	'prefs_submission_handler'	:	$this->prefs_submission_handler();
					break;
				case 'wm_tester'				:	$this->watermark_tester();
					break;
				case 'color_picker'				:	$this->color_picker();
					break;
				case	'delete_gallery_conf'	:	$this->delete_gallery_confirm();
					break;
				case	'delete_gallery'		:	$this->delete_gallery();
					break;
				case	'multi_edit_entries'	:	$this->multi_edit_entries();
					break;
				default							:	$this->index();
					break;
			}
		}
		*/
	}


	/** ----------------------------------------
	/**  Fetch Gallery Preferences
	/** ----------------------------------------*/
	function assign_gallery_preferences()
	{
		$sql = "SELECT * FROM exp_galleries WHERE gallery_id = '".$this->EE->db->escape_str($this->gallery_id)."'";
				
		$query = $this->EE->db->query($sql);
		
		if ($query->num_rows() == "")
		{
			return FALSE;
		}
		
		foreach ($query->row_array() as $key => $val)
		{
			$this->prefs[$key] = $val;		
		}
		
		if (strncmp($this->prefs['gallery_thumb_prefix'], '_', 1) != 0 && strncmp($this->prefs['gallery_thumb_prefix'], '-', 1) != 0)
		{
			$this->prefs['gallery_thumb_prefix'] = "_".$this->prefs['gallery_thumb_prefix'];			
		}
		
		if (strncmp($this->prefs['gallery_medium_prefix'], '_', 1) != 0 && strncmp($this->prefs['gallery_medium_prefix'], '-', 1) != 0)
		{
			$this->prefs['gallery_medium_prefix'] = "_".$this->prefs['gallery_medium_prefix'];
		}		
		
		$this->EE->load->helper('path');
		
		$this->prefs['gallery_upload_path'] = set_realpath($this->prefs['gallery_upload_path']);
		
		$this->prefs['gallery_thumb_quality']  = str_replace('%', '', $this->prefs['gallery_thumb_quality']);
		$this->prefs['gallery_medium_quality'] = str_replace('%', '', $this->prefs['gallery_medium_quality']);

		return TRUE;
	}



	/** -----------------------------------
	/**  Navigation Tabs
	/** -----------------------------------*/
	// Takes an array as input and creates the navigation tabs from it.
	// This functiion is called by the one above.

	function nav($nav_array)
	{
		/** -------------------------------
		/**  Build the menus
		/** -------------------------------*/
		// Equalize the text length.
		// We do this so that the tabs will all be the same length.
		
		$temp = array();
		foreach ($nav_array as $k => $v)
		{
			$temp[$k] = $this->EE->lang->line($k);
		}
		$temp = $this->EE->dsp->equalize_text($temp);

		//-------------------------------

		$page = $this->EE->input->get_post('P');
		
		$highlight = array(
							'entry_form'			=> 'gallery_new_entry',
							'batch_entries'			=> 'gallery_batch_entries',
							'view_entries'			=> 'gallery_view_entries',
							'view_files'			=> 'gallery_view_entries',
							'category_manager'		=> 'gallery_categories',
							'update_category'		=> 'gallery_categories',
							'edit_category'			=> 'gallery_categories',
							'image_toolbox'			=> 'gallery_image_toolbox',
							'image_toolbox'			=> 'gallery_image_toolbox',
							'gallery_prefs_form'	=> 'gallery_preferences'
							);
							
							
		if (isset($highlight[$page]))
		{
			$page = $highlight[$page];
		}
		
			
		$r = <<<EOT
		
		<script type="text/javascript"> 
		<!--

		function styleswitch(link)
		{				 
			if (document.getElementById(link).className == 'altTabs')
			{
				document.getElementById(link).className = 'altTabsHover';
			}
		}
	
		function stylereset(link)
		{				 
			if (document.getElementById(link).className == 'altTabsHover')
			{
				document.getElementById(link).className = 'altTabs';
			}
		}
		
		-->
		</script>
		
		
EOT;
	
		$r .= $this->EE->dsp->table_open(array('width' => '100%'));

		$nav = array();
		foreach ($nav_array as $key => $val)
		{
			$url = '';
		
			if (is_array($val))
			{
				$url = BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'gallery_id='.$this->gallery_id;		
			
				foreach ($val as $k => $v)
				{
					$url .= AMP.$k.'='.$v;
				}					
				$title = $temp[$key];
			}
			else
			{	
				$url = $this->EE->functions->prep_query_string($this->EE->functions->fetch_site_index()).QUERY_MARKER.'URL='.$this->EE->functions->prep_query_string($this->prefs['gallery_url']);
				
				$title = $this->EE->lang->line('gallery_launch');
			}
			

			$url = ($url == '') ? $val : $url;

			$div = ($page == $key) ? 'altTabSelected' : 'altTabs';
			$linko = '<div class="'.$div.'" id="'.$key.'"  onclick="navjump(\''.$url.'\');" onmouseover="styleswitch(\''.$key.'\');" onmouseout="stylereset(\''.$key.'\');">'.$title.'</div>';
			
			$nav[] = array('text' => $this->EE->dsp->anchor($url, $linko));
		}

		$r .= $this->EE->dsp->table_row($nav);		
		$r .= $this->EE->dsp->table_close();

		return $r;		  
	}


	/** ------------------------------------------------
	/**  Content Wrapper
	/** ------------------------------------------------*/
	function content_wrapper($title = '', $crumb = '', $content = '')
	{
		// Default page title if not supplied  
						
		if ($title == '')
		{
			$title = $this->EE->lang->line('gallery_stats');
		}
				
		// Default bread crumb if not supplied
		
		if ($crumb == '')
		{
			$crumb = $this->EE->dsp->crumb_item($this->EE->lang->line('gallery_stats'));		
		}
				
		// Set breadcrumb and title
		
		$this->EE->dsp->title  = $title;
		$this->EE->dsp->crumb .= $this->EE->dsp->crumb_item($this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=manage_gallery'.AMP.'gallery_id='.$this->gallery_id, $this->prefs['gallery_full_name'])).$crumb;

		// Default content if not supplied

		if ($content == '')
		{
			$content .= $this->gallery_stats();
		}

		// Build the output
		
		$nav = $this->nav(	array(
									'gallery_new_entry'			=> array('P' => 'entry_form'),
									'gallery_batch_entries'	 	=> array('P' => 'batch_entries'),
									'gallery_view_entries'		=> array('P' => 'view_entries', 'mode' => 'view'),
									'gallery_categories'		=> array('P' => 'category_manager'),
									'gallery_image_toolbox'		=> array('P' => 'image_toolbox'),
									'gallery_preferences'		=> array('P' => 'gallery_prefs_form', 'menu' => '1'),
									'site_launch'				=> ''
								)
						);

		if ($nav != '')
		{
			$this->EE->dsp->body .= $nav;
		}
		
				
		$this->EE->dsp->body	.=	$this->EE->dsp->td('', '', '', '', 'top');
		$this->EE->dsp->body .= $this->EE->dsp->qdiv('defaultSmall', NBS);
		$this->EE->dsp->body	.=	$this->EE->dsp->qdiv('itemWrapper', $content);
	}



	
	/** ----------------------------------------
	/**  Main Gallery Page
	/** ----------------------------------------*/
	
	function index($message = '')
	{

		$this->EE->dsp->title  = $this->EE->lang->line('gallery_image_galleries');
		$this->EE->dsp->crumb  = $this->EE->lang->line('gallery_image_galleries');
		
		$this->EE->dsp->right_crumb($this->EE->lang->line('gallery_create_new_image_gallery'), BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=create_new_gallery');
		
		if ($message != '')
		{
			$this->EE->dsp->body .= $this->EE->dsp->qdiv('box', stripslashes($message));
		}
			 
		$sql = "SELECT gallery_id, gallery_full_name, gallery_url FROM exp_galleries ORDER BY gallery_full_name";

		$query = $this->EE->db->query($sql);
		
		if ($query->num_rows() == 0)
		{
			$this->EE->dsp->body .= $this->EE->dsp->div('box');
			$this->EE->dsp->body .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->heading($this->EE->lang->line('gallery_no_image_galleries'), 5));
				$this->EE->dsp->body .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=create_new_gallery', $this->EE->lang->line('gallery_create_new_image_gallery')));
			$this->EE->dsp->body .= $this->EE->dsp->div_c();

			return $this->EE->dsp->body;
		}  
		
		$this->EE->dsp->body .= $this->EE->dsp->table('tableBorder', '0', '10', '100%').
					  $this->EE->dsp->tr().
					  $this->EE->dsp->td('tableHeading', '', '').$this->EE->lang->line('gallery_image_galleries').$this->EE->dsp->td_c().
					  $this->EE->dsp->td('tableHeading', '', '').$this->EE->lang->line('gallery_launch').$this->EE->dsp->td_c().
					  $this->EE->dsp->td('tableHeading', '', '').$this->EE->lang->line('gallery_total_files').$this->EE->dsp->td_c().
					  $this->EE->dsp->td('tableHeading', '', '').$this->EE->lang->line('gallery_views').$this->EE->dsp->td_c().
					  $this->EE->dsp->td('tableHeading', '', '').$this->EE->lang->line('delete').$this->EE->dsp->td_c().
					  $this->EE->dsp->tr_c();
		$i = 0;
	
		foreach ($query->result_array() as $row)
		{
			$res = $this->EE->db->query("SELECT views FROM exp_gallery_entries WHERE gallery_id = '{$row['gallery_id']}'");
					
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			
			$views = 0;

			if ($res->num_rows() > 0)
			{
				foreach ($res->result_array() as $vrow)
				{
					$views += $vrow['views'];
				}			
			}
			
			$this->EE->dsp->body .= $this->EE->dsp->tr();
			$this->EE->dsp->body .= $this->EE->dsp->table_qcell($style, $this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=manage_gallery'.AMP.'gallery_id='.$row['gallery_id'], '<b>'.$row['gallery_full_name'].'</b>'), '35%');
			$this->EE->dsp->body .= $this->EE->dsp->table_qcell($style, $this->EE->dsp->anchor($this->EE->functions->prep_query_string($this->EE->functions->fetch_site_index()).QUERY_MARKER.'URL='.$this->EE->functions->prep_query_string($row['gallery_url']), '<b>'.$this->EE->lang->line('gallery_view').'</b>', '', TRUE), '20%');
			$this->EE->dsp->body .= $this->EE->dsp->table_qcell($style, $res->num_rows, '15%');
			$this->EE->dsp->body .= $this->EE->dsp->table_qcell($style, $views, '15%');
			$this->EE->dsp->body .= $this->EE->dsp->table_qcell($style, $this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=delete_gallery_conf'.AMP.'gallery_id='.$row['gallery_id'], $this->EE->lang->line('delete')), '15%');	  
			$this->EE->dsp->body .= $this->EE->dsp->tr_c();
		}
		
		$this->EE->dsp->body .= $this->EE->dsp->table_c();

		return $this->EE->dsp->body;
	}

	


	/** ----------------------------------------
	/**  Gallery Stats Page
	/** ----------------------------------------*/
	
	function gallery_stats()
	{
		/** --------------------------------------
		/**  Compile the stats
		/** ---------------------------------------*/
		$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_gallery_entries WHERE gallery_id = '".$this->EE->db->escape_str($this->gallery_id)."'");
		$total_entries = $query->row('count') ;

		$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_gallery_comments WHERE gallery_id = '".$this->EE->db->escape_str($this->gallery_id)."'");
		$total_comments = $query->row('count') ;
				

		$r  = '';
		$r .= $this->EE->dsp->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		$r .= $this->EE->dsp->table_row(array(
									array(
											'text'		=> $this->prefs['gallery_full_name'],
											'class'		=> 'tableHeading',
											'colspan'	=> 2
										)
									)
							);

		$r .= $this->EE->dsp->table_row(array(
									array(
											'text'	=> $this->EE->dsp->qspan('defaultBold', $this->EE->lang->line('gallery_total_entries')),
											'class'	=> 'tableCellTwo',
											'width'	=> '40%'
										),
									array(
											'text'	=> $total_entries,
											'class'	=> 'tableCellTwo',
											'width'	=> '60%'
										)
									)
							);

		$r .= $this->EE->dsp->table_row(array(
									array(
											'text'	=> $this->EE->dsp->qspan('defaultBold', $this->EE->lang->line('gallery_total_comments')),
											'class'	=> 'tableCellTwo',
											'width'	=> '40%'
										),
									array(
											'text'	=> $total_comments,
											'class'	=> 'tableCellTwo',
											'width'	=> '60%'
										)
									)
							);
							
 		$r .= $this->EE->dsp->table_close();
		$r .= $this->EE->dsp->qdiv('defaultSmall', '');
		
		
		
 	
		/** ------------------------------------
		/**  Have categories been set up yet?
		/** ------------------------------------*/
		
		$this->category_tree('raw', 'a', '', FALSE);

		if (count($this->categories) > 0)
		{
			$r .= $this->EE->dsp->table_open(array('class' => 'tableBorder', 'width' => '100%'));
			$r .= $this->EE->dsp->table_row(array(
										array(
												'text'		=> $this->EE->lang->line('gallery_categories'),
												'class'		=> 'tableHeading'
											),
										array(
												'text'		=> $this->EE->lang->line('gallery_total_entries'),
												'class'		=> 'tableHeading'
											)
										)
								);
																
			foreach ($this->categories as $val)
			{			
				$r .= $this->EE->dsp->table_row(array(
											array(
													'text'	=> $this->EE->dsp->qspan('defaultBold', $val['1']),
													'class'	=> 'tableCellTwo',
													'width'	=> '40%'
												),
											array(
													'text'	=> $val['0'],
													'class'	=> 'tableCellTwo',
													'width'	=> '60%'
												)
											)
									);
			}
			
			$r .= $this->EE->dsp->table_close();
		}
 		
		return $r;
	}


	
	
	/** ----------------------------------------
	/**  New/Edit Entry Form
	/** ----------------------------------------*/
	
	function entry_form()
	{
		/** ------------------------------------
		/**  Are we editing an existing entry?
		/** ------------------------------------*/
				
		$entry_id = ( ! $this->EE->input->get_post('entry_id')) ? FALSE : $this->EE->input->get_post('entry_id');
		
		/** ---------------------------------------
		/**  Is there a default category for new entries?
		/** ---------------------------------------*/
		
		if ($entry_id === FALSE)
		{
			$result = $this->ee->db->query("SELECT cat_id FROM exp_gallery_categories WHERE is_default = 'y' AND gallery_id = '".$this->gallery_id."'");

			if ($result->num_rows() == 1)
			{
				$cat_id = $result->row('cat_id');
			}			
		}
		
		/** ------------------------------------
		/**  Set Default Variables
		/** ------------------------------------*/
		
		$default = array('cat_id', 'author_id', 'filename', 'extension', 'title', 'caption', 'status', 'entry_date', 'allow_comments', 'views', 'custom_field_one', 'custom_field_two', 'custom_field_three', 'custom_field_four', 'custom_field_five', 'custom_field_six');
		
		if ($entry_id !== FALSE)
		{
			$query = $this->EE->db->query("SELECT cat_id, author_id, filename, extension, title, caption, status, entry_date, allow_comments, views, custom_field_one, custom_field_two, custom_field_three, custom_field_four, custom_field_five, custom_field_six FROM exp_gallery_entries WHERE entry_id = '".$this->EE->db->escape_str($entry_id)."' AND gallery_id = '{$this->gallery_id}'");
		
			$row = $query->row_array();

			foreach ($default as $val)
			{
				$name = str_replace('custom_field', 'gallery_cf', $val);
				$$name = $row[$val];
			}
		}
		else
		{
			foreach ($default as $val)
			{
				$val = str_replace('custom_field', 'gallery_cf', $val);
				$$val = '';
			}
		}

		if ($author_id == '')
		{
			$author_id = $this->EE->session->userdata('member_id');
		}
		
		if ($entry_date == '')
		{
			$entry_date = $this->EE->localize->now;
		}
		
		if ($status == '')
		{
			$status = 'o';
		}
		
		$fullname = $filename.$extension;
		
		/** ------------------------------------
		/**  Page heading/crumb/title
		/** ------------------------------------*/
		$heading = ($entry_id == '') ? $this->EE->lang->line('gallery_new_entry') : $this->EE->lang->line('gallery_edit_entry');
		$page = ($entry_id == '') ? $this->EE->lang->line('gallery_new_entry') : $this->EE->lang->line('gallery_edit_entry');
			$crumb = $this->EE->dsp->crumb_item($page);
		
		/** ------------------------------------
		/**  Have categories been set up yet?
		/** ------------------------------------*/
		// Categrories are required, so if they have not set them up issue a warning
		
		$this->category_tree('text', $this->prefs['gallery_sort_order'], $cat_id);
		
		if (count($this->categories) == 0)
		{
			$r  = $this->EE->dsp->qdiv('tableHeading', $heading);
			$r .= $this->EE->dsp->qdiv('box', $this->EE->dsp->qdiv('highlight', $this->EE->lang->line('gallery_categories_required').BR.BR).$this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=edit_category'.AMP.'gallery_id='.$this->gallery_id, $this->EE->lang->line('gallery_new_category'))));

			return $this->content_wrapper($title, $crumb, $r);
		}
		
		/** ------------------------------------
		/**  Javascript show/hide
		/** -------------------------------------*/
$r = <<<EOT
<script type="text/javascript">
<!--
	
	function showhide_item(id)
	{
		if (document.getElementById(id).style.display == "block")
		{
			document.getElementById(id).style.display = "none";
		}
		else
		{
			document.getElementById(id).style.display = "block";
		}
	}
			
//-->
</script>
EOT;
		
		$file_array = array();
		
		/** ------------------------------------
		/**  Fetch File list from server
		/** ------------------------------------*/
		
		if ($entry_id == '')
		{
		
			if ( ! class_exists('File_Browser'))
			{ 
				require APPPATH.'cp/cp.filebrowser'.EXT;
			}
			
			$FP = new File_Browser();
			$FP->show_errors = FALSE;
			$FP->images_only = TRUE;
			$FP->ignore[] = $this->prefs['gallery_thumb_prefix'];
			$FP->ignore[] = $this->prefs['gallery_medium_prefix'];
			$FP->set_upload_path($this->prefs['gallery_upload_path']);
			$FP->create_filelist('');
			
			$directory_url = $this->EE->functions->remove_double_slashes($this->prefs['gallery_image_url'].'/');
			
			if (count($FP->filelist) > 0)
			{
			
$r .= <<<EOT

<script type="text/javascript">
<!--

var item=new Array();
var width=new Array();
var height=new Array();

EOT;

	$i = 0;
	foreach ($FP->filelist as $file_info)
	{
		$file_array[] = $file_info['name'];
		$r .= "width[$i] = ".$file_info['width'].";\n";
		$r .= "height[$i] = ".$file_info['height'].";\n";
		$i++;
	}

$r .= <<<EOT
	
	function showimage(i)
	{
		var loc_w = 10;
		var loc_h = 10;
		
		var id		= document.getElementById('entryform').filebrowse.options[i].value;
		var name	= document.getElementById('entryform').filebrowse.options[i].text;
		var loc		= '{$directory_url}'+name;
		
		if (id != 'null')
		{
			window.open(loc, '_blank','width='+width[id]+',height='+height[id]+',screenX='+loc_w+',screenY='+loc_h+',top='+loc_h+',left='+loc_w+',toolbar=0,status=0,scrollbars=0,location=0,menubar=1,resizable=1');
		}
		
		document.getElementById('entryform').serverfile.value = name;
		return false;
	}
			
//-->
</script>

EOT;
			}
		}
 		
 			 
		if ($this->EE->input->get_post('action') == 'insert')
		{
			$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('successBox', $this->EE->dsp->qdiv('success', $this->EE->lang->line('gallery_file_inserted'))));						 
		}
		elseif ($this->EE->input->get_post('action') == 'update')
		{
			$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('successBox', $this->EE->dsp->qdiv('success', $this->EE->lang->line('gallery_file_updated'))));						 
		}
		
		
		$i = 0;
		
		$form_loc = 'C=modules'.AMP.'M=gallery'.AMP.'P=';
		
		if ($entry_id == '')
		{
			$r .= "<form name='entryform' id='entryform' method=\"post\" action=\"".BASE.AMP.$form_loc."insert_new_entry\" enctype=\"multipart/form-data\">\n";
		}
		else
		{
			$r .= $this->EE->dsp->form_open(
								array(
										'action' => $form_loc.'update_entry', 
										'name'	=> 'entryform',
										'id'	=> 'entryform'
									)
								);
		}
		
		$r .= $this->EE->dsp->input_hidden('gallery_id', $this->gallery_id);
		$r .= $this->EE->dsp->input_hidden('entry_id', $entry_id);
		$r .= $this->EE->dsp->input_hidden('old_title', $title);
		$r .= $this->EE->dsp->input_hidden('old_cat', $cat_id);
		$r .= $this->EE->dsp->input_hidden('author_id', $author_id);
		$r .= $this->EE->dsp->input_hidden('raw_filename', $filename);
		$r .= $this->EE->dsp->input_hidden('extension', $extension);
		$r .= $this->EE->dsp->input_hidden('serverfile', 'null');

		$r .= $this->EE->dsp->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		$r .= $this->EE->dsp->table_row(array(
									array(
											'text'		=> $heading,
											'class'		=> 'tableHeading',
											'colspan'	=> 2
										)
									)
							);


		// Image selector

		$input = ($entry_id == '') ? "<input type=\"file\" name=\"userfile\" size=\"20\" />" : $this->EE->dsp->input_text('filename', $fullname, '15', '100', 'input', '200px').NBS.$this->EE->dsp->qspan('highlight', $this->EE->lang->line('gallery_rename_note'));
		
		$flink = '';
		if (count($file_array) > 0 AND $entry_id == '')
		{		
			$flink .= "&nbsp;&nbsp;<select name='filebrowse' class='select' onchange='return showimage(this.selectedIndex);'>\n";
			$flink .= $this->EE->dsp->input_select_option('null', ($entry_id == '') ? $this->EE->lang->line('gallery_select_file') : $this->EE->lang->line('gallery_select_new_file'));
	
			$i = 0;
			foreach ($file_array as $fname)
			{
				$flink .= $this->EE->dsp->input_select_option($i++, $fname);
			}
			
			$flink .= $this->EE->dsp->input_select_footer();	
		}
		
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$r .= $this->EE->dsp->table_row(array(
									array(
											'text'		=> $this->EE->dsp->qdiv('defaultBold', $this->EE->dsp->required().$this->EE->lang->line('gallery_file_name')),
											'class'		=> $style,
											'width'		=> '20%'
										),
									array(
											'text'		=> $input.$flink,
											'class'		=> $style,
											'width'		=> '80%'
										)
									)
							);
		
		// Title
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$r .= $this->EE->dsp->table_row(array(
									array(
											'text'		=> $this->EE->dsp->qdiv('defaultBold',  $this->EE->dsp->required().$this->EE->lang->line('gallery_entry_tit')),
											'class'		=> $style,
											'width'		=> '20%'
										),
									array(
											'text'		=> $this->EE->dsp->input_text('title', $title, '15', '100', 'input', '100%'),
											'class'		=> $style,
											'width'		=> '80%'
										)
									)
							);
							
							
		
		
		// Date
		
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';

		$loc_entry_date = $this->EE->localize->set_human_time($entry_date);
		$cal_entry_date = ($this->EE->localize->set_localized_time($entry_date) * 1000);
		
		$cal_img = '<a href="javascript:void(0);" onclick="showhide_item(\'calendarentry_date\');"><img src="'.PATH_CP_GBL_IMG.'calendar.gif" border="0"  width="16" height="16" alt="'.$this->EE->lang->line('calendar').'" /></a>';
		
		/** --------------------------------
		/**  JavaScript Calendar
		/** --------------------------------*/
		
		if ( ! class_exists('js_calendar'))
		{
			if (include_once(APPPATH.'javascript/js_calendar'.EXT))
			{
				$CAL = new js_calendar();
			}				
		}			
		$this->EE->dsp->extra_header .= $CAL->calendar('left');

		$cal  = $this->EE->dsp->input_text('entry_date', $loc_entry_date, '15', '22', 'input', '150px', ' onkeyup="update_calendar(\'entry_date\', this.value);" ').$cal_img;		
		$cal .= '<div id="calendarentry_date" style="display:none;margin:4px 0 0 0;padding:0;">';
		$cal .= NL.'<script type="text/javascript">
				
				var entry_date = new calendar(
										"entry_date", 
										new Date('.$cal_entry_date.'), 
										true
										);
				
				document.write(entry_date.write());
				</script>'.NL;
		$cal .= '</div>';
		
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$r .= $this->EE->dsp->table_row(array(
									array(
											'text'		=> $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('defaultBold',  $this->EE->dsp->required().$this->EE->lang->line('gallery_entry_date'))),
											'class'		=> $style,
											'width'		=> '20%',
											'valign'	=> 'top'
										),
									array(
											'text'		=> $cal,
											'class'		=> $style,
											'width'		=> '80%'
										)
									)
							);
							
		// Categories
		
		$cat_menu  = $this->EE->dsp->input_select_header('cat_id');
		foreach ($this->categories as $val)
		{
			$cat_menu .= $val;
		}
		$cat_menu .= $this->EE->dsp->input_select_footer();	
		
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$r .= $this->EE->dsp->table_row(array(
									array(
											'text'		=> $this->EE->dsp->qdiv('defaultBold',  $this->EE->dsp->required().$this->EE->lang->line('gallery_entry_cat')),
											'class'		=> $style,
											'width'		=> '20%'
										),
									array(
											'text'		=> $cat_menu,
											'class'		=> $style,
											'width'		=> '80%'
										)
									)
							);		
		// Status
		
		$status_menu = $this->EE->dsp->input_select_header('status');
		$status_menu .= $this->EE->dsp->input_select_option('o', $this->EE->lang->line('gallery_open'), ($status == 'o') ? 1 : 0);
		$status_menu .= $this->EE->dsp->input_select_option('c', $this->EE->lang->line('gallery_closed'), ($status == 'c') ? 1 : 0);		
		$status_menu .= $this->EE->dsp->input_select_footer();
				
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$r .= $this->EE->dsp->table_row(array(
									array(
											'text'		=> $this->EE->dsp->qdiv('defaultBold',  $this->EE->dsp->required().$this->EE->lang->line('gallery_status')),
											'class'		=> $style,
											'width'		=> '20%'
										),
									array(
											'text'		=> $status_menu,
											'class'		=> $style,
											'width'		=> '80%'
										)
									)
							);
		
		// Watermark Image
		
		if ($this->prefs['gallery_wm_type'] != 'n')
		{
			$watermark =  ($this->prefs['gallery_wm_type'] != 'n' AND $entry_id == FALSE) ? 'y' : 'n';
		
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			$r .= $this->EE->dsp->table_row(array(
										array(
												'text'		=> $this->EE->dsp->qdiv('defaultBold',  $this->EE->lang->line('gallery_watermark_image')),
												'class'		=> $style,
												'width'		=> '20%'
											),
										array(
												'text'		=> $this->EE->dsp->input_checkbox('apply_watermark', 'y', ($watermark == 'y') ? 1 : 0),
												'class'		=> $style,
												'width'		=> '80%'
											)
										)
								);
		}
	
		// Allow comments
		$allow =  ($entry_id == '') ? $this->prefs['gallery_allow_comments'] : $allow_comments;		
	
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$r .= $this->EE->dsp->table_row(array(
									array(
											'text'		=> $this->EE->dsp->qdiv('defaultBold',  $this->EE->lang->line('gallery_allow_entry_comments')),
											'class'		=> $style,
											'width'		=> '20%'
										),
									array(
											'text'		=> $this->EE->dsp->input_checkbox('allow_comments', 'y', ($allow == 'y') ? 1 : 0),
											'class'		=> $style,
											'width'		=> '80%'
										)
									)
							);				

		// Caption
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$r .= $this->EE->dsp->table_row(array(
									array(
											'text'		=> $this->EE->dsp->qdiv('defaultBold',  $this->EE->lang->line('gallery_entry_des')),
											'class'		=> $style,
											'width'		=> '20%',
											'valign'	=> 'top'
										),
									array(
											'text'		=> $this->EE->dsp->input_textarea('caption', $caption, 8, 'textarea', '100%'),
											'class'		=> $style,
											'width'		=> '80%'
										)
									)
							);
		
		// Custom Fields
		
		foreach (array('gallery_cf_one', 'gallery_cf_two', 'gallery_cf_three', 'gallery_cf_four', 'gallery_cf_five', 'gallery_cf_six') as $val)
		{
			if ($this->prefs[$val] == 'y')
			{
				$fieldname = str_replace('gallery_cf', 'custom_field', $val);
				
				switch ($this->prefs[$val.'_type'])
				{
					case 'i' :	$field = $this->EE->dsp->input_text($fieldname, $$val, '15', '100', 'input', '100%');
						break;
					case 't' :	$field = $this->EE->dsp->input_textarea($fieldname, $$val, $this->prefs[$val.'_rows'], 'textarea', '100%');
						break;
					case 's' :
								$field = $this->EE->dsp->input_select_header($fieldname);
								foreach (explode("\n", trim($this->prefs[$val.'_list'])) as $v)
								{					
									$v = trim($v);
									$selected = ($v == $$val) ? 1 : '';
									$field .= $this->EE->dsp->input_select_option($v, $v, $selected);
								}
								$field .= $this->EE->dsp->input_select_footer();
						break;
				}
				
				$r .= $this->EE->dsp->table_row(array(
											array(
													'text'		=> $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('defaultBold', $this->prefs[$val.'_label'])),
													'class'		=> $style,
													'width'		=> '20%',
													'valign'	=> 'top'
												),
											array(
													'text'		=> $field,
													'class'		=> $style,
													'width'		=> '80%'
												)
											)
									);				
			}
		}

		// Views
			
		if ($views == '' OR ! is_numeric($views))
			$views = '0';

		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$r .= $this->EE->dsp->table_row(array(
									array(
											'text'		=> $this->EE->dsp->qdiv('defaultBold',  $this->EE->lang->line('gallery_views')),
											'class'		=> $style,
											'width'		=> '20%'
										),
									array(
											'text'		=> $this->EE->dsp->input_text('views', $views, '12', '10', 'input', '60px'),
											'class'		=> $style,
											'width'		=> '80%'
										)
									)
							);								
			
		// Show image if editing
		
		if ($entry_id != '')
		{
			$result = $this->EE->db->query("SELECT cat_folder FROM exp_gallery_categories WHERE cat_id = '$cat_id' AND gallery_id = '".$this->gallery_id."'");
			$cat_folder = ($result->row('cat_folder')  != '') ? $result->row('cat_folder')  : '';
		
		
			require APPPATH.'_to_be_replaced/lib.image_lib'.EXT;
			$IM = new Image_lib();
			
			$url = $this->EE->functions->remove_double_slashes($this->prefs['gallery_image_url'].'/'.$cat_folder.'/'.$fullname);
			$path = $this->EE->functions->remove_double_slashes($this->prefs['gallery_upload_path'].'/'.$cat_folder.'/'.$fullname);
			
			$IM->get_image_properties($path);
			
			$props = array(
							'width' 		=> $IM->src_width,
							'height' 		=> $IM->src_height,
							'new_width'		=> ($IM->src_width > 600) ? 600 : $IM->src_width,
							'new_height'	=> ''
						  );
				
			$s = $IM->size_calculator($props);

			$img = "<img src='{$url}' width='".$s['new_width']."' height='".$s['new_height']."' border='0' title='".$filename.$extension."' />";

			$r .= $this->EE->dsp->table_row(array(
										array(
												'text'		=> $img,
												'class'		=> 'galleryBG',
												'colspan'	=> '2'
											)
										)
								);				
		}
		
		
		/* -------------------------------------------
		/* 'gallery_cp_entry_form_add_row' hook.
		/*  - Allows adding of new rows to the New Entry or Edit form
		/*  - Added 1.4.2
		*/
			if ($this->EE->extensions->active_hook('gallery_cp_entry_form_add_row') === TRUE)
			{
				$r .= $this->EE->extensions->call('gallery_cp_entry_form_add_row', $entry_id, $r);
			}
		/*
		/* -------------------------------------------*/
		
		 
		$r .= $this->EE->dsp->table_close();
		
		$button = ($entry_id == '') ? $this->EE->lang->line('gallery_file_submit') : $this->EE->lang->line('gallery_file_update');
		
		$r .= $this->EE->dsp->qdiv('tablePad',  BR.NBS.$this->EE->dsp->required(1).BR.BR.NBS.NBS.$this->EE->dsp->input_submit($button).BR.BR);
		$r .= $this->EE->dsp->form_close();	
	
		return $this->content_wrapper($page, $crumb, $r);
	}

 	


	/** ---------------------------------
	/**  Batch Entries Form
	/** ---------------------------------*/
	function batch_entries()
	{
		$this->horizontal_nav = TRUE;
		
		/** ------------------------------------
		/**  Set title, crumb and header
		/** ------------------------------------*/
		
		$title = $this->EE->lang->line('gallery_batch_entries');
		$crumb = $this->EE->dsp->crumb_item($this->EE->lang->line('gallery_batch_entries'));
		
		// Default vars
		
		$row			= ( ! $this->EE->input->get_post('row')) ? 0 : $this->EE->input->get_post('row');
		$action		= ( ! $this->EE->input->get_post('action')) ? FALSE : $this->EE->input->get_post('action');
		$filename	= '';
		$extension	= '';
		
		$r = '';
		
		/** ------------------------------------
		/**  Have categories been set up yet?
		/** ------------------------------------*/
		
		// If not, admonish harshly..
		
		$deft_cat = (isset($_GET['deft_cat']) AND $_GET['deft_cat'] != 'none') ? $_GET['deft_cat'] : '';
		
		$this->category_tree('text', $this->prefs['gallery_sort_order'], $deft_cat);
		
		if (count($this->categories) == 0)
		{
			$r .= $this->EE->dsp->qdiv('tableHeading', $title);
			$r .= $this->EE->dsp->qdiv('box', $this->EE->dsp->qdiv('highlight', $this->EE->lang->line('gallery_categories_required').BR.BR).$this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=edit_category'.AMP.'gallery_id='.$this->gallery_id, $this->EE->lang->line('gallery_new_category'))));
			return $this->content_wrapper($title, $crumb, $r);
		}
		
		/** ------------------------------------
		/**  Fetch File list from server
		/** ------------------------------------*/
		
		// No files? Beat them with a stick.
						
		if ($this->prefs['gallery_batch_path'] == '' OR  ! @is_dir($this->prefs['gallery_batch_path']))
		{
			$r .= $this->EE->dsp->qdiv('tableHeading', $title);
			$r .= $this->EE->dsp->div('box');		
			$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('alert', $this->EE->lang->line('gallery_no_batch_folder')));
			$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->lang->line('gallery_batch_instructions'));
			$r .= $this->EE->dsp->div_c();
			
			return $this->content_wrapper($title, $crumb, $r);
		}
	
		/** ------------------------------------
		/**  Define Filter Menu
		/** ------------------------------------*/
		
		$filter_by = ( ! isset($_GET['filter_by'])) ? FALSE : $_GET['filter_by'];
						
		$loc = BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=batch_entries'.AMP.'gallery_id='.$this->gallery_id.AMP.'filter_by=';		

		$fltr  = "<select class='select' name='filter_by' onchange='window.open(\"".$loc."\"+this.options[this.selectedIndex].value, \"_top\");' >\n";
		
		$fltr .= $this->EE->dsp->input_select_option('',  $this->EE->lang->line('gallery_filter_title'), ($filter_by === FALSE) ? 1 : 0);
		$fltr .= $this->EE->dsp->input_select_option('0', $this->EE->lang->line('gallery_filter_any'),   ($filter_by == '0') ? 1 : 0);
		$fltr .= $this->EE->dsp->input_select_option('1', $this->EE->lang->line('gallery_filter_today'), ($filter_by == 1) ? 1 : 0);
		$fltr .= $this->EE->dsp->input_select_option('7', $this->EE->lang->line('gallery_filter_week'),  ($filter_by == 7) ? 1 : 0);		
		$fltr .= $this->EE->dsp->input_select_option('30',$this->EE->lang->line('gallery_filter_month'), ($filter_by == 30) ? 1 : 0);
			
		$fltr .= $this->EE->dsp->input_select_footer();
		
		/** ------------------------------------
		/**  Fetch File list from server
		/** ------------------------------------*/
		
		if ( ! class_exists('File_Browser'))
		{ 
			require APPPATH.'cp/cp.filebrowser'.EXT;
		}
		
		$FP = new File_Browser();
		$FP->images_only = TRUE;
		$FP->recursive	 = FALSE;
		$FP->cutoff_date = $filter_by;
		$FP->ignore[] = $this->prefs['gallery_thumb_prefix'];
		$FP->ignore[] = $this->prefs['gallery_medium_prefix'];
		$FP->set_upload_path($this->prefs['gallery_batch_path']);
		$FP->create_filelist();
	
		/** ------------------------------------
		/**  No File?
		/** ------------------------------------*/
		
		// Run them out of town...
				
		if (count($FP->filelist) == 0)
		{
			$r .= $this->EE->dsp->qdiv('tableHeading', $title);
		
			if ($filter_by === FALSE)
			{		
				$r .= $this->EE->dsp->div('box');		
				$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('alert', $this->EE->lang->line('gallery_no_batch_files')));
				$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->lang->line('gallery_batch_files_info'));
				$r .= $this->EE->dsp->div_c();
			}
			else
			{			
				$r .= $this->EE->dsp->div('box');		
				$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('alert', $this->EE->lang->line('gallery_no_file_criteria')));
				$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->form_open(array('action' => '')).$fltr.$this->EE->dsp->form_close());
				$r .= $this->EE->dsp->div_c();
			}
			
			return $this->content_wrapper($title, $crumb, $r);		
		}
		
		
		/** ------------------------------------
		/**  Success message after insertion
		/** ------------------------------------*/
				
		if ($action != FALSE)
		{	
			$r .= $this->EE->dsp->qdiv('successBox', $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qspan('success', $this->EE->lang->line('gallery_img_inserted')).$this->EE->dsp->qspan('defaultBold', NBS.$FP->filelist[$row-1]['name'])));
		}
		
		/** ------------------------------------
		/**  Are there files to show?
		/** ------------------------------------*/
		
		if (count($FP->filelist) == $row)
		{
			$r .= $this->EE->dsp->div('box');		
			$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('success', $this->EE->lang->line('gallery_batch_complete')));
			$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('highlight', $this->EE->lang->line('gallery_batch_file_note')));
			$r .= $this->EE->dsp->div_c();
			
			return $this->content_wrapper($title, $crumb, $r);				
		}
				
		/** ------------------------------------
		/**  Build the category drop-down menu
		/** ------------------------------------*/
		
		$cat_menu = '';
		
		foreach ($this->categories as $val)
		{
			$cat_menu .= $val;
		}
		
		$cat_menu .= $this->EE->dsp->input_select_footer();	
		
		/** ------------------------------------
		/**  Build the status drop-down
		/** ------------------------------------*/
		$status_menu  = $this->EE->dsp->input_select_option('o', $this->EE->lang->line('gallery_open'), (( ! isset($_GET['status'])) OR (isset($_GET['status']) AND $_GET['status'] == 'o')) ? 1 : 0);
		$status_menu .= $this->EE->dsp->input_select_option('c', $this->EE->lang->line('gallery_closed'), (isset($_GET['status']) AND $_GET['status'] == 'c') ? 1 : 0);		
		$status_menu .= $this->EE->dsp->input_select_footer();

		/** ------------------------------------
		/**  Instantiate Image_lib Class
		/** ------------------------------------*/
		require APPPATH.'_to_be_replaced/lib.image_lib'.EXT;
		$IM = new Image_lib();

		/** ------------------------------------
		/**  Build the output
		/** ------------------------------------*/
		
		$r .= $this->EE->dsp->form_open(
								array(
										'action' => 'C=modules'.AMP.'M=gallery'.AMP.'P=insert_new_entry'.AMP.'from=batch'.AMP.'row='.$row, 
										'name'	=> 'entryform',
										'id'	=> 'entryform'
									)
							);

		$x = count($FP->filelist);
		$s = $row+1;

		$r .= $this->EE->dsp->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		$r .= $this->EE->dsp->table_row(array(
									array(
											'text'		=> $fltr,
											'class'		=> 'tableHeading',
											'width'		=> '25%'
										),
									array(
											'text'		=> str_replace("%s", $s, str_replace("%x", $x, $this->EE->lang->line('gallery_process_total'))),
											'class'		=> 'tableHeading',
											'width'		=> '75%'
										)
									)
							);
		
		$j  = 0;
		
		foreach ($FP->filelist as $file_info)
		{
			if ($j != $row)
			{
				$j++;
				continue;
			}
			
			$nsplit = $IM->explode_name($file_info['name']);
			$filename  = $nsplit['name'];
			$extension = $nsplit['ext'];
			
			$i = 0;
			
			// Base the title off of the filename
			$x = explode('.', $file_info['name']);
			$title = ucwords(str_replace('_', ' ', str_replace('.'.end($x), '', $file_info['name'])));
		
			$props = array(
							'width' 		=> $file_info['width'],
							'height' 	=> $file_info['height'],
							'new_width'	=> 170,
							'new_height'	=> ''
						  );	
		
			$vals = $IM->size_calculator($props);
		
			$image = "<img src='".$this->prefs['gallery_batch_url'].$file_info['name']."' width='".$vals['new_width']."' height='".$vals['new_height']."' border='0' />";
					
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			$r .= $this->EE->dsp->table_row(array(
										array(
												'text'		=> $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('defaultBold', $this->EE->dsp->required().$this->EE->lang->line('gallery_file_name'))),
												'class'		=> $style
											),
										array(
												'text'		=> $this->EE->dsp->qspan('highlight', $file_info['name']),
												'class'		=> $style
											)
										)
								);

			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			$r .= $this->EE->dsp->table_row(array(
										array(
												'text'		=> $this->EE->dsp->qdiv('defaultBold', $this->EE->dsp->required().$this->EE->lang->line('gallery_entry_tit')),
												'class'		=> $style
											),
										array(
												'text'		=> $this->EE->dsp->input_text('title', $title, '20', '100', 'input', '100%'),
												'class'		=> $style
											)
										)
								);

			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';								
			$r .= $this->EE->dsp->table_row(array(
										array(
												'text'		=> $image,
												'class'		=> 'galleryBG'
											),
										array(
												'text'		=> $this->EE->dsp->qdiv('galleryLight', $this->EE->lang->line('gallery_entry_des')).$this->EE->dsp->input_textarea('caption', '', 10, 'textarea', '100%'),
												'class'		=> $style
											)
										)
								);

		
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			$r .= $this->EE->dsp->table_row(array(
										array(
												'text'		=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_entry_date')),
												'class'		=> $style
											),
										array(
												'text'		=> $this->EE->dsp->input_text('entry_date', $this->EE->localize->set_human_time($this->EE->localize->now), '15', '22', 'input', '200px'),
												'class'		=> $style
											)
										)
								);

			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			$r .= $this->EE->dsp->table_row(array(
										array(
												'text'		=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_entry_cat')),
												'class'		=> $style
											),
										array(
												'text'		=> $this->EE->dsp->input_select_header('cat_id').$cat_menu,
												'class'		=> $style
											)
										)
								);

			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			$r .= $this->EE->dsp->table_row(array(
										array(
												'text'		=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_status')),
												'class'		=> $style
											),
										array(
												'text'		=> $this->EE->dsp->input_select_header('status').$status_menu,
												'class'		=> $style
											)
										)
								);
			
			if ($this->prefs['gallery_wm_type'] != 'n')
			{				
				if ( ! isset($_GET['apply_watermark']))
				{
					$watermark =  ($this->prefs['gallery_wm_type'] != 'n') ? 'y' : 'n';
				}
				else
				{
					$watermark = $_GET['apply_watermark'];
				}
				
				$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
				$r .= $this->EE->dsp->table_row(array(
											array(
													'text'		=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_watermark_image')),
													'class'		=> $style
												),
											array(
													'text'		=> $this->EE->dsp->input_checkbox('apply_watermark', 'y', ($watermark == 'y') ? 1 : 0),
													'class'		=> $style
												)
											)
									);
			}
	
			
			if ( ! isset($_GET['allow_comments']))
			{
				$allow_comments =  ($this->prefs['gallery_allow_comments'] != 'n') ? 'y' : 'n';
			}
			else
			{
				$allow_comments = $_GET['allow_comments'];
			}


			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			$r .= $this->EE->dsp->table_row(array(
										array(
												'text'		=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_allow_entry_comments')),
												'class'		=> $style
											),
										array(
												'text'		=> $this->EE->dsp->input_checkbox('allow_comments', 'y', ($allow_comments == 'y') ? 1 : 0),
												'class'		=> $style
											)
										)
								);
								
								
								
			// Custom Fields
			foreach (array('gallery_cf_one', 'gallery_cf_two', 'gallery_cf_three', 'gallery_cf_four', 'gallery_cf_five', 'gallery_cf_six') as $val)
			{
				if ($this->prefs[$val] == 'y')
				{
					$fieldname = str_replace('gallery_cf', 'custom_field', $val);
					
					switch ($this->prefs[$val.'_type'])
					{
						case 'i' :	$field = $this->EE->dsp->input_text($fieldname, '', '15', '100', 'input', '100%');
							break;
						case 't' :	$field = $this->EE->dsp->input_textarea($fieldname, '', $this->prefs[$val.'_rows'], 'textarea', '100%');
							break;
						case 's' :
									$field = $this->EE->dsp->input_select_header($fieldname);
									foreach (explode("\n", trim($this->prefs[$val.'_list'])) as $v)
									{					
										$v = trim($v);
										$field .= $this->EE->dsp->input_select_option($v, $v);
									}
									$field .= $this->EE->dsp->input_select_footer();
							break;
					}
					
					$r .= $this->EE->dsp->table_row(array(
												array(
														'text'		=> $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('defaultBold', $this->prefs[$val.'_label'])),
														'class'		=> $style,
														'width'		=> '20%',
														'valign'	=> 'top'
													),
												array(
														'text'		=> $field,
														'class'		=> $style,
														'width'		=> '80%'
													)
												)
										);				
				}
			}
							
							
								
								
								
								
								
								
								
								
								
								
								
								
								
								
								
								
								
			break;
		}
				
		
			
		$r .= $this->EE->dsp->table_close(); 
		
		$r .= $this->EE->dsp->div('box');		
		$ct = count($FP->filelist);
						
		$row_minus = $row - 1;
		$prev = '';

		if ($ct > 0 AND $row_minus >= 0)
		{
			$prev = $this->EE->dsp->qspan('defaultBold', $this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=batch_entries'.AMP.'gallery_id='.$this->gallery_id.AMP.'row='.$row_minus.AMP.'filter_by='.$filter_by.AMP.'deft_cat='.$deft_cat, $this->EE->lang->line('gallery_prev_image')));
		}
		
		$row_plus = $row+1;
		$next = '';
		
		if ($ct > 1 AND $row_plus < $ct)
		{	
			$next = $this->EE->dsp->qspan('defaultBold', $this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=batch_entries'.AMP.'gallery_id='.$this->gallery_id.AMP.'row='.$row_plus.AMP.'filter_by='.$filter_by.AMP.'deft_cat='.$deft_cat, $this->EE->lang->line('gallery_next_image')));
		}
		
		$sep = ($next != '' AND $prev != '') ? NBS.NBS.'|'.NBS.NBS : '';
		
		$r .= $this->EE->dsp->qdiv('itemWrapper',  $this->EE->dsp->required(1));
		$r .= $this->EE->dsp->qdiv('itemWrapper',  $this->EE->dsp->input_submit($this->EE->lang->line('gallery_submit_entry')).$this->EE->dsp->nbs(6).$prev.$sep.$next);
		
		$r .= $this->EE->dsp->div_c();
			 
		$r .= $this->EE->dsp->input_hidden('gallery_id', $this->gallery_id);
		$r .= $this->EE->dsp->input_hidden('entry_id', '');
		$r .= $this->EE->dsp->input_hidden('old_title', '');
		$r .= $this->EE->dsp->input_hidden('author_id', $this->EE->session->userdata('member_id'));
		$r .= $this->EE->dsp->input_hidden('raw_filename', $filename);
		$r .= $this->EE->dsp->input_hidden('extension', $extension);
		$r .= $this->EE->dsp->input_hidden('serverfile', $filename.$extension);
		$r .= $this->EE->dsp->form_close();

		return $this->content_wrapper($title, $crumb, $r);
	}

	
	

	/** ----------------------------------
	/**  Insert a new gallery entry
	/** ----------------------------------*/
	function insert_new_entry()
	{
		$serverfile = ($this->EE->input->post('serverfile') == 'null') ? FALSE : $this->EE->input->get_post('serverfile');
		
		$from = ( ! $this->EE->input->get_post('from')) ? 'single' : 'batch';

		/** --------------------------------
		/**  Error Trapping
		/** --------------------------------*/
		
		$error = array();
				
		if ( ! $serverfile)
		{
			if ( ! isset($_FILES['userfile']) AND $_FILES['userfile']['name'] == '')
			{
				$error[] = $this->EE->lang->line('gallery_missing_file');
			}
		}
		
		if ( ! $title = $this->EE->input->post('title'))
		{
			$error[] = $this->EE->lang->line('gallery_missing_title');
		}
		
		if ( ! $cat_id = $this->EE->input->post('cat_id') OR $cat_id == 'null')
		{
			$error[] = $this->EE->lang->line('gallery_missing_category');
		}
			
		if ( ! $this->EE->input->post('entry_date'))
		{
			$error[] = $this->EE->lang->line('gallery_missing_date');
		}
		
		// Convert the date to a Unix timestamp
		
		$entry_date = $this->EE->localize->convert_human_date_to_gmt($this->EE->input->post('entry_date'));
					 
		if ( ! is_numeric($entry_date)) 
		{ 
			$error[] = $this->EE->lang->line('gallery_invalid_date_formatting');
		}		
		
		if (count($error) > 0)
		{
			return $this->EE->dsp->error_message($error);
		}

		/** --------------------------------
		/**  Fetch Preferences
		/** --------------------------------*/
				
		$result = $this->EE->db->query("SELECT cat_folder FROM exp_gallery_categories WHERE cat_id = '$cat_id' AND gallery_id = '".$this->gallery_id."'");
		$cat_folder = ($result->row('cat_folder')  != '') ? $result->row('cat_folder')  : '';
						
		$upload_path = $this->EE->functions->remove_double_slashes($this->prefs['gallery_upload_path'].'/'.$cat_folder.'/');

		/** --------------------------------
		/**  Perform the upload
		/** --------------------------------*/
		
		if ($serverfile == FALSE)
		{	
			require APPPATH.'_to_be_replaced/lib.upload'.EXT;
			
			$this->EE->UP = new Upload();
							
			if ($this->EE->UP->set_upload_path($upload_path) !== TRUE)
			{
				return $this->EE->UP->show_error();
			}
			
			$this->EE->UP->set_max_width(0);
			$this->EE->UP->set_max_height(0);
			$this->EE->UP->set_max_filesize(0);
			$this->EE->UP->set_max_filename(100);							 
		
			if ( ! $this->EE->UP->upload_file())
			{
				return $this->EE->UP->show_error();
			}
			
			$image_name = $this->EE->UP->file_name;
			
			if ($this->EE->UP->file_exists == TRUE)
			{	
				// Truncate the file name if needed
				$image_name = $this->EE->UP->limit_filename_length($image_name, 100);
			
				$image_name = $this->rename_file($this->EE->UP->upload_path, $this->EE->UP->file_name);
				  
				if ( ! $this->EE->UP->file_overwrite($this->EE->UP->file_name, $image_name))
				{
					return $this->EE->UP->show_error();
				}
			}
		}
		else
		{
			if ($from == 'batch')
			{
				$image_name = $serverfile;
				$extension  = $this->EE->input->get_post('extension');
				$src = $this->EE->functions->remove_double_slashes($this->prefs['gallery_batch_path'].'/');
			}
			else
			{
				$x = explode("/", $serverfile);
				$image_name = (strpos($serverfile, '/') !== FALSE) ? end($x) : $serverfile;	
				$src = $this->EE->functions->remove_double_slashes($this->prefs['gallery_upload_path'].'/');
			}
			
			// filename security and whitespace removal
			$image_name = preg_replace("/\s+/", "_", $this->EE->functions->sanitize_filename($image_name));


			require APPPATH.'_to_be_replaced/lib.upload'.EXT;
			$this->EE->UP = new Upload();
			
			// Truncate the file name if needed
			$image_name = $this->EE->UP->limit_filename_length($image_name, 100);
			
			$src = $src.$serverfile;
			$dst	 = $upload_path.$image_name;
						
			if ($src != $dst)
			{	
				if (file_exists($upload_path.$image_name))
				{
					$image_name = $this->rename_file($upload_path, $image_name);
					$dst	 = $upload_path.$image_name;
				}
							
				if ( ! @copy($src, $dst))
				{
					return $this->EE->dsp->error_message(array($this->EE->lang->line("gallery_copy_error")));
				}
			
				@chmod($dst, FILE_WRITE_MODE);
			}
		}
		
		/** --------------------------------
		/**  Compile Thumb data
		/** --------------------------------*/
		
		if ($this->prefs['gallery_create_thumb'] == 'y')
			$thumbs['thumb'] = array($this->prefs['gallery_thumb_prefix'],  $this->prefs['gallery_thumb_width'],  $this->prefs['gallery_thumb_height'], $this->prefs['gallery_thumb_quality']);
		
		if ($this->prefs['gallery_create_medium'] == 'y')
			$thumbs['med'] = array($this->prefs['gallery_medium_prefix'], $this->prefs['gallery_medium_width'], $this->prefs['gallery_medium_height'], $this->prefs['gallery_medium_quality']);		


		/** --------------------------------
		/**  Invoke the Image Lib Class
		/** --------------------------------*/
		require APPPATH.'_to_be_replaced/lib.image_lib'.EXT;
		$IM = new Image_lib();
		
		$vals = $IM->get_image_properties($upload_path.$image_name, TRUE);
		
		$width  	= $vals['width'];
		$height 	= $vals['height'];
		$t_width	= 0;
		$t_height	= 0;
		$m_width	= 0;
		$m_height	= 0;		
		
		/** --------------------------------
		/**  Do the thumbs require watermark?
		/** --------------------------------*/
		
		// We need to determine if ether the thumbnail
		// or the medium sized image require the watermark.
		// If the do, we will add the watermark to the full-sized
		// image and create our thumbs from it.  If not, we will
		// create a temporary copy of the full-sized image without
		// the watermark and use it as the basis for the thumbs.
		// Since the thumb and medium image can have separate prefs
		// we have to test for each individually.
		
		$temp_marker	 = '58fdhCX9ZXd0guhh';
		$create_tmp_copy = FALSE;
		$tmp_thumb_name  = $image_name;
		$tmp_medium_name = $image_name;
				
		if (isset($_POST['apply_watermark']))
		{
			if ($this->prefs['gallery_create_thumb'] == 'y' AND $this->prefs['gallery_wm_apply_to_thumb'] == 'n')
			{
				$create_tmp_copy = TRUE;
				$tmp_thumb_name  = $temp_marker.$image_name;			
			}
			
			if ($this->prefs['gallery_create_medium'] == 'y' AND $this->prefs['gallery_wm_apply_to_medium'] == 'n')
			{
				$create_tmp_copy = TRUE;
				$tmp_medium_name = $temp_marker.$image_name;			
			}
			
			if ($create_tmp_copy == TRUE)
			{
				@copy($upload_path.$image_name, $upload_path.$temp_marker.$image_name);
			}
	
			/** --------------------------------
			/**  Apply Watermark to main image
			/** --------------------------------*/
		
			if ($this->prefs['gallery_wm_type'] != 'n')
			{		
				$res = $IM->set_properties(	
										array (
											'resize_protocol'		=> $this->prefs['gallery_image_protocal'],
											'libpath'				=> $this->prefs['gallery_image_lib_path'],
											'file_path'				=>	$upload_path,
											'file_name'				=>	$image_name,
											'wm_image_path'			=>	$this->prefs['gallery_wm_image_path'],	
											'wm_use_font'			=>	($this->prefs['gallery_wm_use_font'] == 'y') ? TRUE : FALSE,
											'dynamic_output'		=>	FALSE,
											'wm_font'				=>	$this->prefs['gallery_wm_font'],
											'wm_font_size'			=>	$this->prefs['gallery_wm_font_size'],	
											'wm_text_size'			=>	5,
											'wm_text'				=>	$this->prefs['gallery_wm_text'],
											'wm_vrt_alignment'		=>	$this->prefs['gallery_wm_vrt_alignment'],	
											'wm_hor_alignment'		=>	$this->prefs['gallery_wm_hor_alignment'],
											'wm_padding'			=>	$this->prefs['gallery_wm_padding'],
											'wm_x_offset'			=>	$this->prefs['gallery_wm_x_offset'],
											'wm_y_offset'			=>	$this->prefs['gallery_wm_y_offset'],
											'wm_x_transp'			=>	$this->prefs['gallery_wm_x_transp'],
											'wm_y_transp'			=>	$this->prefs['gallery_wm_y_transp'],
											'wm_text_color'			=>	$this->prefs['gallery_wm_text_color'],
											'wm_use_drop_shadow'	=>	($this->prefs['gallery_wm_use_drop_shadow']) ? TRUE : FALSE,
											'wm_shadow_color'		=>	$this->prefs['gallery_wm_shadow_color'],
											'wm_shadow_distance'	=>	$this->prefs['gallery_wm_shadow_distance'],
											'wm_opacity'			=>	$this->prefs['gallery_wm_opacity']
									  )
								);
				
				$type = ($this->prefs['gallery_wm_type'] == 't') ? 'text_watermark' : 'image_watermark';
													
				if ( ! $res)
				{
					return $IM->show_error();
				}
				if ( ! $IM->$type())
				{  
					return $IM->show_error();
				}			
			}
		}
		
		/** --------------------------------
		/**  Create the thumbnails
		/** --------------------------------*/
		
		if (isset($thumbs) AND count($thumbs) > 0)
		{
			foreach ($thumbs as $key => $val)
			{
				$res = $IM->set_properties(			
											array(
													'resize_protocol'	=> $this->prefs['gallery_image_protocal'],
													'libpath'			=> $this->prefs['gallery_image_lib_path'],
													'maintain_ratio'	=> ($this->prefs['gallery_maintain_ratio'] == 'y') ? TRUE : FALSE,
													'thumb_prefix'		=> $val['0'],
													'file_path'			=> $upload_path,
													'file_name'			=> ($key == 'thumb') ? $tmp_thumb_name : $tmp_medium_name,
													'new_file_name'		=> $image_name,
													'quality'			=> $val['3'],
													'dst_width'			=> $val['1'],
													'dst_height'		=> $val['2']
													)
											);
											
				if ($res === FALSE OR ! $IM->image_resize())
				{
					return $IM->show_error();
				}
				
				if ($key == 'thumb')
				{
					$t_width  = $IM->dst_width;
					$t_height = $IM->dst_height;
				}
				else
				{
					$m_width  = $IM->dst_width;
					$m_height = $IM->dst_height;
				}
				
				$IM->initialize();
			}		
		}

		/** --------------------------------
		/**  Remove the temporary image
		/** --------------------------------*/
		
		if ($create_tmp_copy == TRUE)
		{
			unlink($upload_path.$temp_marker.$image_name);
		}
												
		/** --------------------------------
		/**  Insert New Entry
		/** --------------------------------*/
		
		$x = explode(".", $image_name);
		$extension	= '.'.end($x);
		$filename	= str_replace($extension, '', $image_name);

		$data = array(
						'gallery_id'			=> $this->gallery_id,
						'cat_id'				=> $_POST['cat_id'],
						'author_id'				=> $_POST['author_id'],
						'filename'				=> $filename,
						'extension'				=> $extension,
						'title'					=> $_POST['title'],
						'caption'				=> $_POST['caption'],
						'status'				=> $_POST['status'],
						'views'					=> ( ! isset($_POST['views']) OR ! is_numeric($_POST['views'])) ? 0 : $_POST['views'],
						'width'					=> $width,
						'height'				=> $height,
						't_width'				=> $t_width,
						't_height'				=> $t_height,
						'm_width'				=> $m_width,
						'm_height'				=> $m_height,
						'entry_date'			=> $entry_date,
						'allow_comments'		=> ( ! isset($_POST['allow_comments'])) 	? 'n' : 'y',
						'custom_field_one'		=> ( ! isset($_POST['custom_field_one'])) 	? '' : $_POST['custom_field_one'],
						'custom_field_two'		=> ( ! isset($_POST['custom_field_two'])) 	? '' : $_POST['custom_field_two'],
						'custom_field_three'	=> ( ! isset($_POST['custom_field_three']))	? '' : $_POST['custom_field_three'],
						'custom_field_four'		=> ( ! isset($_POST['custom_field_four']))	? '' : $_POST['custom_field_four'],
						'custom_field_five'		=> ( ! isset($_POST['custom_field_five']))	? '' : $_POST['custom_field_five'],
						'custom_field_six'		=> ( ! isset($_POST['custom_field_six']))	? '' : $_POST['custom_field_six']
					);

		$this->EE->db->query($this->EE->db->insert_string('exp_gallery_entries', $data));
		$insert_id = $this->EE->db->insert_id();

		$this->update_cat_total($_POST['cat_id']);
				 
		if ($from == 'batch')
		{
			$row = ( ! $this->EE->input->get_post('row')) ? 0 : $this->EE->input->get_post('row');
			$row = $row+1;
			
			$allow_c = ( ! isset($_POST['allow_comments'])) ? 'n' : 'y';
			$apply_w = ( ! isset($_POST['apply_watermark'])) ? 'n' : 'y';
			
			$loc = BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=batch_entries'.AMP.'gallery_id='.$this->gallery_id.AMP.'row='.$row.AMP.'action=insert'.AMP.'deft_cat='.$_POST['cat_id'].AMP.'status='.$_POST['status'].AMP.'allow_comments='.$allow_c.AMP.'apply_watermark='.$apply_w.AMP.'filter_by='.$_POST['filter_by'];
	 	}
	 	else
	 	{
			$loc = BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=entry_form'.AMP.'gallery_id='.$this->gallery_id.AMP.'entry_id='.$insert_id.AMP.'action=insert';
		}
		 
		$this->EE->functions->redirect($loc);
		exit;		
	}

  
  
	/** ----------------------------------
	/**  Update the total number of entries in a category
	/** ----------------------------------*/
  
  	function update_cat_total($cat_id)
  	{
  		
  		if ( ! is_numeric($cat_id))
  			return;
  		
		$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_gallery_entries WHERE cat_id= '".$this->EE->db->escape_str($cat_id)."'");
		$tot = $query->row('count') ;
		
		$query = $this->EE->db->query("SELECT MAX(entry_date) AS max_date FROM exp_gallery_entries 
							 WHERE cat_id= '".$this->EE->db->escape_str($cat_id)."'");
		$date = ($query->num_rows() > 0 && is_numeric($query->row('max_date') )) ? $query->row('max_date')  : '0';
		
		$query = $this->EE->db->query("SELECT views FROM exp_gallery_entries WHERE cat_id= '".$this->EE->db->escape_str($cat_id)."'");
		
		$views = 0;
		
		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() as $row)
			{
				$views = $views + $row['views'];
			}
		}
		
		
		// Gather comment counts for inclusion in the update
		
		$query = $this->EE->db->query("SELECT comment_date FROM exp_gallery_comments egc, exp_gallery_entries ege WHERE egc.entry_id = ege.entry_id AND egc.status = 'o' AND ege.cat_id = '".$this->EE->db->escape_str($cat_id)."' ORDER BY egc.comment_date desc LIMIT 1");
		$comment_date = ($query->num_rows() == 0) ? 0 : $query->row('comment_date');

		$query = $this->EE->db->query("SELECT COUNT(egc.comment_id) AS count FROM exp_gallery_comments egc, exp_gallery_entries ege WHERE egc.entry_id = ege.entry_id AND ege.status = 'o' AND ege.cat_id = '".$this->EE->db->escape_str($cat_id)."'");
		$total_comments = $query->row('count');
		
		$this->EE->db->query("UPDATE exp_gallery_categories SET total_files = '{$tot}', total_views = '{$views}', recent_entry_date = '{$date}', total_comments = '{$total_comments}', recent_comment_date = '{$comment_date}' WHERE cat_id = '".$this->EE->db->escape_str($cat_id)."'");
  	}



	/** ----------------------------------
	/**  Update an existing entry
	/** ----------------------------------*/
	function update_entry()
	{
		if ( ! $entry_id = $this->EE->input->post('entry_id'))
		{
			return $this->EE->dsp->no_access_message();
		}
		
		if ( ! is_numeric($entry_id))
		{
			return FALSE;
		}

		/** --------------------------------
		/**  Error Trapping
		/** --------------------------------*/
		
		$error = array();
			
		if ( ! $title = $this->EE->input->post('title'))
		{
			$error[] = $this->EE->lang->line('gallery_missing_title');
		}
		
		if ( ! $cat_id = $this->EE->input->post('cat_id'))
		{
			$error[] = $this->EE->lang->line('gallery_missing_category');
		}
			
		if ( ! is_numeric($cat_id))
		{
			return FALSE;
		}
			
		if ( ! $this->EE->input->post('entry_date'))
		{
			$error[] = $this->EE->lang->line('gallery_missing_date');
		}
		
		// Convert the date to a Unix timestamp
		
		$entry_date = $this->EE->localize->convert_human_date_to_gmt($this->EE->input->post('entry_date'));
					 
		if ( ! is_numeric($entry_date)) 
		{ 
			$error[] = $this->EE->lang->line('gallery_invalid_date_formatting');
		}
		
		if (count($error) > 0)
		{
			return $this->EE->dsp->error_message($error);
		}

		/** ------------------------------------
		/**  Prep the file data
		/** -----------------------------------*/
		
		// First we'll fetch the currently stored filename and prep a few things
		
		// Separate the filename from the extension since we store these separately
		$x = explode(".", $_POST['filename']);
		$extension = '.'.end($x);
 		$filename = str_replace($extension, '', $_POST['filename']);
 		
 		// Fetch category folder name
		$result = $this->EE->db->query("SELECT cat_folder FROM exp_gallery_categories WHERE cat_id = '$cat_id' AND gallery_id = '".$this->gallery_id."'");
		$cat_folder = ($result->row('cat_folder')  != '') ? $result->row('cat_folder')  : '';
 		
 		// Fetch the old filename and extension
		
		$query = $this->EE->db->query("SELECT filename, extension FROM exp_gallery_entries WHERE entry_id = '$entry_id'");
		
		$old_filename	= $query->row('filename') ;
		$old_extension	= $query->row('extension') ;
		$old_fullname 	= $old_filename.$old_extension;
		$filepath 		= $this->EE->functions->remove_double_slashes($this->prefs['gallery_upload_path'].'/'.$cat_folder.'/');		
		
		
		/** ------------------------------------
		/**  Is the file being moved?
		/** -----------------------------------*/
		if ($cat_id != $_POST['old_cat'])
		{
			// Fetch old category folder
			$result = $this->EE->db->query("SELECT cat_folder FROM exp_gallery_categories WHERE cat_id = '".$this->EE->db->escape_str($_POST['old_cat'])."' AND gallery_id = '".$this->gallery_id."'");
			$old_cat_folder = ($result->row('cat_folder')  != '') ? $result->row('cat_folder')  : '';		
 			
 			// We only move the image if the old and new category folders are different
 			if ($cat_folder != $old_cat_folder)
 			{
				$oldpath = $this->EE->functions->remove_double_slashes($this->prefs['gallery_upload_path'].'/'.$old_cat_folder.'/');
	
				$prefixes = array('GXc94hURde6qpalzm543', $this->prefs['gallery_thumb_prefix'], $this->prefs['gallery_medium_prefix']);
	
				foreach ($prefixes as $prefix)
				{
					if ($prefix == '')
						continue;
				
					if ($prefix == 'GXc94hURde6qpalzm543')
						$prefix = '';
				
					$src = $oldpath.$old_filename.$prefix.$old_extension;
					$dst = $filepath.$old_filename.$prefix.$old_extension;
				
					if (file_exists($src))
					{
						if ( ! @copy($src, $dst))
						{
							return $this->EE->dsp->error_message(array($this->EE->lang->line("gallery_copy_error")));
						}
				
						@chmod($dst, FILE_WRITE_MODE);
						@unlink($src);
					}
				}
			}							
		}		
		
		/** ------------------------------------
		/**  Is the filename being changed?
		/** -----------------------------------*/
		if ($old_fullname != $_POST['filename'])
		{			
			/** ------------------------------------
			/**  Assign old and new file paths
			/** -----------------------------------*/
				
			$oldfile = $filepath.$old_fullname;
			$newfile = $filepath.$_POST['filename'];
				
			/** ---------------------------------------
			/**  If the new name already exists, bail...
			/** ---------------------------------------*/
			if (file_exists($newfile))
			{
				return $this->EE->dsp->error_message(array($this->EE->lang->line('gallery_file_exists')));
			}
				
			/** ---------------------------------------
			/**  If rename() failed issue an error
			/** ---------------------------------------*/
			
			if ( ! @rename($oldfile, $newfile))
			{
				return $this->EE->dsp->error_message(array($this->EE->lang->line('gallery_renaming_error')));
			}
			
			@chmod($newfile, FILE_WRITE_MODE);
				
			/** ---------------------------------------
			/**  Rename the thumbnails
			/** ---------------------------------------*/
			$thumbs = array();
			
			if ($this->prefs['gallery_create_thumb'] == 'y')
				$thumbs[] = $this->prefs['gallery_thumb_prefix'];
			
			if ($this->prefs['gallery_create_medium'] == 'y')
				$thumbs[] = $this->prefs['gallery_medium_prefix'];

			
			foreach ($thumbs as $val)
			{
				$old = $filepath.$old_filename.$val.$old_extension;
				$new = $filepath.$filename.$val.$extension;

				if (file_exists($old))
				{
					if (@rename($old, $new))
					{
						@chmod($new, FILE_WRITE_MODE);
					}
				}
			}
			
			unset($thumbs);
		}
		// END RENAMING

		/** --------------------------------
		/**  Compile Thumb data
		/** --------------------------------*/
			
		if ($this->prefs['gallery_create_thumb'] == 'y')
			$thumbs['gallery_wm_apply_to_thumb'] = array($this->prefs['gallery_thumb_prefix'],  $this->prefs['gallery_thumb_width'],  $this->prefs['gallery_thumb_height'], $this->prefs['gallery_thumb_quality']);
		
		if ($this->prefs['gallery_create_medium'] == 'y')
			$thumbs['gallery_wm_apply_to_medium'] = array($this->prefs['gallery_medium_prefix'], $this->prefs['gallery_medium_width'], $this->prefs['gallery_medium_height'], $this->prefs['gallery_medium_quality']);		

		/** --------------------------------
		/**  Invoke the Image Lib Class
		/** --------------------------------*/
		require APPPATH.'_to_be_replaced/lib.image_lib'.EXT;
		$IM = new Image_lib();
		
		// We'll recalculate the width/height in case something changed.
		// It's probably unnecessary but what the hay...
		
		$vals = $IM->get_image_properties($filepath.$filename.$extension, TRUE);
		
		$width		= $vals['width'];
		$height		= $vals['height'];
		$t_width	= 0;
		$t_height	= 0;
		$m_width	= 0;
		$m_height	= 0;
		
		/** --------------------------------
		/**  Do the thumbs require watermark?
		/** --------------------------------*/
		
		// We need to determine if ether the thumbnail
		// or the medium sized image require the watermark.
		// If the do, we will add the watermark to the full-sized
		// image and create our thumbs from it.  If not, we will
		// create a temporary copy of the full-sized image without
		// the watermark and use it as the basis for the thumbs.
		// Since the thumb and medium image can have separate prefs
		// we have to test for each individually.
		
		$image_name	 		= $filename.$extension;
		$temp_marker		 	= '58fdhCX9ZXd0guhh';
		$create_tmp_copy 	= FALSE;
		$tmp_thumb_name  	= $image_name;
		$tmp_medium_name 	= $image_name;
		
		$result = $this->EE->db->query("SELECT cat_folder FROM exp_gallery_categories WHERE cat_id = '$cat_id' AND gallery_id = '".$this->gallery_id."'");
		$cat_folder = ($result->row('cat_folder')  != '') ? $result->row('cat_folder')  : '';
						
		$upload_path = $this->EE->functions->remove_double_slashes($this->prefs['gallery_upload_path'].'/'.$cat_folder.'/');
		
		if (isset($_POST['apply_watermark']))
		{
			if ($this->prefs['gallery_create_thumb'] == 'y' AND $this->prefs['gallery_wm_apply_to_thumb'] == 'n')
			{
				$create_tmp_copy = TRUE;
				$tmp_thumb_name = $temp_marker.$image_name;			
			}

			if ($this->prefs['gallery_create_medium'] == 'y' AND $this->prefs['gallery_wm_apply_to_medium'] == 'n')
			{
				$create_tmp_copy = TRUE;
				$tmp_medium_name = $temp_marker.$image_name;			
			}
			
			if ($create_tmp_copy == TRUE)
			{
				@copy($upload_path.$image_name, $upload_path.$temp_marker.$image_name);
			}
		
			/** --------------------------------
			/**  Apply Watermark to main image
			/** --------------------------------*/
		
			if ($this->prefs['gallery_wm_type'] != 'n')
			{		
				$res = $IM->set_properties(	
										array (
											'resize_protocol'		=> $this->prefs['gallery_image_protocal'],
											'libpath'				=> $this->prefs['gallery_image_lib_path'],
											'file_path'				=>	$upload_path,
											'file_name'				=>	$image_name,
											'wm_image_path'			=>	$this->prefs['gallery_wm_image_path'],	
											'wm_use_font'			=>	($this->prefs['gallery_wm_use_font'] == 'y') ? TRUE : FALSE,
											'dynamic_output'		=>	FALSE,
											'wm_font'				=>	$this->prefs['gallery_wm_font'],
											'wm_font_size'			=>	$this->prefs['gallery_wm_font_size'],	
											'wm_text_size'			=>	5,
											'wm_text'				=>	$this->prefs['gallery_wm_text'],
											'wm_vrt_alignment'		=>	$this->prefs['gallery_wm_vrt_alignment'],	
											'wm_hor_alignment'		=>	$this->prefs['gallery_wm_hor_alignment'],
											'wm_padding'			=>	$this->prefs['gallery_wm_padding'],
											'wm_x_offset'			=>	$this->prefs['gallery_wm_x_offset'],
											'wm_y_offset'			=>	$this->prefs['gallery_wm_y_offset'],
											'wm_x_transp'			=>	$this->prefs['gallery_wm_x_transp'],
											'wm_y_transp'			=>	$this->prefs['gallery_wm_y_transp'],
											'wm_text_color'			=>	$this->prefs['gallery_wm_text_color'],
											'wm_use_drop_shadow'	=>	($this->prefs['gallery_wm_use_drop_shadow']) ? TRUE : FALSE,
											'wm_shadow_color'		=>	$this->prefs['gallery_wm_shadow_color'],
											'wm_shadow_distance'	=>	$this->prefs['gallery_wm_shadow_distance'],
											'wm_opacity'			=>	$this->prefs['gallery_wm_opacity']
									  )
								);
				
				$type = ($this->prefs['gallery_wm_type']	 == 't') ? 'text_watermark' : 'image_watermark';
													
				if ( ! $res)
				{
					return $IM->show_error();
				}
				if ( ! $IM->$type())
				{  
					return $IM->show_error();
				}			
			}
		}
		
		/** --------------------------------
		/**  Create the thumbnails
		/** --------------------------------*/
		
		if (isset($thumbs) AND count($thumbs) > 0)
		{
			foreach ($thumbs as $key => $val)
			{
				if ($this->prefs[$key] == 'y')
				{
					$res = $IM->set_properties(			
												array(
														'resize_protocol'	=> $this->prefs['gallery_image_protocal'],
														'libpath'			=> $this->prefs['gallery_image_lib_path'],
														'maintain_ratio'	=> ($this->prefs['gallery_maintain_ratio'] == 'y') ? TRUE : FALSE,
														'thumb_prefix'		=> $val['0'],
														'file_path'			=> $upload_path,
														'file_name'			=> ($key == 'gallery_wm_apply_to_thumb') ? $tmp_thumb_name : $tmp_medium_name,
														'new_file_name'		=> $image_name,
														'quality'			=> $val['3'],
														'dst_width'			=> $val['1'],
														'dst_height'		=> $val['2']
														)
												);
												
					if ($res === FALSE OR ! $IM->image_resize())
					{
						return $IM->show_error();
					}
					
					if ($key == 'thumb')
					{
						$t_width  = $IM->dst_width;
						$t_height = $IM->dst_height;
					}
					else
					{
						$m_width  = $IM->dst_width;
						$m_height = $IM->dst_height;
					}
					
					$IM->initialize();
				}
			}		
		}

		/** --------------------------------
		/**  Remove the temporary image
		/** --------------------------------*/
		
		if ($create_tmp_copy == TRUE)
		{
			@unlink($upload_path.$temp_marker.$image_name);
		}
				
				
		if (file_exists($filepath.$filename.$this->prefs['gallery_thumb_prefix'].$extension))
		{
			$vals = $IM->get_image_properties($filepath.$filename.$this->prefs['gallery_thumb_prefix'].$extension, TRUE);
			
			$t_width  	= $vals['width'];
			$t_height 	= $vals['height'];
		}

		if (file_exists($filepath.$filename.$this->prefs['gallery_medium_prefix'].$extension))
		{
			$vals = $IM->get_image_properties($filepath.$filename.$this->prefs['gallery_medium_prefix'].$extension, TRUE);
			
			$m_width  	= $vals['width'];
			$m_height 	= $vals['height'];
		}
		
		/** --------------------------------
		/**  Update Entry
		/** --------------------------------*/
		$data = array(
						'author_id'			=> $_POST['author_id'],
						'filename'			=> $filename,
						'extension'			=> $extension,
						'cat_id'			=> $cat_id,
						'title'				=> $_POST['title'],
						'caption'			=> $_POST['caption'],
						'width'				=> $width,
						'height'			=> $height,
						't_width'			=> $t_width,
						't_height'			=> $t_height,
						'm_width'			=> $m_width,
						'm_height'			=> $m_height,
						'status'			=> $_POST['status'],
						'views'				=> ( ! is_numeric($_POST['views'])) ? 0 : $_POST['views'],
						'entry_date'		=> $entry_date,
						'allow_comments'	=> ( ! isset($_POST['allow_comments'])) ? 'n' : 'y',
						'custom_field_one'		=> ( ! isset($_POST['custom_field_one'])) 	? '' : $_POST['custom_field_one'],
						'custom_field_two'		=> ( ! isset($_POST['custom_field_two'])) 	? '' : $_POST['custom_field_two'],
						'custom_field_three'	=> ( ! isset($_POST['custom_field_three']))	? '' : $_POST['custom_field_three'],
						'custom_field_four'		=> ( ! isset($_POST['custom_field_four']))	? '' : $_POST['custom_field_four'],
						'custom_field_five'		=> ( ! isset($_POST['custom_field_five']))	? '' : $_POST['custom_field_five'],
						'custom_field_six'		=> ( ! isset($_POST['custom_field_six']))	? '' : $_POST['custom_field_six']
					);


		$this->EE->db->query($this->EE->db->update_string('exp_gallery_entries', $data, "entry_id='$entry_id' AND gallery_id='$this->gallery_id'")); 
		
		// -------------------------------------------
		// 'gallery_cp_update_entry_end' hook.
		//  - Allows taking of entry id and doing extra actions on update
		//  - Added EE 1.6.0
		//
			$edata = $this->EE->extensions->call('gallery_cp_update_entry_end', $entry_id);
			if ($this->EE->extensions->end_script === TRUE) return;
		//
		// -------------------------------------------

		$this->update_cat_total($cat_id);
		
		if (isset($_POST['old_cat']) AND is_numeric($_POST['old_cat']))
		{
			if ($cat_id != $_POST['old_cat'])
			{
				$this->update_cat_total($_POST['old_cat']);	
			}		
		}
		
		/** --------------------------------------
		/**  Is this entry a child of another parent?
		/** --------------------------------------*/
				
		// If the entry being submitted is a "child" of channel entry parent
		// we need to re-compile and cache the data. 
		
		$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_relationships WHERE rel_type = 'gallery' AND rel_child_id = '".$this->EE->db->escape_str($entry_id)."'");
		
		if ($query->row('count')  > 0)
		{
			$reldata = array(
								'type'		=> 'gallery',
								'child_id'	=> $entry_id
							);
				
			$this->EE->functions->compile_relationship($reldata, FALSE);
		}
		
		
		$this->EE->functions->redirect(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=entry_form'.AMP.'gallery_id='.$this->gallery_id.AMP.'entry_id='.$entry_id.AMP.'action=update');
		exit;		
	}


	

	/** ------------------------------
	/**  Delete Entry Confirm
	/** ------------------------------*/
	function delete_entry_confirm()
	{
		if ( ! $cat_id = $this->EE->input->get_post('cat_id'))
		{
			return $this->EE->dsp->no_access_message();
		}
		
		if ( ! is_numeric($cat_id))
		{
			return FALSE;
		}
		
		$entries = array();
		foreach ($_POST['toggle'] as $key => $val)
		{		
			$entries[] = $this->EE->db->escape_str($val);
		}
		
		if (count($entries) == 0)
		{
			$this->EE->functions->redirect(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=view_files'.AMP.'gallery_id='.$this->gallery_id.AMP.'cat_id='.$cat_id);
			exit;
		}
		
		// Fetch Image Path
		
		$result = $this->EE->db->query("SELECT cat_folder FROM exp_gallery_categories WHERE cat_id = '$cat_id' AND gallery_id = '".$this->gallery_id."'");
		$cat_folder = ($result->row('cat_folder')  != '') ? $result->row('cat_folder')  : '';
		
		$gallery_image_url = $this->EE->functions->remove_double_slashes($this->prefs['gallery_image_url'].'/'.$cat_folder.'/');	
		$gallery_upload_path = $this->EE->functions->remove_double_slashes($this->prefs['gallery_upload_path'].'/'.$cat_folder.'/');
		
		require APPPATH.'_to_be_replaced/lib.image_lib'.EXT;
		
		$IM = new Image_lib();
	
		$query = $this->EE->db->query("SELECT entry_id, title, filename, extension FROM exp_gallery_entries WHERE entry_id IN ('".implode("','", $entries)."')");
		
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}
		
		$titles  = array();
		$images  = array();
		$entries = array();
		
		foreach($query->result_array() as $row)
		{
			$url 	= $this->EE->functions->remove_double_slashes($gallery_image_url.'/'.$row['filename'].$row['extension']);
			$path	= $this->EE->functions->remove_double_slashes($gallery_upload_path.'/'.$row['filename'].$row['extension']);
				
			$IM->get_image_properties($path);
			
			$props = array(
							'width' 		=> $IM->src_width,
							'height' 		=> $IM->src_height,
							'new_width'		=> ($IM->src_width > 500) ? 500 : $IM->src_width,
							'new_height'	=> ''
						  );
				
			$s = $IM->size_calculator($props);
	
			$titles[$row['entry_id']] = $row['title'];
			$images[$row['entry_id']] = "<img src='{$url}' width='".$s['new_width']."' height='".$s['new_height']."' border='0' title='' />";
											
			$entries[] = $row['entry_id'];
		}
		
		$js = '';
		
		if (count($images) > 1)
		{
			$js = <<<EOT

<script type="text/javascript"> 
<!--

function select_all_images(thebutton)
{
	if (thebutton.checked) 
	{
		val = true;
	}
	else
	{
		val = false;
	}
				
	var len = document.delete_entries_confirm.elements.length;

	for (var i = 0; i < len; i++) 
	{
		var button = document.delete_entries_confirm.elements[i];
		
		var name_array = button.name.split("["); 
		
		if (name_array[0] == "delete_file") 
		{
			button.checked = val;
		}
	}
	
	document.delete_entries_confirm.delete_all_files.checked = val;
}

//-->
</script>
EOT;
		}
		
		
		$r = $js.
			 $this->EE->dsp->form_open(array('action' => 'C=modules'.AMP.'M=gallery'.AMP.'P=delete_entry', 'name' => 'delete_entries_confirm'),
			 				 array('gallery_id'		=> $this->gallery_id,
			 				 		'cat_id'			=> $cat_id,
			 				 		'entry_ids'		=> implode('|', $entries))
			 				 )
			 .	$this->EE->dsp->heading($this->EE->dsp->qspan('alert', (count($entries) == 1) ? $this->EE->lang->line('gallery_delete_entry') : $this->EE->lang->line('gallery_delete_entries')))
			 .	$this->EE->dsp->div('box')
			 .		$this->EE->dsp->qdiv('itemWrapper', '<b>'.((count($entries) == 1) ? $this->EE->lang->line('gallery_delete_entry_confirmation') : $this->EE->lang->line('gallery_delete_entries_confirmation')).'</b>')
			 .		$this->EE->dsp->qdiv('itemWrapper', '<i>'.implode(', ', $titles).'</i>')
			 .		$this->EE->dsp->qdiv('alert', BR.$this->EE->lang->line('action_can_not_be_undone')).BR
			 .		$this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->input_submit($this->EE->lang->line('delete')));
			 
		if (count($images) > 1)
		{
			$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->input_checkbox('delete_all_files', 'y', '', 'onclick="select_all_images(this)"').' '.$this->EE->dsp->qspan('defaultBold', $this->EE->lang->line('gallery_delete_all_files')));
		}
			 
			 
		foreach($images as $entry_id => $img)
		{
			$r .=	$this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->input_checkbox('delete_file['.$entry_id.']', 'y').' '.$this->EE->lang->line('gallery_delete_file')).
					$this->EE->dsp->qdiv('', $img).BR;
		}
			 
			 
		$r .= $this->EE->dsp->div_c().$this->EE->dsp->form_close();
			
		$title = $this->EE->lang->line('gallery_view_category');		
		$crumb = $this->EE->dsp->crumb_item($this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=category_manager'.AMP.'gallery_id='.$this->gallery_id, $this->EE->lang->line('gallery_categories'))).$this->EE->dsp->crumb_item($this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=view_files'.AMP.'gallery_id='.$this->gallery_id.AMP.'cat_id='.$cat_id, $this->EE->lang->line('gallery_view_category'))).$this->EE->dsp->crumb_item((count($entries) == 1) ? $this->EE->lang->line('gallery_delete_entry') : $this->EE->lang->line('gallery_delete_entries'));

		return $this->content_wrapper($title, $crumb, $r);
	}




	/** ------------------------------
	/**  Delete Entry
	/** ------------------------------*/
	function delete_entry()
	{
		if ( ! $entry_ids = $this->EE->input->get_post('entry_ids'))
		{
			return $this->EE->dsp->no_access_message();
		}
		
		if ( ! $cat_id = $this->EE->input->get_post('cat_id'))
		{
			return $this->EE->dsp->no_access_message();
		}
		
		$IDS = array();
		
		foreach(explode('|', $entry_ids) as $id)
		{
			$IDS[] = $this->EE->db->escape_str($id);
		}
		
		// Fetch Entry
		
		$query = $this->EE->db->query("SELECT entry_id, filename, extension, cat_id 
							 FROM exp_gallery_entries 
							 WHERE entry_id IN ('".implode("','", $IDS)."') AND gallery_id = '".$this->gallery_id."'" );
							 
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		$result = $this->EE->db->query("SELECT cat_folder FROM exp_gallery_categories WHERE cat_id = '$cat_id' AND gallery_id = '".$this->gallery_id."'");
		$cat_folder = ($result->row('cat_folder')  != '') ? $result->row('cat_folder')  : '';
		
		$prefixes = array('', $this->prefs['gallery_thumb_prefix'], $this->prefs['gallery_medium_prefix']);

		foreach($query->result_array() as $row)
		{
			/** --------------------------------
			/**  Are we deleting the file?
			/** --------------------------------*/
			if (isset($_POST['delete_file'][$row['entry_id']]))
			{
				foreach ($prefixes as $prefix)
				{			
					@unlink($this->EE->functions->remove_double_slashes($this->prefs['gallery_upload_path'].'/'.$cat_folder.'/').
							$row['filename'].
							$prefix.
							$row['extension']);
				}
			}
		}
		
		$this->EE->db->query("DELETE FROM exp_gallery_comments WHERE entry_id IN ('".implode("','", $IDS)."')");
				
		$this->EE->db->query("DELETE FROM exp_gallery_entries WHERE entry_id IN ('".implode("','", $IDS)."') AND gallery_id='{$this->gallery_id}'");
	
		$this->update_cat_total($cat_id);
	
		$this->EE->functions->redirect(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=view_files'.AMP.'gallery_id='.$this->gallery_id.AMP.'cat_id='.$cat_id.AMP.'action=delete');
		exit;
	}

  
  

	/** ------------------------------
	/**  View Files
	/** ------------------------------*/
	function view_files()
	{
		if ( ! $cat_id = $this->EE->input->get_post('cat_id'))
		{
			return FALSE;
		}
		
		if ( ! is_numeric($cat_id))
		{
			return FALSE;
		}

		/** ------------------------------
		/**  Set Title and Breadcrumb
		/** ------------------------------*/
		
		$title = $this->EE->lang->line('gallery_view_category');		
		$crumb = $this->EE->dsp->crumb_item($this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=view_entries'.AMP.'gallery_id='.$this->gallery_id.AMP.'mode=view', $this->EE->lang->line('gallery_view_entries'))).$this->EE->dsp->crumb_item($this->EE->lang->line('gallery_files'));
		
		/** ------------------------------------
		/**  Have categories been set up yet?
		/** ------------------------------------*/
					
		$this->category_tree('text', $this->prefs['gallery_sort_order'], $cat_id);
		
		if (count($this->categories) == 0)
		{
			return $this->EE->dsp->qdiv('box', $this->EE->dsp->qdiv('highlight', $this->EE->lang->line('gallery_categories_required').BR.BR).$this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=edit_category'.AMP.'gallery_id='.$this->gallery_id, $this->EE->lang->line('gallery_new_category'))));
		}		
		
		/** ------------------------------
		/**  Fetch Preferences
		/** ------------------------------*/
		
		$result = $this->EE->db->query("SELECT cat_folder FROM exp_gallery_categories WHERE cat_id = '$cat_id' AND gallery_id = '".$this->gallery_id."'");
		$cat_folder = ($result->row('cat_folder')  != '') ? $result->row('cat_folder')  : '';

		
		$url  = $this->EE->functions->remove_double_slashes($this->prefs['gallery_image_url'].'/'.$cat_folder.'/');
		$path = $this->EE->functions->remove_double_slashes($this->prefs['gallery_upload_path'].'/'.$cat_folder.'/');
		
		$t_prefix = $this->prefs['gallery_thumb_prefix'];
		$m_prefix = $this->prefs['gallery_medium_prefix'];
				
		/** ------------------------------
		/**  Fetch the Entries
		/** ------------------------------*/
		// We need this in a variable for pagination later
		$sql = "SELECT filename, extension, entry_id, entry_date, title, gallery_id, total_comments, status FROM exp_gallery_entries WHERE cat_id = '$cat_id' AND gallery_id = '{$this->gallery_id}' ORDER BY entry_date desc";		
		
		$query = $this->EE->db->query($sql);

		/** -----------------------------
			/**  Do we need pagination?
			/** -----------------------------*/
		
		$paginate = '';
		
		if ($query->num_rows() > $this->row_limit)
		{ 
			$row_count = ( ! $this->EE->input->get_post('row')) ? 0 : $this->EE->input->get_post('row');
						
			$base_url = BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=view_files'.AMP.'cat_id='.$cat_id.AMP.'gallery_id='.$this->gallery_id;
						
			$paginate = $this->EE->dsp->pager(  $base_url,
									  $query->num_rows, 
									  $this->row_limit,
									  $row_count,
									  'row'
									);
			 
			$sql .= " LIMIT ".$row_count.", ".$this->row_limit;
			
			$query = $this->EE->db->query($sql);	
		}
				
		$date_fmt = ($this->EE->session->userdata('time_format') != '') ? $this->EE->session->userdata('time_format') : $this->EE->config->item('time_format');

		if ($date_fmt == 'us')
		{
			$datestr = '%m/%d/%y %h:%i %a';
		}
		else
		{
			$datestr = '%Y-%m-%d %H:%i';
		}

		/** ------------------------------
		/**  Build the output
		/** ------------------------------*/
		$r = '';
		
		// This message is shown when entries are deleted
		
		if ($this->EE->input->get_post('action') == 'delete')
		{
			$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('successBox', $this->EE->dsp->qdiv('success', $this->EE->lang->line('gallery_entry_deleted'))));						 
		}
		elseif (isset($this->EE->lang->language['action_'.$this->EE->input->get_post('action')]))
		{
			$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('successBox', $this->EE->dsp->qdiv('success', $this->EE->lang->line('action_'.$this->EE->input->get_post('action')))));
		}
		
		// If there are no categories yet we'll show an error message

		if ($query->num_rows() == 0)
		{
			$r .= $this->EE->dsp->qdiv('tableHeading', $this->EE->lang->line('gallery_view_category'));
			$r .= $this->EE->dsp->qdiv('box', $this->EE->dsp->qdiv('highlight', $this->EE->lang->line('gallery_no_entries_in_cat')));
			return $this->content_wrapper($title, $crumb, $r);
		}  
		
		/** ------------------------------
		/**  Instantiate Upload Class
		/** ------------------------------*/
		
		// We'll use this in a moment

		require APPPATH.'_to_be_replaced/lib.image_lib'.EXT;
		$IM = new Image_lib();
		
		/** ------------------------------
		/**  Our Form for Deletes
		/** ------------------------------*/
		
		//$this->EE->dsp->body_props .= ' onload="magic_check()" ';
		
		$r .=	$this->EE->dsp->toggle().
				//$this->EE->dsp->magic_checkboxes().
				$this->EE->dsp->form_open(
								array(
										'action' => 'C=modules'.AMP.'M=gallery'.AMP.'P=multi_edit_entries', 
										'name'	=> 'target',
										'id'	=> 'target',
									),
								array('gallery_id'	=> $this->gallery_id,
									  'cat_id'		=> $cat_id)
							);

		/** ------------------------------
		/**  Table Header
		/** ------------------------------*/
		$r .= $this->EE->dsp->table('tableBorder', '0', '10', '100%').
			  $this->EE->dsp->tr().
			  $this->EE->dsp->td('tableHeading', '', '').NBS.$this->EE->dsp->td_c().
			  $this->EE->dsp->td('tableHeading', '', '').$this->EE->lang->line('gallery_entry_tit').$this->EE->dsp->td_c().
			  $this->EE->dsp->td('tableHeading', '', '').$this->EE->lang->line('gallery_comments').$this->EE->dsp->td_c().
			  $this->EE->dsp->td('tableHeading', '', '').$this->EE->lang->line('gallery_entry_date').$this->EE->dsp->td_c().
			  $this->EE->dsp->td('tableHeading', '', '').$this->EE->lang->line('gallery_filename').$this->EE->dsp->td_c().
			  $this->EE->dsp->td('tableHeading', '', '').$this->EE->lang->line('gallery_status').$this->EE->dsp->td_c().
			  $this->EE->dsp->td('tableHeading', '', '').$this->EE->dsp->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"").$this->EE->dsp->td_c().
			  $this->EE->dsp->tr_c();

		/** ------------------------------
		/**  Table Rows
		/** ------------------------------*/
		$i = 0;
	
		foreach ($query->result_array() as $row)
		{		
			if (file_exists($path.$row['filename'].$t_prefix.$row['extension']))
			{
				$nurl  = $url.$row['filename'].$t_prefix.$row['extension'];	
				$npath = $path.$row['filename'].$t_prefix.$row['extension'];	
			}
			elseif (file_exists($path.$row['filename'].$m_prefix.$row['extension']))
			{
				$nurl  = $url.$row['filename'].$m_prefix.$row['extension'];			
				$npath = $path.$row['filename'].$m_prefix.$row['extension'];			
			}
			else
			{
				$nurl  = $url.$row['filename'].$row['extension'];			
				$npath = $path.$row['filename'].$row['extension'];			
			}			
			
			$IM->get_image_properties($npath);
						
			$props = array(
							'width' 		=> $IM->src_width,
							'height' 		=> $IM->src_height,
							'new_width'		=> '',
							'new_height'	=> 40
						  );
				
			$s = $IM->size_calculator($props);
													
			$img = "<img src='{$nurl}' width='".$s['new_width']."' height='".$s['new_height']."' border='0' title='".$row['filename'].$row['extension']."' />";
			
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			
			$r .=  $this->EE->dsp->tr();
			$r .=  $this->EE->dsp->table_qcell('galleryThumbView', $this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=entry_form'.AMP.'gallery_id='.$row['gallery_id'].AMP.'entry_id='.$row['entry_id'], $img), '15%');	  
			$r .=  $this->EE->dsp->table_qcell($style, NBS.NBS.$this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=entry_form'.AMP.'gallery_id='.$row['gallery_id'].AMP.'entry_id='.$row['entry_id'], '<b>'.$row['title'].'</b>'), '27%');	  
			$r .=  $this->EE->dsp->table_qcell($style, NBS.NBS.'('.$row['total_comments'].') '.$this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=view_comments'.AMP.'gallery_id='.$row['gallery_id'].AMP.'entry_id='.$row['entry_id'].AMP.'cat_id='.$cat_id, $this->EE->lang->line('gallery_view_comments')), '15%');	  
			$r .=  $this->EE->dsp->table_qcell($style, $this->EE->localize->decode_date($datestr, $row['entry_date'], TRUE), '15%');	  
			$r .=  $this->EE->dsp->table_qcell($style, $this->EE->dsp->qdiv('highlight', $row['filename'].$row['extension']), '10%');			
			$r .=  $this->EE->dsp->table_qcell($style, $this->EE->dsp->qdiv('highlight', ($row['status'] == 'o') ? $this->EE->lang->line('gallery_open') : $this->EE->lang->line('gallery_closed')), '8%');			
			$r .=  $this->EE->dsp->table_qcell($style, $this->EE->dsp->input_checkbox('toggle[]', $row['entry_id'], '' , ' id="delete_box_'.$row['entry_id'].'"'), '10%');	  
			$r .=  $this->EE->dsp->tr_c();
		}
		
		$r .=  $this->EE->dsp->table_c();
		
		$r .= $this->EE->dsp->table('', '0', '', '100%');
		$r .= $this->EE->dsp->tr().
			  $this->EE->dsp->td();
		
		// Pagination
		
		if ($paginate != '')
		{
			$r .= $this->EE->dsp->qdiv('crumblinks', $this->EE->dsp->qdiv('itemWrapper', $paginate));
		}
		
		$r .= $this->EE->dsp->td_c().
			  $this->EE->dsp->td('defaultRight');
		
		
		// Actions and submit button
		
		$r .= $this->EE->dsp->div('itemWrapper');
		
		$r .= $this->EE->dsp->input_submit($this->EE->lang->line('submit'));
		
		$r .= NBS.$this->EE->dsp->input_select_header('action').
			  $this->EE->dsp->input_select_option('delete', $this->EE->lang->line('delete_selected')).
			  $this->EE->dsp->input_select_option('null', '--').
			  $this->EE->dsp->input_select_option('close', $this->EE->lang->line('close_selected')).
			  $this->EE->dsp->input_select_option('open', $this->EE->lang->line('open_selected')).
			  $this->EE->dsp->input_select_option('null', '--').
			  $this->EE->dsp->input_select_option('disallow_comments', $this->EE->lang->line('disallow_comments')).
			  $this->EE->dsp->input_select_option('allow_comments', $this->EE->lang->line('allow_comments')).
			  $this->EE->dsp->input_select_footer();
			  
		$r .= $this->EE->dsp->div_c();
		
		$r .= $this->EE->dsp->td_c().
			  $this->EE->dsp->tr_c().
			  $this->EE->dsp->table_c();
		
		$r .= $this->EE->dsp->form_close();
		
		return $this->content_wrapper($title, $crumb, $r);
	}

	
	
	/** -----------------------------
	/**  Edit Multiple Comments
	/** -----------------------------*/
	
	function multi_edit_entries()
	{
		if ( ! $cat_id = $this->EE->input->get_post('cat_id'))
		{
			return $this->EE->dsp->no_access_message();
		}
		
		if ( ! is_numeric($cat_id))
		{
			return FALSE;
		}
		
		if ($this->EE->input->get_post('action') == 'delete')
		{
			return $this->delete_entry_confirm();
		}
		
		$entries = array();
		foreach ($_POST['toggle'] as $key => $val)
		{		
			$entries[] = $this->EE->db->escape_str($val);
		}
		
		if (count($entries) == 0)
		{
			$this->EE->functions->redirect(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=view_files'.AMP.'gallery_id='.$this->gallery_id.AMP.'cat_id='.$cat_id);
			exit;
		}
		
		$action = $this->EE->input->get_post('action');
		
		if ($this->EE->input->get_post('action') == 'open')
		{
			$this->EE->db->query("UPDATE exp_gallery_entries SET status = 'o' 
						WHERE entry_id IN ('".implode("','", $entries)."')
						AND cat_id = '".$this->EE->db->escape_str($cat_id)."'");
		}
		elseif ($this->EE->input->get_post('action') == 'close')
		{
			$this->EE->db->query("UPDATE exp_gallery_entries SET status = 'c' 
						WHERE entry_id IN ('".implode("','", $entries)."')
						AND cat_id = '".$this->EE->db->escape_str($cat_id)."'");
		}
		elseif ($this->EE->input->get_post('action') == 'disallow_comments')
		{
			$this->EE->db->query("UPDATE exp_gallery_entries SET allow_comments = 'n' 
						WHERE entry_id IN ('".implode("','", $entries)."')
						AND cat_id = '".$this->EE->db->escape_str($cat_id)."'");
		}
		elseif ($this->EE->input->get_post('action') == 'allow_comments')
		{
			$this->EE->db->query("UPDATE exp_gallery_entries SET allow_comments = 'y' 
						WHERE entry_id IN ('".implode("','", $entries)."')
						AND cat_id = '".$this->EE->db->escape_str($cat_id)."'");
		}
		else
		{
			$action = '';
		}
		
		$this->update_cat_total($cat_id);
	
		$this->EE->functions->redirect(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=view_files'.AMP.'gallery_id='.$this->gallery_id.AMP.'cat_id='.$cat_id.AMP.'action='.$action);
		exit;
	}

	


	/** ----------------------------------
	/**  Auto-Rename File
	/** ----------------------------------*/
	
	// This function determines if a file exists.
	// If so, it'll append a number to the filename
	// and call itself again.  It does this as many
	// times as necessary until a filename is clear.
	
	function rename_file($path, $name, $i = 0)
	{
		if (file_exists($path.$name))
		{	
			$xy = explode(".", $name);
			$ext = end($xy);
			
			$name = str_replace('.'.$ext, '', $name);
					
			if (substr($name, - strlen($i)) == $i)	
			{
				$name = substr($name, 0, -strlen($i));
			}
			
			$i++;

			$name .= $i.'.'.$ext;

			return $this->rename_file($path, $name, $i);
		}
		
		if (strlen($name) > 100)
        {
			if ( ! isset($ext))
			{
				$xy = explode(".", $name);
				$ext = end($xy);
			}
			
            $name = $this->UP->limit_filename_length($name, 100-strlen($i));
            $name = str_replace('.'.$ext, $i.'.'.$ext, $name);
            
            return $this->rename_file($path, $name, $i);            
        }

		return $name;
	}

	


	/** ------------------------------------------------
	/**  Category Manager (main category page)
	/** -------------------------------------------------*/
	function category_manager($action = '')
	{
		$r = '';
											
		if ($action == 'update')
		{
				$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('successBox', $this->EE->dsp->qdiv('success', NBS.NBS.$this->EE->lang->line('gallery_category_updated'))));
		}
		elseif ($action == 'insert')
		{
				$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('successBox', $this->EE->dsp->qdiv('success', NBS.NBS.$this->EE->lang->line('gallery_category_created'))));
		}
		
		// Set Mode
		
		$editmode = ($this->EE->input->get_post('mode') == 'view') ? FALSE : TRUE;		
 	
 		if ($editmode)
		$this->EE->dsp->right_crumb($this->EE->lang->line('gallery_new_category'), BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=edit_category'.AMP.'gallery_id='.$this->gallery_id);
 			
		$title = ($editmode) ? $this->EE->lang->line('gallery_categories') : $this->EE->lang->line('gallery_view_entries');
		$crumb = ($editmode) ? $this->EE->dsp->crumb_item($this->EE->lang->line('gallery_categories')) : $this->EE->dsp->crumb_item($this->EE->lang->line('gallery_view_entries')) ; 
 	
		/** ------------------------------------
		/**  Have categories been set up yet?
		/** ------------------------------------*/
		
		$this->category_tree('table', $this->prefs['gallery_sort_order'], '', $editmode);

		if (count($this->categories) == 0)
		{
			$warn = ($editmode) ? $this->EE->lang->line('gallery_no_categories') : $this->EE->lang->line('gallery_no_entries');
			
			$link = ($editmode) ? $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=edit_category'.AMP.'gallery_id='.$this->gallery_id, $this->EE->lang->line('gallery_new_category'))) : '';
			
			$r = $this->EE->dsp->qdiv('box', $this->EE->dsp->qdiv('highlight', $warn.BR.BR).$link);
			
			return $this->content_wrapper($title, $crumb, $r);
		}
		else
		{	
			$r .= $this->EE->dsp->table('tableBorder', '0', '0').
				  $this->EE->dsp->tr().
				  $this->EE->dsp->table_qcell('tableHeading', 'ID', '34px');
				  
			if ($editmode)
			{
				$r .= $this->EE->dsp->table_qcell('tableHeading', $this->EE->lang->line('gallery_cat_order'), '65px');
			}
				
			$r .= $this->EE->dsp->table_qcell('tableHeading', ($editmode) ? $this->EE->lang->line('gallery_entry_cat') : $this->EE->lang->line('gallery_categories'), ($editmode) ? '45%' : '50%').	
				  $this->EE->dsp->table_qcell('tableHeading', $this->EE->lang->line('gallery_files'), ($editmode) ? '12%' : '45%');
			
			if ($editmode)
			{
				$r .= $this->EE->dsp->table_qcell('tableHeading', $this->EE->lang->line('edit'), '12%').				  
				  	$this->EE->dsp->table_qcell('tableHeading', $this->EE->lang->line('delete'), '12%');
			}
			
			$r .= $this->EE->dsp->tr_c();
								
			foreach ($this->categories as $val)
			{			
				$r .= $val;
			}
			
			$r .= $this->EE->dsp->table_c();
						
			if ($editmode)
			{
				$r .= $this->EE->dsp->div('box');
				$r .= $this->EE->dsp->form_open(array('action' => 'C=modules'.AMP.'M=gallery'.AMP.'P=global_cat_order'.AMP.'gallery_id='.$this->gallery_id));
				$r .= $this->EE->dsp->div('bigPad');	
				$r .= $this->EE->dsp->qspan('defaultBold', $this->EE->lang->line('gallery_sort_order')).NBS;
				$r .= $this->EE->dsp->input_radio('sort_order', 'a', ($this->prefs['gallery_sort_order'] == 'a') ? 1 : '').NBS.$this->EE->lang->line('gallery_alpha').NBS.NBS;
				$r .= $this->EE->dsp->input_radio('sort_order', 'c', ($this->prefs['gallery_sort_order'] != 'a') ? 1 : '').NBS.$this->EE->lang->line('gallery_custom');			
				$r .= NBS.NBS.$this->EE->dsp->input_submit($this->EE->lang->line('update'));			
				$r .= $this->EE->dsp->div_c();
				$r .= $this->EE->dsp->div_c();
				$r .= $this->EE->dsp->form_close();
			}			
		}
			  
		// Assign output data	 
		$this->content_wrapper($title, $crumb, $r);
	}

	

	
	/** ------------------------------
	/**  Category tree
	/** ------------------------------*/
	function category_tree($type = 'table', $sort_order = 'a', $sel_id = '', $editmode = TRUE)
	{
		// Fetch category groups
				
		$sql = "SELECT cat_name, cat_id, parent_id, is_default FROM exp_gallery_categories WHERE gallery_id = '{$this->gallery_id}' ";
		$sql .= ($sort_order == 'a') ? "ORDER BY parent_id, cat_name" : "ORDER BY parent_id, cat_order";
		$query = $this->EE->db->query($sql);
			  
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}	 
		
		// Assign the query result to a multi-dimensional array
					
		foreach($query->result_array() as $row)
		{		
			$cat_array[$row['cat_id']]  = array($row['parent_id'], $row['cat_name'], $row['is_default']);
		}
				
		$up		= '<img src="'.PATH_CP_GBL_IMG.'arrow_up.gif" border="0"  width="16" height="16" alt="" title="" />';
		$down	= '<img src="'.PATH_CP_GBL_IMG.'arrow_down.gif" border="0"  width="16" height="16" alt="" title="" />';

		// Build our output...
				 
		foreach($cat_array as $key => $val) 
		{		
			if (0 == $val['0']) 
			{
				if ($type == 'table')
				{
					$res = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_gallery_entries WHERE cat_id= '$key'");
					$total = $res->row('count') ;
							
					if ($editmode == FALSE)
					{
						$tablearray = array($key,
											$this->EE->dsp->qdiv('defaultBold', NBS.$this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=view_files'.AMP.'cat_id='.$key.AMP.'gallery_id='.$this->gallery_id, $val['1'])),			  								
											$this->EE->dsp->qdiv('', $total));
					}
					else
					{
						$tablearray = array($key,
											$this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=cat_order'.AMP.'cat_id='.$key.AMP.'gallery_id='.$this->gallery_id.AMP.'order=up', $up).NBS.
											$this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=cat_order'.AMP.'cat_id='.$key.AMP.'gallery_id='.$this->gallery_id.AMP.'order=down', $down),
											$this->EE->dsp->qdiv('defaultBold', NBS.$val['1']),			  								
											$this->EE->dsp->qdiv('', $total),
											$this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=edit_category'.AMP.'cat_id='.$key.AMP.'gallery_id='.$this->gallery_id, $this->EE->lang->line('edit')),			  								
											$this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=del_category_conf'.AMP.'cat_id='.$key.AMP.'gallery_id='.$this->gallery_id, $this->EE->lang->line('delete')));					
					}
				
					$this->categories[] = $this->EE->dsp->table_qrow('tableCellTwo', $tablearray);	
				}
				elseif ($type == 'raw')
				{
					$res = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_gallery_entries WHERE cat_id= '$key'");
					$total = $res->row('count') ;
					$this->categories[] = array($total, $this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=view_files'.AMP.'cat_id='.$key.AMP.'gallery_id='.$this->gallery_id, $val['1']));
				}
				else
				{
					$this->categories[] = $this->EE->dsp->input_select_option($key, $val['1'], ($sel_id == $key) ? 1 : 0);
				}					
					
				$this->category_subtree($key, $cat_array, $depth=0, $type, $sel_id, $editmode);
			}
		} 
	}

	
	
	
	/** --------------------------------------
	/**  Category sub-tree
	/** --------------------------------------*/
		
	function category_subtree($cat_id, $cat_array, $depth, $type, $sel_id, $editmode = TRUE)
	{
		if ($type == 'table' OR $type == 'raw')
		{
			$spcr = '<img src="'.PATH_CP_GBL_IMG.'clear.gif" border="0"  width="24" height="14" alt="" title="" />';
			$indent = $spcr.'<img src="'.PATH_CP_GBL_IMG.'cat_marker.gif" border="0"  width="18" height="14" alt="" title="" />';
		}
		else
		{	
			$spcr = '&nbsp;';
			$indent = $spcr.$spcr.$spcr.$spcr;
		}

		$up		= '<img src="'.PATH_CP_GBL_IMG.'arrow_up.gif" border="0"  width="17" height="14" alt="" title="" />';
		$down	= '<img src="'.PATH_CP_GBL_IMG.'arrow_down.gif" border="0"  width="17" height="14" alt="" title="" />';
		
	
		if ($depth == 0)	
		{
			$depth = 1;
		}
		else 
		{								
			$indent = str_repeat($spcr, $depth+1).$indent;
			$depth = ($type == 'table' OR $type == 'raw') ? $depth + 1 : $depth + 4;
		}
				
		foreach ($cat_array as $key => $val) 
		{				
			if ($cat_id == $val['0']) 
			{
				$pre = ($depth > 2) ? "&nbsp;" : '';
				  
				if ($type == 'table')
				{
					$res = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_gallery_entries WHERE cat_id= '$key'");
					$total = $res->row('count') ; 
					
					if ($editmode == FALSE)
					{
						$tablearray = array($key,
											$this->EE->dsp->qdiv('defaultBold', $pre.$indent.NBS.$this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=view_files'.AMP.'cat_id='.$key.AMP.'gallery_id='.$this->gallery_id, $val['1'])),	 								
											$this->EE->dsp->qdiv('', $total));
					}
					else
					{
						$tablearray = array($key,
											$this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=cat_order'.AMP.'cat_id='.$key.AMP.'gallery_id='.$this->gallery_id.AMP.'order=up', $up).NBS.
											$this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=cat_order'.AMP.'cat_id='.$key.AMP.'gallery_id='.$this->gallery_id.AMP.'order=down', $down),
											$this->EE->dsp->qdiv('defaultBold', $pre.$indent.NBS.$val['1']),	 								
											$this->EE->dsp->qdiv('', $total),
											$this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=edit_category'.AMP.'cat_id='.$key.AMP.'gallery_id='.$this->gallery_id, $this->EE->lang->line('edit')),
											$this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=del_category_conf'.AMP.'cat_id='.$key.AMP.'gallery_id='.$this->gallery_id, $this->EE->lang->line('delete')));
					}
				
					$this->categories[] = $this->EE->dsp->table_qrow( 'tableCellTwo', $tablearray);						
				}
				elseif ($type == 'raw')
				{
					$res = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_gallery_entries WHERE cat_id= '$key'");
					$total = $res->row('count') ;
					$this->categories[] = array($total, $pre.$indent.NBS.$this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=view_files'.AMP.'cat_id='.$key.AMP.'gallery_id='.$this->gallery_id, $val['1']));
				}
				else
				{
					$selected = 0;
				
					if ($sel_id == '')
					{
						if ($val['2'] == 'y')
						{
							$selected = 1;
						}
					}
					else
					{
						if ($sel_id == $key)
						{
							$selected = 1;
						}
					}
				
						$this->categories[] = $this->EE->dsp->input_select_option($key, $pre.$indent.NBS.$val['1'], $selected);
				}
		
				$this->category_subtree($key, $cat_array, $depth, $type, $sel_id, $editmode);	
			}
		}
	}




	/** --------------------------------------
	/**  New / Edit category form
	/** --------------------------------------*/
	function category_form()
	{
		$cat_id = $this->EE->input->get_post('cat_id');

		$default = array('cat_name', 'cat_description', 'cat_folder', 'cat_id', 'parent_id', 'is_default');
	
			if ($cat_id)
 		{
			if ( ! is_numeric($cat_id))
			{
				return FALSE;
			}
 		
			$query = $this->EE->db->query("SELECT cat_id, cat_name, cat_description, cat_folder, parent_id, is_default FROM  exp_gallery_categories WHERE cat_id = '$cat_id' AND gallery_id = '{$this->gallery_id}'");

			$row = $query->row_array();
	
			foreach ($default as $val)
			{
				$$val = $row[$val];
			}
		}
		else
		{
			foreach ($default as $val)
			{
				if (isset($_POST[$val]))
					$$val = $_POST[$val];
				else
					$$val = '';
			}
		}

		// Build our output
		
		$title = ($cat_id == '') ? 'gallery_new_category' : 'gallery_edit_category';
		
		$title = $this->EE->lang->line($title);
		$crumb = $this->EE->dsp->anchor( BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=new_category', $this->EE->lang->line('category_groups')).
				 $this->EE->dsp->crumb_item($title);
		
		$r  = $this->EE->dsp->div('box320');
		$r .= $this->EE->dsp->heading($title, 5);
		
		$r .= $this->EE->dsp->form_open(array('action' => 'C=modules'.AMP.'M=gallery'.AMP.'P=update_category')).
			  $this->EE->dsp->input_hidden('gallery_id', $this->gallery_id).
			  $this->EE->dsp->input_hidden('old_cat_name', $cat_name);
			  
			  
		if ($cat_id)
		{
			$r .= $this->EE->dsp->input_hidden('cat_id', $cat_id);
		}
		 
		$r .= $this->EE->dsp->div('itemWrapper').BR.
			  $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('defaultBold', $this->EE->dsp->required().NBS.$this->EE->lang->line('gallery_category_name', 'cat_name'))).
			  $this->EE->dsp->input_text('cat_name', $cat_name, '20', '60', 'input', '310px', '', TRUE).
			  $this->EE->dsp->div_c();
		 
		$r .= $this->EE->dsp->div('itemWrapper').
			  $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_category_description', 'cat_description'))).
			  $this->EE->dsp->input_textarea('cat_description', $cat_description, 4, 'textarea', '310px').
			  $this->EE->dsp->div_c();
			  
		$r .= $this->EE->dsp->div('itemWrapper').
			  $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_cat_folder', 'cat_folder'))).
			  $this->EE->dsp->qdiv('default', $this->EE->lang->line('gallery_cat_folder_txt', 'cat_folder')).
			  $this->EE->dsp->input_text('cat_folder', $cat_folder, '20', '60', 'input', '310px', '', TRUE).
			  $this->EE->dsp->div_c();
				
		$r .= $this->EE->dsp->div('itemWrapper').
			  $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_category_parent'))).
			  $this->EE->dsp->input_select_header('parent_id').	 
			  $this->EE->dsp->input_select_option('0', $this->EE->lang->line('gallery_none'));
		
		$this->category_tree('text', '', $parent_id);
		
		foreach ($this->categories as $val)
		{
			$r .= $val;
		}
		
		$r .= $this->EE->dsp->input_select_footer().
			  $this->EE->dsp->div_c();
			  
		$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->input_checkbox('is_default', 'y', ($is_default == 'y') ? 1 : 0).' '.$this->EE->lang->line('gallery_is_default_cat'));
					  
		$r .= $this->EE->dsp->div('itemWrapper');
		$r .= ( ! $cat_id) ? $this->EE->dsp->input_submit($this->EE->lang->line('submit')) : $this->EE->dsp->input_submit($this->EE->lang->line('update'));
		$r .= $this->EE->dsp->div_c();
		$r .= $this->EE->dsp->div_c();

		$r .= $this->EE->dsp->form_close();
  
		$this->content_wrapper($title, $crumb, $r);
	}

	
	

	/** -----------------------------------
	/**  Category submission handler
	/** -----------------------------------*/
	function category_submission_hander()
	{
		$cat_id = ( ! $this->EE->input->post('cat_id')) ? FALSE : $this->EE->input->get_post('cat_id');
		
		$action = ($cat_id == FALSE) ? 'insert' : 'update';
		
		$new_cat_folder = ( ! $this->EE->input->post('cat_folder')) ? '' : $this->EE->input->get_post('cat_folder');

		if ( ! $this->EE->input->post('cat_name'))
		{
			return $this->category_form();
		}

		if ($this->EE->config->item('auto_convert_high_ascii') == 'y')
		{
			// Load the text helper
			$this->EE->load->helper('text');

			$_POST['cat_name'] =  ascii_to_entities($_POST['cat_name']);
		}
		
		$_POST['cat_name'] = str_replace('<', '&lt;', $_POST['cat_name']);
		$_POST['cat_name'] = str_replace('>', '&gt;', $_POST['cat_name']);
		
		// Does this category already exist?
				
		if ($cat_id == FALSE OR ($_POST['cat_name'] != $_POST['old_cat_name']))
		{
			$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_gallery_categories WHERE gallery_id = '".$this->gallery_id."' AND cat_name ='".$this->EE->db->escape_str($_POST['cat_name'])."'");
			
			if ($query->row('count')  > 0)
			{
				return $this->EE->dsp->error_message(array($this->EE->lang->line('gallery_catname_exists')));
			}
		}
		unset($_POST['old_cat_name']);
		
		if (substr($this->prefs['gallery_upload_path'], -1) != '/')
		{
			$this->prefs['gallery_upload_path'] .= '/';
		}

		// We need to first upadate the default category
		
		$is_default = (isset($_POST['is_default'])) ? 'y' : 'n';
		unset($_POST['is_default']);
		
		if ($cat_id == FALSE)
		{
			// Create the upload folder if needed
			
			if ($new_cat_folder != '')
			{
				if (substr($new_cat_folder, 0, 1) == '/')
				{
					$new_cat_folder = substr($new_cat_folder, 1);
				}
				
				if ($new_cat_folder != '' && substr($new_cat_folder, -1) == '/')
				{
					$new_cat_folder = substr($new_cat_folder, 0, -1);
				}
				
				if ($new_cat_folder != '')
				{
					$dir = $this->prefs['gallery_upload_path'];
					
					$folders = explode('/', $new_cat_folder);
					
					foreach($folders as $folder)
					{
						$dir .= $this->EE->functions->sanitize_filename($folder);
					
						if ( ! @is_dir($dir))
						{
							if ( ! @mkdir($dir, DIR_WRITE_MODE))
							{
								echo $dir;
								return $this->EE->dsp->error_message(array($this->EE->lang->line('gallery_cat_folder_error')));
							}
						
							@chmod($dir, DIR_WRITE_MODE);			
						}
						
						$dir .= '/';
					}
				}
			}		
		
			$sql = $this->EE->db->insert_string('exp_gallery_categories', $_POST);	 
			$this->EE->db->query($sql);
			
			$row_id = $this->EE->db->insert_id();
			
			/** ------------------------
			/**  Re-order categories
			/** ------------------------*/
			
			// When a new category is inserted we need to assign it an order.
			// Since the list of categories might have a custom order, all we
			// can really do is position the new category alphabetically.
			 
			// First we'll fetch all the categories alphabetically and assign
			// the position of our new category
			
			$query = $this->EE->db->query("SELECT cat_id, cat_name FROM exp_gallery_categories WHERE gallery_id = '{$this->gallery_id}' AND parent_id = '".$this->EE->db->escape_str($_POST['parent_id'])."' ORDER BY cat_name asc");
			
			$position = 0;
			$cat_id = '';
			
			foreach ($query->result_array() as $row)
			{
				if ($_POST['cat_name'] == $row['cat_name'])
				{
					$cat_id = $row['cat_id'];
					break;
				}	
				
				$position++;
			}
			
			// Next we'll fetch the list of categories ordered by the custom order
			// and create an array with the category ID numbers
				
			$query = $this->EE->db->query("SELECT cat_id, cat_name FROM exp_gallery_categories WHERE gallery_id = '{$this->gallery_id}' AND cat_id != '".$this->EE->db->escape_str($cat_id)."' ORDER BY cat_order");
	
			$cat_array = array();
	
			foreach ($query->result_array() as $row)
			{
				$cat_array[] = $row['cat_id'];
			}
			
			// Now we'll splice in our new category to the array.
			// Thus, we now have an array in the proper order, with the new
			// category added in alphabetically
	
			array_splice($cat_array, $position, 0, $cat_id);

			// Lastly, update the whole list
			
			$i = 1;
			foreach ($cat_array as $val)
			{
				$this->EE->db->query("UPDATE exp_gallery_categories SET cat_order = '$i' WHERE cat_id = '$val'");
				$i++;
			}
		}
		else  // UPDATE AN EXISTING CATEGORY
		{
		
			// Does the category folder need to be renamed or deleted?

			// Fetch the name of the old category folder
			$query = $this->EE->db->query("SELECT cat_folder FROM exp_gallery_categories WHERE cat_id = '".$this->EE->db->escape_str($cat_id)."' AND gallery_id = '{$this->gallery_id}'");
			$old_cat_folder = $query->row('cat_folder') ;
					
			$move_files 	= FALSE;
			$kill_old_folder = FALSE;
			
			// Unless the old cat folder name is different than the new one 
			// we don't need to do anything
							
			if ($old_cat_folder != $new_cat_folder)
			{					
				if ($new_cat_folder != "")
				{
				// Does a folder already exist with this name?
				if (@is_dir($this->prefs['gallery_upload_path'].$new_cat_folder))
				{
					return $this->EE->dsp->error_message($this->EE->lang->line('gallery_folder_exist_warning'));	  
				}
				
				// Old folder exists and new one is specified, so rename old folder
				
				if ($query->row('cat_folder')  != '')
				{
					$oldfile = $this->prefs['gallery_upload_path'].$query->row('cat_folder') ;
					$newfile = $this->prefs['gallery_upload_path'].$new_cat_folder;
					
					if ( ! @rename($oldfile, $newfile))
					{
						return $this->EE->dsp->error_message(array($this->EE->lang->line('gallery_renaming_cat_folder_error')));
					}
					
					@chmod($newfile, FILE_WRITE_MODE);
				}
				else
				{
					// Old folder does not exist, but new one is specified.
					// Create new folder and move images into it.
				
					$new_path = $this->prefs['gallery_upload_path'].$new_cat_folder;
					
					if ( ! @is_dir($new_path))
					{
						if ( ! @mkdir($new_path, DIR_WRITE_MODE))
						{
							return $this->EE->dsp->error_message(array($this->EE->lang->line('gallery_cat_folder_error')));
						}
						
						@chmod($new_path, DIR_WRITE_MODE);			
					}	
					
					$move_files = TRUE;
					}
				}
				else
				{
				// Old folder exist, but new one is blank
				// Move images into it root and delete old folder
				
					$move_files 		= TRUE;
					$kill_old_folder = TRUE;
				}
			}
			
			if ($move_files == TRUE)
			{
				// First we'll fetch all the images in the old location
				
				$query = $this->EE->db->query("SELECT filename, extension FROM exp_gallery_entries WHERE cat_id ='{$cat_id}'");
				
				if ($query->num_rows() > 0)
				{
					$old_path = $this->EE->functions->remove_double_slashes($this->prefs['gallery_upload_path'].'/'.$old_cat_folder.'/');
					$new_path = $this->EE->functions->remove_double_slashes($this->prefs['gallery_upload_path'].'/'.$new_cat_folder.'/');
	
					$prefixes = array('GXc94hURde6qpalzm543', $this->prefs['gallery_thumb_prefix'], $this->prefs['gallery_medium_prefix']);
	
					foreach ($query->result_array() as $row)
					{
						foreach ($prefixes as $prefix)
						{
							if ($prefix == '')
								continue;
						
							if ($prefix == 'GXc94hURde6qpalzm543')
								$prefix = '';
						
							$src = $old_path.$row['filename'].$prefix.$row['extension'];
							$dst = $new_path.$row['filename'].$prefix.$row['extension'];
															
							if (file_exists($src))
							{
								if ( ! @copy($src, $dst))
								{
									return $this->EE->dsp->error_message(array($this->EE->lang->line("gallery_copy_error")));
								}
						
								@chmod($dst, FILE_WRITE_MODE);
								@unlink($src);
							}
						}
						
					}					
				}	
				
				if ($kill_old_folder == TRUE)
				{
					@rmdir($this->EE->functions->remove_double_slashes($this->prefs['gallery_upload_path'].'/'.$old_cat_folder.'/'));
				}
			}
		
		
			if ($_POST['cat_id'] == $_POST['parent_id'])
			{
				$_POST['parent_id'] = 0;  
			}
 
			$sql = $this->EE->db->update_string(
										'exp_gallery_categories',
										
										array(
												'cat_name'  		=> $_POST['cat_name'],
												'cat_description'	=> $this->EE->input->post('cat_description'),
												'cat_folder'		=> $new_cat_folder,
												'parent_id' 		=> $this->EE->input->post('parent_id')
											 ),
											
										array(
												'cat_id'		=> $cat_id,
												'gallery_id'	=> $this->gallery_id			
											  )				
									 );	
				
			$this->EE->db->query($sql);
			$row_id = $this->EE->input->get_post('cat_id');
		}


		if ($is_default == 'y')
		{
			$this->EE->db->query("UPDATE exp_gallery_categories SET is_default = 'n' WHERE gallery_id = '{$this->gallery_id}'");
			$this->EE->db->query("UPDATE exp_gallery_categories SET is_default = 'y' WHERE cat_id = '".$this->EE->db->escape_str($row_id)."' AND gallery_id = '{$this->gallery_id}'");		
		}


		return $this->category_manager($action);
	}





	/** --------------------------------------
	/**  Category order change confirm
	/** --------------------------------------*/
	function category_reorder_confirm()
	{
		$this->EE->dsp->title = $this->EE->lang->line('gallery_reorder_categories');
		
		$this->EE->dsp->crumb = $this->EE->dsp->anchor( BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'M=gallery'.AMP.'P=category_manager'.AMP.'gallery_id='.$this->gallery_id, $this->EE->lang->line('gallery_categories')).
					  $this->EE->dsp->crumb_item($this->EE->lang->line('gallery_reorder_categories'));

		$this->EE->dsp->body = $this->EE->dsp->form_open(array('action' => 'C=modules'.AMP.'M=gallery'.AMP.'P=global_cat_order'.AMP.'gallery_id='.$this->gallery_id))
					.$this->EE->dsp->input_hidden('sort_order', $_POST['sort_order'])
					.$this->EE->dsp->input_hidden('override', 1)
					.$this->EE->dsp->heading($this->EE->lang->line('gallery_reorder_categories'))
					.$this->EE->dsp->div('box')
					.$this->EE->dsp->qdiv('default', $this->EE->lang->line('gallery_category_order_confirm'))
					.$this->EE->dsp->qdiv('alert', BR.$this->EE->lang->line('gallery_category_sort_warning'))
					.$this->EE->dsp->qdiv('itemWrapper', BR.$this->EE->dsp->input_submit($this->EE->lang->line('update')))
					.$this->EE->dsp->div_c()
					.$this->EE->dsp->form_close();
	}



	
	/** -----------------------------------
	/**  Set Global Category Order
	/** -----------------------------------*/
	
	function reorder_categories()
	{
		$order = ($_POST['sort_order'] == 'a') ? 'a' : 'c';
				
		if ($order == 'a')
		{
			if ( ! isset($_POST['override']))
			{
				return $this->category_reorder_confirm();
			}
			else
			{
				$this->reorder_categories_alpha();
			}
		}
		
		$this->EE->db->query("UPDATE exp_galleries SET gallery_sort_order = '$order' WHERE gallery_id = '{$this->gallery_id}'");
		
		// Return Location
		$return = BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=category_manager'.AMP.'gallery_id='.$this->gallery_id;
		$this->EE->functions->redirect($return);
		exit;		
	}



	/** --------------------------------
	/**  Re-order Categories Alphabetically
	/** --------------------------------*/
	
	function reorder_categories_alpha()
	{
		$data = $this->process_category_group();
		
		if (count($data) == 0)
		{
			return FALSE;
		}

		foreach($data as $cat_id => $cat_data)
		{
			$this->EE->db->query("UPDATE exp_gallery_categories SET cat_order = '{$cat_data['1']}' WHERE cat_id = '{$cat_id}'");
		}
		
			return TRUE;
	}


  
	/** --------------------------------
	/**  Process nested category group
	/** --------------------------------*/
	function process_category_group()
	{
		$sql = "SELECT cat_name, cat_id, parent_id FROM exp_gallery_categories WHERE gallery_id ='{$this->gallery_id}' ORDER BY parent_id, cat_name";
		
		$query = $this->EE->db->query($sql);
			  
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}
							
		foreach($query->result_array() as $row)
		{		
			$this->cat_update[$row['cat_id']]  = array($row['parent_id'], '1', $row['cat_name']);
		}
	 	
		$order = 0;
		
		foreach($this->cat_update as $key => $val) 
		{
			if (0 == $val['0'])
			{	
				$order++;
				$this->cat_update[$key]['1'] = $order;
				$this->process_subcategories($key);  // Sends parent_id
			}
		} 
		
		return $this->cat_update;
	}

	
	
	
	/** --------------------------------
	/**  Process Subcategories
	/** --------------------------------*/
		
	function process_subcategories($parent_id)
	{		
		$order = 0;
		
		foreach($this->cat_update as $key => $val) 
		{
			if ($parent_id == $val['0'])
			{
				$order++;
				$this->cat_update[$key]['1'] = $order;												
				$this->process_subcategories($key);
			}
		}
	}

  
	/** --------------------------------------
	/**  Change Category Order
	/** --------------------------------------*/
		
	function change_category_order()
	{
		// Fetch required globals
		
		foreach (array('cat_id', 'order') as $val)
		{
			if ( ! isset($_GET[$val]))
			{
				return FALSE;
			}
		
				$$val = $_GET[$val];
		}
		
		
		// Return Location
		$return = BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=category_manager'.AMP.'gallery_id='.$this->gallery_id;
		
		// Fetch the parent ID
		
		$query = $this->EE->db->query("SELECT parent_id FROM exp_gallery_categories WHERE cat_id = '".$this->EE->db->escape_str($cat_id)."'");
		$parent_id = $query->row('parent_id') ;
		
		// Is the requested category already at the beginning/end of the list?
		
		$dir = ($order == 'up') ? 'asc' : 'desc';
		
		$query = $this->EE->db->query("SELECT cat_id FROM exp_gallery_categories WHERE gallery_id = '{$this->gallery_id}' ORDER BY cat_order {$dir} LIMIT 1");
			
		if ($query->row('cat_id')  == $cat_id)
		{ 
			$this->EE->functions->redirect($return);
			exit;		
		}
		
		// Fetch all the categories in the parent
				
		$query = $this->EE->db->query("SELECT cat_id, cat_order FROM exp_gallery_categories WHERE gallery_id = '{$this->gallery_id}' AND  parent_id = '".$this->EE->db->escape_str($parent_id)."' ORDER BY cat_order asc");
		
		// If there is only one category, there is nothing to re-order
		
		if ($query->num_rows() <= 1)
		{
			$this->EE->functions->redirect($return);
			exit;		
		}
		
		// Assign category ID numbers in an array except the category being shifted.
		// We will also set the position number of the category being shifted, which
		// we'll use in array_shift()
	
		$flag	= '';
		$i		= 1;
		$cats	= array();
		
		foreach ($query->result_array() as $row)
		{
			if ($cat_id == $row['cat_id'])
			{
				$flag = ($order == 'down') ? $i+1 : $i-1;
			}
			else
			{
				$cats[] = $row['cat_id'];				
			}
			
			$i++;
		}
						
		array_splice($cats, ($flag -1), 0, $cat_id);
		
		// Update the category order for all the categories within the given parent
		
		$i = 1;
		
		foreach ($cats as $val)
		{
			$this->EE->db->query("UPDATE exp_gallery_categories SET cat_order = '$i' WHERE cat_id = '$val'");
			
			$i++;
		}
		
		// Switch to custom order
		
		$this->EE->db->query("UPDATE exp_galleries SET gallery_sort_order = 'c' WHERE gallery_id = '{$this->gallery_id}'");

		$this->EE->functions->redirect($return);
		exit;		
	}




	/** -------------------------------------
	/**  Delete category confirm
	/** ------------------------------------*/
	function delete_category_confirm()
	{
		if ( ! $cat_id = $this->EE->input->get_post('cat_id'))
		{
			return FALSE;
		}

		$query = $this->EE->db->query("SELECT cat_name FROM exp_gallery_categories WHERE cat_id = '$cat_id'");
		
		
		$this->EE->dsp->title = $this->EE->lang->line('gallery_delete_category');		
		
		$this->EE->dsp->crumb = $this->EE->dsp->anchor( BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'M=gallery'.AMP.'P=category_manager'.AMP.'gallery_id='.$this->gallery_id, $this->EE->lang->line('gallery_categories')).
					  $this->EE->dsp->crumb_item($this->EE->lang->line('gallery_delete_category'));
		
		$this->EE->dsp->body = $this->EE->dsp->form_open(array('action' => 'C=modules'.AMP.'M=gallery'.AMP.'P=del_category'))
					.$this->EE->dsp->input_hidden('cat_id', $cat_id)
					.$this->EE->dsp->input_hidden('gallery_id', $this->gallery_id)
					.$this->EE->dsp->heading($this->EE->dsp->qspan('alert', $this->EE->lang->line('gallery_delete_category')))
					.$this->EE->dsp->div('box')
					.$this->EE->dsp->qdiv('itemWrapper', '<b>'.$this->EE->lang->line('delete_category_confirmation').'</b>')
					.$this->EE->dsp->qdiv('itemWrapper', '<i>'.$query->row('cat_name') .'</i>')
					.$this->EE->dsp->qdiv('alert', BR.$this->EE->lang->line('gallery_entries_will_be_nuked'))
					.$this->EE->dsp->qdiv('alert', BR.$this->EE->lang->line('action_can_not_be_undone'))
					.$this->EE->dsp->qdiv('itemWrapper', BR.$this->EE->dsp->input_submit($this->EE->lang->line('delete')))
					.$this->EE->dsp->div_c()
					.$this->EE->dsp->form_close();
	}



	/** -----------------------------------
	/**  Delete category
	/** -----------------------------------*/
	function delete_category()
	{
		if ( ! $cat_id = $this->EE->input->post('cat_id'))
		{
			return FALSE;
		}
		
		if ( ! is_numeric($cat_id))
		{
			return FALSE;
		}


		$this->EE->db->query("DELETE FROM exp_gallery_categories WHERE cat_id = '$cat_id' AND gallery_id = '{$this->gallery_id}'");
		$this->EE->db->query("DELETE FROM exp_gallery_entries WHERE cat_id = '$cat_id' AND gallery_id = '{$this->gallery_id}'");

		// Fetch and delete comments
		$query = $this->EE->db->query("SELECT entry_id FROM exp_gallery_entries WHERE cat_id = '$cat_id' AND gallery_id = '{$this->gallery_id}'");
	
		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$this->EE->db->query("DELETE FROM exp_gallery_comments WHERE entry_id = '".$row['entry_id']."'");
			}
		}
		
		/** -----------------------------------
		/**  Are there any sub-categories?
		/** -----------------------------------*/
		// If so, we'll re-assign the parent_id to zero so they are not longer a child
		
		$this->EE->db->query("UPDATE exp_gallery_categories SET parent_id = '0'  WHERE parent_id = '$cat_id' AND gallery_id = '{$this->gallery_id}'");		
		
				
		$return = BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=category_manager'.AMP.'gallery_id='.$this->gallery_id;
		$this->EE->functions->redirect($return);
		exit;		
	}

	

	 
	/** ----------------------------------------
	/**  New Gallery Pre-Setup Step One form
	/** ----------------------------------------*/
	
	function create_new_gallery()
	{
		$folder	= '';		
		
		$this->EE->dsp->body = $this->EE->dsp->form_open(array('action' => 'C=modules'.AMP.'M=gallery'.AMP.'P=new_gallery_step_two'))
					.$this->EE->dsp->qdiv('tableHeading', $this->EE->lang->line('gallery_step_one'))
					.$this->EE->dsp->div('box')
					.$this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qspan('defaultBold', $this->EE->lang->line('gallery_upload_folder')))
					.$this->EE->dsp->qdiv('itemWrapper', $this->EE->lang->line('gallery_create_info'))
					.$this->EE->dsp->qdiv('itemWrapper', $this->EE->lang->line('gallery_create_info2'))
					.$this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qspan('defaultBold', $this->EE->lang->line('gallery_upload_path')))
					.$this->EE->dsp->qdiv('itemWrapper', $this->EE->lang->line('gallery_if_path_unknown'))
					.$this->EE->dsp->input_text('gallery_folder', $folder, '30', '100', 'input', '400px').BR.BR
					.$this->EE->dsp->qdiv('itemWrapper', BR.$this->EE->dsp->input_submit($this->EE->lang->line('submit')))
					.$this->EE->dsp->div_c()
					.$this->EE->dsp->form_close();

		$this->EE->dsp->title  = $this->EE->lang->line('gallery_step_one');
		$this->EE->dsp->crumb  = $this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery', $this->EE->lang->line('gallery_step_one')).$this->EE->dsp->crumb_item($this->EE->lang->line('gallery_new_gallery_form'));
	}

	
	
	 
	/** ----------------------------------------
	/**  New Gallery Pre-Setup Step Two form
	/** ----------------------------------------*/
	
	function create_new_gallery_step_two($error = FALSE)
	{
		if ( ! $gallery_folder = $this->EE->input->post('gallery_folder'))		
		{
				return $this->create_new_gallery();
		}
		
		$template_group = ( ! $this->EE->input->post('template_group')) ? 'gallery' : $this->EE->input->get_post('template_group');
		
		
		$msg = ($error == '') ? '' : $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('alert', $error));
		
		
		$this->EE->dsp->body = $this->EE->dsp->form_open(array('action' => 'C=modules'.AMP.'M=gallery'.AMP.'P=gallery_prefs_form'))
					.$this->EE->dsp->input_hidden('gallery_folder', $gallery_folder)
					.$this->EE->dsp->qdiv('tableHeading', $this->EE->lang->line('gallery_step_two'))
					.$this->EE->dsp->div('box')
					.$msg
					.$this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qspan('defaultBold', $this->EE->lang->line('gallery_tempgroup_name')))
					.$this->EE->dsp->qdiv('itemWrapper', $this->EE->lang->line('gallery_tg_info'))
					.$this->EE->dsp->qdiv('itemWrapper', $this->EE->lang->line('gallery_tg_info2'))
					.$this->EE->dsp->input_text('template_group', $template_group, '15', '50', 'input', '200px')
					.$this->EE->dsp->qdiv('itemWrapper', BR.$this->EE->dsp->input_submit($this->EE->lang->line('submit')))
					.$this->EE->dsp->div_c()
					.$this->EE->dsp->form_close();

		$this->EE->dsp->title  = $this->EE->lang->line('gallery_step_one');
		$this->EE->dsp->crumb  = $this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery', $this->EE->lang->line('gallery_step_one')).$this->EE->dsp->crumb_item($this->EE->lang->line('gallery_new_gallery_form'));
	}

	
	
	
	
	/** ----------------------------------------
	/**  Locate Image Folder
	/** ----------------------------------------*/
	
	// This function walks up the directory tree
	// looking for the submitted folder.  It's 
	// designed to let us make an educated guess
	// as to where the folder

	function locate_folder($path, $target)
	{
			if ($this->image_folder != '')
				return TRUE;
				
		if ($this->timeout !== FALSE && time() > $this->timeout)
		{ 
			$this->timeout = FALSE;
			return FALSE;
		}
						
		if ($handle = @opendir($path)) 
		{ 
			while (FALSE !== ($file = @readdir($handle)) && FALSE !== $this->timeout)
			{
				if (@is_dir($path.$file) && $file != SYSDIR && substr($file,0,1) != '.' )
				{	
					if ($file == $target)
					{
						$this->image_folder = $path;
						@closedir($handle);
						
						return TRUE;
					}
					
					$this->locate_folder($path.$file.'/', $target);	
				}
			}
			
			@closedir($handle);
		}
		
		return FALSE;
	}

	 
 
	/** ----------------------------------------
	/**  Watermark Tester
	/** ----------------------------------------*/
	
	function watermark_tester()
	{
 		$this->EE->dsp->show_crumb = FALSE;

 
		$props = array (
							'file_path'				=> '',
							'file_name'				=> '',
							'wm_image_path'			=>	'',	
							'wm_test_image_path'	=>	'',
							'wm_type'				=>	't',
							'wm_use_font'			=>	FALSE,
							'dynamic_output'		=>	TRUE,
							'wm_font'				=>	'texb.ttf',
							'wm_font_size'			=>	17,	
							'wm_text_size'			=>	0,
							'wm_text'				=>	'',
							'wm_vrt_alignment'		=>	'T',	
							'wm_hor_alignment'		=>	'L',
							'wm_padding'			=>	0,
							'wm_x_offset'			=>	0,
							'wm_y_offset'			=>	0,
							'wm_transp_color'		=>	'ffffff',
							'wm_text_color'			=>	'',
							'wm_use_drop_shadow'	=>	FALSE,
							'wm_shadow_color'		=>	'',
							'wm_shadow_distance'	=>	2,
							'wm_opacity'			=>	70,
							'wm_x_transp'			=>  4,
							'wm_y_transp'			=>  4
					  );
		 
			 
		foreach ($props as $key => $val)
		{
			if (isset($_GET['gallery_'.$key]))
			{
				$props[$key] = urldecode($_GET['gallery_'.$key]);
			}
		}
		
		$props['resize_protocol']	= $this->prefs['gallery_image_protocal'];
		$xy = explode('/', $props['wm_test_image_path']);
		$props['file_name']			= end($xy);
		$props['file_path']			= $this->EE->functions->remove_double_slashes(str_replace($props['file_name'], '', $props['wm_test_image_path']).'/');
		$props['wm_use_font'] 		= ($props['wm_use_font'] == 'y') ? TRUE : FALSE; 
		$props['wm_use_drop_shadow'] = ($props['wm_use_drop_shadow'] == 'y') ? TRUE : FALSE; 
	
		require APPPATH.'_to_be_replaced/lib.image_lib'.EXT;
		$IM = new Image_lib();
		
		$ret = $IM->set_properties($props);		
		
		if ($ret == FALSE)
		{  
			echo $IM->show_error();
			exit;
		}
						
		$type = ($props['wm_type'] == 't') ? 'text_watermark' : 'image_watermark';		

		if ( ! $IM->$type())
		{
			echo $IM->show_error();
		}
					
		exit; 
	}

 
 
	 
	/** ----------------------------------------
	/**  New Gallery Preferences Form
	/** ----------------------------------------*/
	
	function gallery_prefs_form()
	{
			// If the gallery_id does not exist we are creating a new gallery
			// otherwise we are updating ane existing one
		
		$this->gallery_id = ($this->EE->input->get_post('gallery_id')) ? $this->EE->input->get_post('gallery_id') : '';
		
		$gallery_folder = ( ! $this->EE->input->post('gallery_folder')) ? FALSE : $this->EE->input->get_post('gallery_folder');
		$gallery_image_path	= '../'.$gallery_folder;				
		$gallery_image_url	= $this->EE->config->slash_item('site_url').$gallery_folder;
		$gallery_url			= '';
		$gallery_comment_url	= '';
		
		$expand		= '<img src="'.PATH_CP_GBL_IMG.'expand.gif" border="0"  width="10" height="10" alt="Expand" />';
		$collapse	= '<img src="'.PATH_CP_GBL_IMG.'collapse.gif" border="0"  width="10" height="10" alt="Collapse" />';

		/** ------------------------------------
		/**  Fetch the name of the image folder
		/** ------------------------------------*/
		
		if ($this->gallery_id == '')
		{
			if ( ! $gallery_folder = $this->EE->input->post('gallery_folder'))		
			{
				return $this->create_new_gallery();
			}
			
			if ( ! $template_group = $this->EE->input->post('template_group'))		
			{
				return $this->create_new_gallery();
			}
			
			// Is the group name illegal?
			
			if ( ! preg_match("#^[a-zA-Z0-9_\-/]+$#i", $template_group))
			{
				return $this->create_new_gallery_step_two($this->EE->lang->line('gallery_illegal_characters'));
			}
			
			// Is the group name reserved?
			
			if (in_array($template_group, $this->reserved_names))
			{
				return $this->create_new_gallery_step_two($this->EE->lang->line('gallery_reserved_name'));
			}

			// Is the group name taken

			$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_template_groups WHERE group_name = '{$template_group}'");
			
			if ($query->row('count')  > 0)
			{
				return $this->create_new_gallery_step_two($this->EE->lang->line('gallery_group_taken'));
			}
						
			
			$this->prefs['gallery_image_protocal'] = $this->EE->config->item('image_resize_protocol');
			$this->prefs['gallery_image_lib_path'] = $this->EE->config->item('image_library_path');
			
			
			$gallery_url = $this->EE->config->slash_item('site_url').$this->EE->config->item('site_index').'/'.$template_group.'/';
			$gallery_comment_url = $this->EE->config->slash_item('site_url').$this->EE->config->item('site_index').'/'.$template_group.'/comments/';
						
			// If we are setting up a new gallery we'll try to 
			// determine the location of the image folder
			
			$good_path = TRUE;
			
			if ($this->EE->config->item('demo_date') !== FALSE)
			{
				$gallery_image_path	= 'images/'.$gallery_folder;
				$gallery_image_url	= $this->EE->config->slash_item('site_url').'images/'.$gallery_folder;
				
				$gallery_image_path	= $this->EE->functions->remove_double_slashes($gallery_image_path);
				$gallery_image_url	= $this->EE->functions->remove_double_slashes($gallery_image_url);
					
				if (@realpath($gallery_image_path) !== FALSE)
				{
					$gallery_image_path = realpath($gallery_image_path).'/';
					$gallery_image_path = str_replace("\\", "/", $gallery_image_path);	
				}
			}
			else
			{
				$gallery_image_path = $gallery_folder;
				
				if (strstr($gallery_image_path, "/"))
				{
					if (@is_dir($gallery_image_path))
					{
						$this->image_folder = $gallery_image_path;
					}
					else
					{
						$good_path = FALSE;
					}

					// Load the string helper
					$this->EE->load->helper('string');

					$gallery_folder = trim_slashes($gallery_image_path);
					
					if (strstr($gallery_folder, "/"))
					{
						$xy = explode("/", $gallery_folder);
						$gallery_folder = end($xy);
					}
				}
				else
				{
					$good_path = FALSE;
				}
							
				if ( ! $good_path)
				{
					$gallery_image_path	= '../'.$gallery_folder;				
					$dots	= '../';
					$path	= $dots;
					$this->timeout = time()+25;
					
					for ($i = 0; $i < 3; $i++)
					{			
						if (TRUE === $this->locate_folder($path, $gallery_folder))
						{
							break;
						}
						else
						{
							$path .= $dots;				
						}
					}
				}
			}
			
			if ($this->image_folder !== FALSE)
			{
				$gallery_image_path	= ( ! $good_path) ? $this->image_folder.$gallery_folder.'/' : $this->image_folder;
				$gallery_image_url	= $this->EE->config->slash_item('site_url');
				
				if ( ! $good_path)
				{
					$gallery_image_url .= str_replace("./",  "", str_replace("../", "", $this->image_folder)).$gallery_folder.'/';
				}
				else
				{					
					$gallery_image_url = '';
				}
			
				$gallery_image_path	= $this->EE->functions->remove_double_slashes($gallery_image_path	);
				$gallery_image_url	= $this->EE->functions->remove_double_slashes($gallery_image_url);
				
				// Set the "real path" to image folder
				
				if (@realpath($gallery_image_path) !== FALSE)
				{
					$gallery_image_path = realpath($gallery_image_path).'/';
					$gallery_image_path = str_replace("\\", "/", $gallery_image_path);	
				}	
			}
		}
		
		// Determine where the "cp_images" folder is

		if ( ! isset($this->prefs['gallery_wm_test_image_path']))
		{
			if ( file_exists('./themes/cp_global_images/watermark_test.jpg'))
			{
				$test_path = './themes/cp_global_images/watermark_test.jpg';
				$wm_image_path = './themes/cp_global_images/watermark.png';
			}
			else
			{
				$site_url = $this->EE->config->slash_item('site_url');
				$tpath  = PATH_CP_GBL_IMG;
				$tpath = str_replace($site_url, '', $tpath);
				
				if (stristr($tpath, 'www.') !== FALSE OR stristr($tpath, 'http://') !== FALSE)
				{
					$tpath = str_replace('http://', '', $tpath);
					$xy = explode('/', $tpath);
					$seg = current($xy);
					$tpath = str_replace($seg, '', $tpath);
				}
				
				$tpath	= $this->EE->functions->remove_double_slashes('../'.$tpath.'/');
				
				if (@realpath($tpath) !== FALSE)
				{
					$tpath = realpath($tpath).'/';
					$tpath = str_replace("\\", "/", $tpath);	
				}	

				$test_path = $tpath.'watermark_test.jpg';
			
				$wm_image_path = ( ! isset($this->prefs['gallery_wm_test_image_path'])) ? $tpath.'watermark.png' : $this->prefs['gallery_wm_test_image_path'];
			}
		}
		else
		{
			$test_path = $this->prefs['gallery_wm_test_image_path'];
			$wm_image_path = ( ! isset($this->prefs['gallery_wm_test_image_path'])) ? '' : $this->prefs['gallery_wm_test_image_path'];
		}
			
		if ( ! class_exists('Image_lib'))
		{ 
			require APPPATH.'_to_be_replaced/lib.image_lib'.EXT;
		}
		
		$IM = new Image_lib();
								
		$imgprops = $IM->get_image_properties($test_path , TRUE);		

		$testwidth  = ( ! isset($imgprops['width']))		? 800 : $imgprops['width'];
		$testheight = ( ! isset($imgprops['height']))		? 600 : $imgprops['height'];
		
		
			
		$default = array(
						'gallery_image_protocal'				=> $this->EE->config->item('image_resize_protocol'),
						'gallery_image_lib_path'				=> $this->EE->config->item('image_library_path'),						
						'gallery_full_name' 					=> '',
						'gallery_short_name' 					=> '',
						'gallery_url' 							=> $gallery_url,
						'gallery_comment_url' 					=> $gallery_comment_url,
						'gallery_upload_folder'					=> $gallery_folder,
						'gallery_upload_path' 					=> $gallery_image_path,
						'gallery_image_url' 					=> $gallery_image_url,
						'gallery_batch_folder' 					=> '',
						'gallery_batch_path' 					=> '',
						'gallery_batch_url'						=> '',
						'gallery_text_formatting'				=> 'xhtml',
						'gallery_auto_link_urls'				=> 'y',
						'gallery_allow_comments'				=> 'y',
						'gallery_comment_require_membership'	=> 'n',
						'gallery_comment_html_formatting'		=> 'safe',
						'gallery_comment_text_formatting'		=> 'xhtml',
						'gallery_comment_use_captcha'			=> 'n',
						'gallery_comment_moderate'				=> 'n',
						'gallery_comment_max_chars'				=> '2500',
						'gallery_comment_timelock'				=> '30',
						'gallery_comment_require_email'			=> 'y',
						'gallery_comment_allow_img_urls'		=> 'n',
						'gallery_comment_auto_link_urls'		=> 'y',
						'gallery_comment_notify'				=> 'n',
						'gallery_comment_notify_authors'		=> 'n',
						'gallery_comment_notify_emails'			=> '',
						'gallery_comment_expiration'			=> '0',
						'gallery_maintain_ratio'				=> 'y',
						'gallery_create_thumb'					=> 'y',
						'gallery_thumb_width'					=> '100',
						'gallery_thumb_height'					=> '75',
						'gallery_thumb_quality'					=> '75%',
						'gallery_thumb_prefix'					=> 'thumb',
						'gallery_create_medium'					=> 'y',
						'gallery_medium_width'					=> '400',
						'gallery_medium_height'					=> '300',
						'gallery_medium_quality'				=> '90%',
						'gallery_medium_prefix'					=> 'medium',
						'gallery_wm_image_path'					=>	$wm_image_path,	
						'gallery_wm_test_image_path'			=>	$test_path,						
						'gallery_wm_type'						=> 'n',
						'gallery_wm_use_font'					=> 'y',
						'gallery_wm_font'						=> 'texb.ttf',
						'gallery_wm_font_size'					=> 16,
						'gallery_wm_text'						=> 'Copyright 2007',
						'gallery_wm_alignment'					=> '',
						'gallery_wm_vrt_alignment'				=> 'T',
						'gallery_wm_hor_alignment'				=> 'L',
						'gallery_wm_padding'					=> 10,
						'gallery_wm_x_offset'					=> 0,
						'gallery_wm_y_offset'					=> 0,
						'gallery_wm_x_transp'					=> 2,
						'gallery_wm_y_transp'					=> 2,
						'gallery_wm_text_color'					=> '#ffff00',	
						'gallery_wm_use_drop_shadow'			=> 'y',	
						'gallery_wm_shadow_color'				=> '#999999',	
						'gallery_wm_shadow_distance'			=> 1,
						'gallery_wm_opacity'					=> 50,
						'gallery_wm_apply_to_thumb'				=> 'n',
						'gallery_wm_apply_to_medium'			=> 'n',
						'gallery_test_mode'						=> 'y',
						'gallery_cf_one'						=> 'n',
						'gallery_cf_one_label'					=> 'Custom Field One',
						'gallery_cf_one_type'					=> 'i',
						'gallery_cf_one_list'					=> '',
						'gallery_cf_one_rows'					=> '12',
						'gallery_cf_one_formatting'				=> 'xhtml',
						'gallery_cf_one_auto_link'				=> 'y',
						'gallery_cf_one_searchable'				=> 'y',
						'gallery_cf_two'						=> 'n',
						'gallery_cf_two_label'					=> 'Custom Field Two',
						'gallery_cf_two_type'					=> 'i',
						'gallery_cf_two_list'					=> '',
						'gallery_cf_two_rows'					=> '12',
						'gallery_cf_two_formatting'				=> 'xhtml',
						'gallery_cf_two_auto_link'				=> 'y',
						'gallery_cf_two_searchable'				=> 'y',
						'gallery_cf_three'						=> 'n',
						'gallery_cf_three_label'				=> 'Custom Field Three',
						'gallery_cf_three_type'					=> 'i',
						'gallery_cf_three_list'					=> '',
						'gallery_cf_three_rows'					=> '12',
						'gallery_cf_three_formatting'			=> 'xhtml',
						'gallery_cf_three_auto_link'			=> 'y',
						'gallery_cf_three_searchable'			=> 'y',
						'gallery_cf_four'						=> 'n',
						'gallery_cf_four_label'					=> 'Custom Field Four',
						'gallery_cf_four_type'					=> 'i',
						'gallery_cf_four_list'					=> '',
						'gallery_cf_four_rows'					=> '12',
						'gallery_cf_four_formatting'			=> 'xhtml',
						'gallery_cf_four_auto_link'				=> 'y',
						'gallery_cf_four_searchable'			=> 'y',
						'gallery_cf_five'						=> 'n',
						'gallery_cf_five_label'					=> 'Custom Field Five',
						'gallery_cf_five_type'					=> 'i',
						'gallery_cf_five_list'					=> '',
						'gallery_cf_five_rows'					=> '12',
						'gallery_cf_five_formatting'			=> 'xhtml',
						'gallery_cf_five_auto_link'				=> 'y',
						'gallery_cf_five_searchable'			=> 'y',
						'gallery_cf_six'						=> 'n',
						'gallery_cf_six_label'					=> 'Custom Field Six',
						'gallery_cf_six_type'					=> 'i',
						'gallery_cf_six_list'					=> '0',
						'gallery_cf_six_rows'					=> '12',
						'gallery_cf_six_formatting'				=> 'xhtml',
						'gallery_cf_six_auto_link'				=> 'y',
						'gallery_cf_six_searchable'				=> 'y',
						);		
				
		
		if ($this->gallery_id != '')
		{			
			foreach ($default as $key => $val)
			{			
				$$key = (isset($this->prefs[$key])) ? $this->prefs[$key] : $val;
			}
		 	}
		else
		{
			foreach ($default as $key => $val)
			{			
				$$key = $val;
			}		
		}

		$required = array('gallery_full_name', 'gallery_short_name', 'gallery_url', 'gallery_upload_folder', 'gallery_upload_path', 'gallery_image_url');

		$menu = array(
					
				'gallery_general_config'		=> array(
												'gallery_full_name'			=> array('t', ''),
												'gallery_short_name'		=> array('t', ''),
												'gallery_url'				=> array('t', ''),
												'gallery_comment_url'		=> array('t', '')
											 ),
											 
				'gallery_paths'				=> array(
												'gallery_upload_folder'		=> array('t', ''),
												'gallery_upload_path'		=> array('t', ''),
												'gallery_image_url'			=> array('t', '')
											 ),
											 
				'gallery_batch_prefs'		=> array(
												'gallery_batch_folder'		=> array('t', ''),
												'gallery_batch_path'		=> array('t', ''),
												'gallery_batch_url'			=> array('t', '')
											 ),

				'gallery_caption_prefs'		=> array(
												'gallery_text_formatting'	=> array('d', array('xhtml' => 'gallery_xhtml', 'br' => 'gallery_br', 'none' => 'gallery_none')),				
												'gallery_auto_link_urls'	=> array('r', array('y' => 'yes', 'n' => 'no'))
											 ),
											 
				'gallery_protocal_prefs'		=> array(
												'gallery_image_protocal'	=> array('d', array('gd' => 'gallery_gd', 'gd2' => 'gallery_gd2', 'imagemagick' => 'gallery_image_magick', 'netpbm' => 'gallery_netpbm')),				
												'gallery_image_lib_path'	=> array('t', '')
											 ),
		
				'gallery_thumb_prefs'		=> array(
											 	'gallery_create_thumb'		=> array('r', array('y' => 'yes', 'n' => 'no')),
												'gallery_thumb_prefix'		=> array('t', ''),
												'gallery_create_medium'		=> array('r', array('y' => 'yes', 'n' => 'no')),
												'gallery_medium_prefix'		=> array('t', '')
											 ),
		
				'gallery_resize_prefs'		=> array(
												'gallery_thumb_width'		=> array('s', ''),
												'gallery_medium_width'		=> array('m', ''),
												'gallery_maintain_ratio'	=> array('r', array('y' => 'yes', 'n' => 'no')),
												'gallery_thumb_quality'		=> array('t', ''),
												'gallery_medium_quality'	=> array('t', '')
											 ),
											 
				'gallery_watermark_prefs'	 => FALSE,
				'gallery_custom_field_prefs' => FALSE,				
				
				 // FUTURE FEATURE - COMMENT MODERATION
				// 'gallery_comment_moderate'				=> 'n',
																						  
				'gallery_comment_prefs'	=> array(
											 	'gallery_allow_comments'				=> array('r', array('y' => 'yes', 'n' => 'no')),
											 	'gallery_comment_require_membership'	=> array('r', array('y' => 'yes', 'n' => 'no')),
											 	'gallery_comment_use_captcha'			=> array('r', array('y' => 'yes', 'n' => 'no')),
												'gallery_comment_expiration'			=> array('x', ''),
												'gallery_comment_max_chars'				=> array('p', ''),
												'gallery_comment_timelock'				=> array('p', ''),
											 	'gallery_comment_require_email'			=> array('r', array('y' => 'yes', 'n' => 'no')),
											 	'gallery_comment_text_formatting'		=> array('d', array('xhtml' => 'gallery_xhtml', 'br' => 'gallery_br', 'none' => 'gallery_none')),				
											 	'gallery_comment_html_formatting'		=> array('d', array('none' => 'gallery_no_html',  'safe' => 'gallery_safe', 'all' => 'gallery_all')),				
											 	'gallery_comment_allow_img_urls'		=> array('r', array('y' => 'yes', 'n' => 'no')),
											 	'gallery_comment_auto_link_urls'		=> array('r', array('y' => 'yes', 'n' => 'no'))
											 ),
											 
				'gallery_comment_notification_prefs'	=> array(
											 	'gallery_comment_notify_authors'		=> array('r', array('y' => 'yes', 'n' => 'no')),
											 	'gallery_comment_notify'				=> array('r', array('y' => 'yes', 'n' => 'no')),
												'gallery_comment_notify_emails'			=> array('t', '')
											 )
					);
					
		$submenu = array(
						'gallery_short_name' 			=> 'gallery_short_name_desc',
						'gallery_upload_path' 			=> 'gallery_upload_path_desc',
						'gallery_maintain_ratio'		=> 'gallery_maintain_ratio_desc',
						'gallery_image_lib_path'		=> 'gallery_image_lib_path_cont',
						'gallery_comment_expiration'	=> 'gallery_comment_expiration_desc',
						'gallery_comment_timelock'		=> 'gallery_comment_timelock_desc',
						'gallery_comment_notify_emails'	=> 'gallery_comment_notify_emails_desc'
					);
		
		
		
									
		$r = '';
		$r .= <<<EOT
	<script type="text/javascript">
	<!--

		function showhide_tablerow()
		{
			var arg_length = arguments.length;
			for(i=0; i< arg_length; i++)
			{
				if (document.getElementById(arguments[i]).style.display == "none")
				{
					document.getElementById(arguments[i]).style.display = "";
				}
				else
				{
					document.getElementById(arguments[i]).style.display = "none";
				}
			}
		}

	//-->
	</script>
EOT;

		$max_exceeded = $this->EE->lang->line('gallery_max_size');		
		$max_exceeded = str_replace("%s", $this->max_size, $max_exceeded);
		
		$basepath = BASE.'&C=modules&M=gallery&gallery_id='.$this->gallery_id.'&Z=1';		
		
		ob_start();
		?>
		<script type="text/javascript">
				
		function change_thumb_value(f, side, nom)
		{
			if (nom == 'gallery_thumb_width' OR nom == 'gallery_thumb_height')
			{
				var form_w		= f.gallery_thumb_width;
				var form_h		= f.gallery_thumb_height;
				var form_wo		= f.thumb_width_orig;
				var form_ho		= f.thumb_height_orig;
				var constrain	= f.constrain_thumb;
			}
			else
			{
				var form_w		= f.gallery_medium_width;
				var form_h		= f.gallery_medium_height;
				var form_wo		= f.medium_width_orig;
				var form_ho		= f.medium_height_orig;
				var constrain	= f.constrain_medium;			
			}
		
			var orig	= (side == "w") ? form_wo	: form_ho;
			var curr	= (side == "w") ? form_w 	: form_h;
			var t_orig	= (side == "h") ? form_wo	: form_ho;
			var t_curr	= (side == "h") ? form_w	: form_h;
		
			var ratio	= curr.value/orig.value;
			var res 	= Math.floor(ratio * t_orig.value);
		
			var max = <?php echo $this->max_size; ?>;
			
			if (res > max && constrain.checked)
			{
				alert("<?php echo $max_exceeded; ?>");
				t_curr.value = t_orig.value;
				curr.value = Math.min(curr.value, orig.value);
			}
			else if (curr.value > max)
			{
				alert("<?php echo $max_exceeded; ?>");
				
				curr.value = Math.min(curr.value, orig.value);
			}
			else
			{
				if (constrain.checked)
				{
					t_curr.value = res;
					t_orig.value = res;
				}
				
				orig.value = curr.value
			}
						
			return;
		}
				
		function watermark_test()
		{
			var base 	= "<?php echo $basepath; ?>&P=wm_tester";
			var item 	= document.forms[0];
			var wm_font = (item.gallery_wm_use_font[0].checked) ? 'y' : 'n';
			var wm_drop = (item.gallery_wm_use_drop_shadow[0].checked) ? 'y' : 'n';
			var text_color = item.gallery_wm_text_color.value;
			var shad_color = item.gallery_wm_shadow_color.value;
					
			if (item.gallery_wm_type[1].checked)
			{
				var wm_type = 't';			
			}
			else if (item.gallery_wm_type[2].checked)
			{
				var wm_type = 'g';
			}
			
			var theText = item.gallery_wm_text.value;
			
			theText = theText.replace('/;/g', '').replace('?', '');
											
			var loc = base + 
			'&gallery_wm_type=' + wm_type +
			'&gallery_wm_text=' + theText +	
			'&gallery_wm_image_path=' + item.gallery_wm_image_path.value +	
			'&gallery_wm_use_font=' + wm_font +
			'&gallery_wm_font=' + item.gallery_wm_font.value +	
			'&gallery_wm_font_size=' + item.gallery_wm_font_size.value +					
			'&gallery_wm_vrt_alignment=' + item.gallery_wm_vrt_alignment.value +					
			'&gallery_wm_hor_alignment=' + item.gallery_wm_hor_alignment.value +					
			'&gallery_wm_padding=' + item.gallery_wm_padding.value +					
			'&gallery_wm_x_offset=' + item.gallery_wm_x_offset.value +					
			'&gallery_wm_y_offset=' + item.gallery_wm_y_offset.value +					
			'&gallery_wm_x_transp=' + item.gallery_wm_x_transp.value +	
			'&gallery_wm_y_transp=' + item.gallery_wm_y_transp.value +	
			'&gallery_wm_text_color=' + text_color.substring(1) +					
			'&gallery_wm_use_drop_shadow=' + wm_drop +					
			'&gallery_wm_shadow_color=' + shad_color.substring(1) +					
			'&gallery_wm_shadow_distance=' + item.gallery_wm_shadow_distance.value +					
			'&gallery_wm_opacity=' + item.gallery_wm_opacity.value +					
			'&gallery_wm_test_image_path=' + item.gallery_wm_test_image_path.value;		
									
			window.open(loc, 'wm_tester','width=<?php echo $testwidth; ?>,height=<?php echo $testheight; ?>,screenX=0,screenY=0,top=0,left=0,toolbar=0,status=0,scrollbars=0,location=0,menubar=1,resizable=1');

			return FALSE;
		}
				
		function color_launch(field)
		{
			var base = "<?php echo $basepath; ?>&P=color_picker&Z=1&field=";
			var loc = base + field;
			
			window.open(loc, 'color_picker','width=420,height=560,screenX=0,screenY=0,top=0,left=0,toolbar=0,status=0,scrollbars=0,location=0,menubar=1,resizable=1');
			return FALSE;
		}
		
		function switch_wm_type()
		{	
			if (document.prefs.gallery_wm_type[0].checked)
			{ 
				document.getElementById('mid').style.display = "none";
				document.getElementById('text').style.display = "none";
				document.getElementById('graphic').style.display = "none";
				document.getElementById('test').style.display = "none";
			}
			else if (document.prefs.gallery_wm_type[1].checked)
			{
				document.getElementById('mid').style.display = "block";
				document.getElementById('text').style.display = "block";
				document.getElementById('graphic').style.display = "none";
				document.getElementById('test').style.display = "block";
				document.getElementById('test').style.margin = "0 0 4px 0";
			}
			else
			{
				document.getElementById('mid').style.display = "block";
				document.getElementById('text').style.display = "none";
				document.getElementById('graphic').style.display = "block";
				document.getElementById('test').style.display = "block";
				document.getElementById('test').style.margin = "0 0 4px 0";
				
			}
		}
		
			
		function showhide_pref(which)
		{
			off = which + '_off';
			on  = which + '_on';
		
			if (document.getElementById(off).style.display == "block")
			{
				document.getElementById(off).style.display = "none";
				document.getElementById(on).style.display = "block";				
			}
			else
			{
				document.getElementById(off).style.display = "block";
				document.getElementById(on).style.display = "none";
			}
		}
		
	
		var chunk	= new Array(12)
		chunk[0]  	= "gallery_general_config";
		chunk[1]  	= "gallery_paths";
		chunk[2]  	= "gallery_batch_prefs";
		chunk[3]  	= "gallery_caption_prefs";
		chunk[4]  	= "gallery_protocal_prefs";
		chunk[5]  	= "gallery_thumb_prefs";
		chunk[6]  	= "gallery_resize_prefs";
		chunk[7]  	= "gallery_watermark_prefs";
		chunk[8]  	= "gallery_custom_field_prefs";
		chunk[9]  	= "gallery_comment_prefs";
		chunk[10]  	= "gallery_comment_notification_prefs";
		chunk[11]  	= "gallery_watermark_prefs";

		
		var state = 'closed';
		
		function showhide_chunk()
		{
			for (i = 0 ; i < chunk.length; i++ )
			{								
				off = chunk[i] + '_off';
				on  = chunk[i] + '_on';
				
				if (state == 'closed' && document.getElementById(off).style.display)
				{
					document.getElementById(off).style.display = "block";
					document.getElementById(on).style.display = "none";	
				}
				else
				{
					document.getElementById(off).style.display = "none";
					document.getElementById(on).style.display = "block";
				}
			}
			
			if (state == 'closed')
				state = 'open';
			else
				state = 'closed';
		}
	
		function customFieldType(which, type)
		{
			if (type == 't')
			{
				document.getElementById(which + '_t').style.display = "block";
				document.getElementById(which + '_s').style.display = "none";
			}
			else if (type == 'i')
			{
				document.getElementById(which + '_t').style.display = "none";
				document.getElementById(which + '_s').style.display = "none";
			}
			else
			{
				document.getElementById(which + '_t').style.display = "none";
				document.getElementById(which + '_s').style.display = "block";
			}
		}
	
		</script>
		<?php
		
		$r .= ob_get_contents();
		ob_end_clean(); 
		
		// Show/hide link

		$this->EE->dsp->right_crumb($this->EE->lang->line('show_hide'), '', 'onclick="showhide_chunk();return false;"');
		$this->EE->dsp->body_props = " onload=\"switch_wm_type();" . (($gallery_wm_use_font == 'y') ? '' : "showhide_tablerow('wm_font_size_cell1','wm_font_size_cell2');") ."\"";
		


		
		/** -----------------------------
		/**  Custom Fields
		/** -----------------------------*/
		$cs = '<div id="gallery_custom_field_prefs_on" style="display: block; padding:0; margin: 0;">';		
		$cs .= $this->EE->dsp->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		$cs .= $this->EE->dsp->table_row(array(
									array(
											'text'			=> $expand.NBS.NBS.$this->EE->lang->line('gallery_custom_field_prefs'),
											'class'			=> 'tableHeadingAlt',
											'id'			=> 'custom_fields1',
											'colspan'		=> 4,
											'onclick'		=> 'showhide_pref("gallery_custom_field_prefs");return false;',
											'onmouseover'	=> 'navTabOn("custom_fields1", "tableHeadingAlt", "tableHeadingAltHover");',
											'onmouseout'	=> 'navTabOff("custom_fields1", "tableHeadingAlt", "tableHeadingAltHover");'
										)
									)
							);
		
		$cs .= $this->EE->dsp->table_close();
		$cs .= $this->EE->dsp->div_c();	
		  		  
		$cs .= '<div id="gallery_custom_field_prefs_off" style="display: none; padding:0; margin: 0;">';
		$cs .= $this->EE->dsp->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		$cs .= $this->EE->dsp->table_row(array(
									array(
											'text'			=> $collapse.NBS.NBS.$this->EE->lang->line('gallery_custom_field_prefs'),
											'class'			=> 'tableHeadingAlt',
											'id'			=> 'custom_fields2',
											'colspan'		=> 4,
											'onclick'		=> 'showhide_pref("gallery_custom_field_prefs");return false;',
											'onmouseover'	=> 'navTabOn("custom_fields2", "tableHeadingAlt", "tableHeadingAltHover");',
											'onmouseout'	=> 'navTabOff("custom_fields2", "tableHeadingAlt", "tableHeadingAltHover");'
										)
									)
							);

		$i = 0;
		
		/** -----------------------------
		/**  Custom Fields
		/** -----------------------------*/
			
		foreach (array('one', 'two', 'three', 'four', 'five', 'six') as $cfval)
		{	
			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		
			$field_enable		= 'gallery_cf_'.$cfval;
			$field_label		= 'gallery_cf_'.$cfval.'_label';
			$field_formatting	= 'gallery_cf_'.$cfval.'_formatting';
			$field_auto_link	= 'gallery_cf_'.$cfval.'_auto_link';
			$field_type			= 'gallery_cf_'.$cfval.'_type';
			$field_rows			= 'gallery_cf_'.$cfval.'_rows';
			$field_list			= 'gallery_cf_'.$cfval.'_list';
			$field_searchable	= 'gallery_cf_'.$cfval.'_searchable';
	
			$indent = NBS.NBS.NBS.'<img src="'.PATH_CP_GBL_IMG.'cat_marker.gif" border="0"  width="18" height="14" alt="" title="" />'.NBS.NBS;

			$cs .= $this->EE->dsp->table_row(array(
										array(
												'text'	=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_cf_'.$cfval)),
												'class'	=> $style,
												'width'	=> '50%'
											),
										array(
												'text'	=> $this->EE->dsp->input_radio('gallery_cf_'.$cfval, 'y', ($$field_enable == 'y') ? 1 : '').NBS.$this->EE->lang->line('yes').NBS.NBS.NBS.$this->EE->dsp->input_radio('gallery_cf_'.$cfval, 'n', ($$field_enable == 'n') ? 1 : '').NBS.$this->EE->lang->line('no'),
												'class'	=> $style,
												'width'	=> '50%'
											)
										)
								);
	
			$cs .= $this->EE->dsp->table_row(array(
										array(
												'text'	=> $this->EE->dsp->qdiv('defaultBold', $indent.$this->EE->lang->line('gallery_cf_label')),
												'class'	=> $style,
												'width'	=> '50%'
											),
										array(
												'text'	=> $this->EE->dsp->input_text('gallery_cf_'.$cfval.'_label', $$field_label, '40', '80', 'input', '100%'),
												'class'	=> $style,
												'width'	=> '50%'
											)
										)
								);
								
			$fmt  = $this->EE->dsp->input_select_header('gallery_cf_'.$cfval.'_formatting');				
			$fmt .= $this->EE->dsp->input_select_option('xhtml', $this->EE->lang->line('gallery_xhtml'), (($$field_formatting == 'xhmlt') ? 1 : 0));
			$fmt .= $this->EE->dsp->input_select_option('br', $this->EE->lang->line('gallery_br'), (($$field_formatting == 'br') ? 1 : 0));
			$fmt .= $this->EE->dsp->input_select_option('none', $this->EE->lang->line('gallery_none'), (($$field_formatting == 'none') ? 1 : 0));
			$fmt .= $this->EE->dsp->input_select_footer();
	
			$cs .= $this->EE->dsp->table_row(array(
										array(
												'text'	=> $this->EE->dsp->qdiv('defaultBold', $indent.$this->EE->lang->line('gallery_text_formatting')),
												'class'	=> $style,
												'width'	=> '50%'
											),
										array(
												'text'	=> $fmt,
												'class'	=> $style,
												'width'	=> '50%'
											)
										)
								);
	
	
			
			$cs .= $this->EE->dsp->table_row(array(
										array(
												'text'	=> $this->EE->dsp->qdiv('defaultBold', $indent.$this->EE->lang->line('gallery_auto_link_urls')),
												'class'	=> $style,
												'width'	=> '50%'
											),
										array(
												'text'	=> $this->EE->dsp->input_radio('gallery_cf_'.$cfval.'_auto_link', 'y', ($$field_auto_link == 'y') ? 1 : '').NBS.$this->EE->lang->line('yes').NBS.NBS.NBS.$this->EE->dsp->input_radio('gallery_cf_'.$cfval.'_auto_link', 'n', ($$field_auto_link == 'n') ? 1 : '').NBS.$this->EE->lang->line('no'),
												'class'	=> $style,
												'width'	=> '50%'
											)
										)
								);
	
			$type = '<select name="gallery_cf_'.$cfval.'_type" class="select" onchange="customFieldType(\'cf_'.$cfval.'\', this.options[this.selectedIndex].value);">';
			$type .= $this->EE->dsp->input_select_option('i', $this->EE->lang->line('input'), (($$field_type == 'i') ? 1 : 0));
			$type .= $this->EE->dsp->input_select_option('t', $this->EE->lang->line('textarea'), (($$field_type == 't') ? 1 : 0));
			$type .= $this->EE->dsp->input_select_option('s', $this->EE->lang->line('select'), (($$field_type == 's') ? 1 : 0));
			$type .= $this->EE->dsp->input_select_footer();
	
			$cf_t = ($$field_type == 't') ? 'block' : 'none';
			$cf_s = ($$field_type == 's') ? 'block' : 'none';
	
			$type .= '<div id="cf_'.$cfval.'_t" style="display: '.$cf_t.'; padding:0; margin: 0;">'.$this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->input_text('gallery_cf_'.$cfval.'_rows', $$field_rows, '6', '4', 'input', '40px').NBS.$this->EE->lang->line('textarea_rows')).'</div>';
			$type .= '<div id="cf_'.$cfval.'_s" style="display: '.$cf_s.'; padding:0; margin: 0;">'.$this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('list_items')).$this->EE->dsp->qdiv('', $this->EE->lang->line('list_items_note')).$this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->input_textarea('gallery_cf_'.$cfval.'_list', $$field_list, '14', 'textarea', '99%')).'</div>';
	
			$cs .= $this->EE->dsp->table_row(array(
										array(
												'text'		=> $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('defaultBold', $indent.$this->EE->lang->line('gallery_cf_type'))),
												'class'		=> $style,
												'width'		=> '50%',
												'valign'	=> 'top'
											),
										array(
												'text'	=> $type,
												'class'	=> $style,
												'width'	=> '50%'
											)
										)
								);
								
			$cs .= $this->EE->dsp->table_row(array(
										array(
												'text'	=> $this->EE->dsp->qdiv('defaultBold', $indent.$this->EE->lang->line('gallery_field_searchable')),
												'class'	=> $style,
												'width'	=> '50%'
											),
										array(
												'text'	=>  $this->EE->dsp->input_radio('gallery_cf_'.$cfval.'_searchable', 'y', ($$field_searchable == 'y') ? 1 : '')
															.	NBS
															.$this->EE->lang->line('yes')
															.	NBS.NBS.NBS
															.$this->EE->dsp->input_radio('gallery_cf_'.$cfval.'_searchable', 'n', ($$field_searchable == 'n') ? 1 : '')
															.	NBS
															.$this->EE->lang->line('no'),
												'class'	=> $style,
												'width'	=> '50%'
											)
										)
								);
		}
		$cs .= $this->EE->dsp->table_close();
		$cs .= $this->EE->dsp->div_c();	

		
		/** -----------------------------
		/**  Watermark TOP SECTION
		/** -----------------------------*/
		
		$top = '<div id="gallery_watermark_prefs_on" style="display: block; padding:0; margin: 0;">';		
		$top .= $this->EE->dsp->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		$top .= $this->EE->dsp->table_row(array(
									array(
											'text'			=> $expand.NBS.NBS.$this->EE->lang->line('gallery_watermark_prefs'),
											'class'			=> 'tableHeadingAlt',
											'id'			=> 'gallery_watermark_prefs1',
											'colspan'		=> 4,
											'onclick'		=> 'showhide_pref("gallery_watermark_prefs");return false;',
											'onmouseover'	=> 'navTabOn("gallery_watermark_prefs1", "tableHeadingAlt", "tableHeadingAltHover");',
											'onmouseout'	=> 'navTabOff("gallery_watermark_prefs1", "tableHeadingAlt", "tableHeadingAltHover");'
										)
									)
							);
		
		$top .= $this->EE->dsp->table_close();
		$top .= $this->EE->dsp->div_c();	
		  
		  
		  
		$top .= '<div id="gallery_watermark_prefs_off" style="display: none; padding:0; margin: 0;">';
		$top .= $this->EE->dsp->table_open(array('class' => 'tableBorder', 'width' => '100%'));
		$top .= $this->EE->dsp->table_row(array(
									array(
											'text'			=> $collapse.NBS.NBS.$this->EE->lang->line('gallery_watermark_prefs'),
											'class'			=> 'tableHeadingAlt',
											'id'			=> 'gallery_watermark_prefs2',
											'colspan'		=> 4,
											'onclick'		=> 'showhide_pref("gallery_watermark_prefs");return false;',
											'onmouseover'	=> 'navTabOn("gallery_watermark_prefs2", "tableHeadingAlt", "tableHeadingAltHover");',
											'onmouseout'	=> 'navTabOff("gallery_watermark_prefs2", "tableHeadingAlt", "tableHeadingAltHover");'
										)
									)
							);
	
		$i = 0;
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$top .= $this->EE->dsp->table_row(array(
									array(
											'text'		=> $this->EE->dsp->qdiv('galleryPrefHeading', $this->EE->lang->line('gallery_wm_type')),
											'class'		=> $style,
											'width'		=> '25%'
										),
									array(
											'text'		=> $this->EE->dsp->qdiv('galleryPrefHeading', $this->EE->dsp->input_radio('gallery_wm_type', 'n', ($gallery_wm_type == 'n') ? 1 : '', 'onclick="switch_wm_type();"').NBS.$this->EE->dsp->qspan('defaultBold', $this->EE->lang->line('none'))),
											'class'		=> $style,
											'width'		=> '25%'
										),
									array(
											'text'		=> $this->EE->dsp->qdiv('galleryPrefHeading', $this->EE->dsp->input_radio('gallery_wm_type', 't', ($gallery_wm_type == 't') ? 1 : '', 'onclick="switch_wm_type();"').NBS.$this->EE->dsp->qspan('defaultBold', $this->EE->lang->line('text'))),
											'class'		=> $style,
											'width'		=> '25%'
										),
									array(
											'text'		=> $this->EE->dsp->qdiv('galleryPrefHeading', $this->EE->dsp->input_radio('gallery_wm_type', 'g', ($gallery_wm_type == 'g') ? 1 : '', 'onclick="switch_wm_type();"').NBS.$this->EE->dsp->qspan('defaultBold', $this->EE->lang->line('graphic'))),
											'class'		=> $style,
											'width'		=> '25%'
										)
									)
							);
			
		$top .= $this->EE->dsp->table_close();
			 
		// -----------------------------------------------------------------------		
		// -----------------------------------------------------------------------		

		/** -----------------------------
		/**  Watermark GLOBAL CONTROLS
		/** -----------------------------*/
		
		// Alignment
	
		$mid = '';
		$mid .= $this->EE->dsp->table_open(array('class' => 'tableBorderNoBot', 'width' => '100%'));
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		
		$item  = $this->EE->dsp->input_select_header('gallery_wm_vrt_alignment');
		$item .= $this->EE->dsp->input_select_option('T', $this->EE->lang->line('gallery_top'), ($gallery_wm_vrt_alignment=='T') ? 1 : 0);
		$item .= $this->EE->dsp->input_select_option('M', $this->EE->lang->line('gallery_mid'), ($gallery_wm_vrt_alignment=='M') ? 1 : 0);
		$item .= $this->EE->dsp->input_select_option('B', $this->EE->lang->line('gallery_bot'), ($gallery_wm_vrt_alignment=='B') ? 1 : 0);
		$item .= $this->EE->dsp->input_select_footer();
	
		$item .= $this->EE->dsp->input_select_header('gallery_wm_hor_alignment');
		$item .= $this->EE->dsp->input_select_option('L', $this->EE->lang->line('gallery_left'), ($gallery_wm_hor_alignment=='L') ? 1 : 0);
		$item .= $this->EE->dsp->input_select_option('C', $this->EE->lang->line('gallery_center'), ($gallery_wm_hor_alignment=='C') ? 1 : 0);
		$item .= $this->EE->dsp->input_select_option('R', $this->EE->lang->line('gallery_right'), ($gallery_wm_hor_alignment=='R') ? 1 : 0);
		$item .= $this->EE->dsp->input_select_footer();

		$mid .= $this->EE->dsp->table_row(array(
									array(
											'text'	=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_wm_alignment')),
											'class'	=> $style,
											'width'	=> '50%'
										),
									array(
											'text'	=> $item,
											'class'	=> $style,
											'width'	=> '50%'
										)
									)
							);
		
		
		/** -----------------------------
		/**  Watermark Padding
		/** -----------------------------*/
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$mid .= $this->EE->dsp->table_row(array(
									array(
											'text'	=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_wm_padding')),
											'class'	=> $style,
											'width'	=> '50%'
										),
									array(
											'text'	=> $this->drop_menu_builder('gallery_wm_padding', 0, 30, $gallery_wm_padding),
											'class'	=> $style,
											'width'	=> '50%'
										)
									)
							);
		
		/** -----------------------------
		/**  Watermark H Offset
		/** -----------------------------*/
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$mid .= $this->EE->dsp->table_row(array(
									array(
											'text'	=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_wm_x_offset')),
											'class'	=> $style,
											'width'	=> '50%'
										),
									array(
											'text'	=> $this->EE->dsp->input_text('gallery_wm_x_offset', $gallery_wm_x_offset, '6', '4', 'input', '40px'),
											'class'	=> $style,
											'width'	=> '50%'
										)
									)
							);

		/** -----------------------------
		/**  Watermark V Offset
		/** -----------------------------*/
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$mid .= $this->EE->dsp->table_row(array(
									array(
											'text'	=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_wm_y_offset')),
											'class'	=> $style,
											'width'	=> '50%'
										),
									array(
											'text'	=> $this->EE->dsp->input_text('gallery_wm_y_offset', $gallery_wm_y_offset, '6', '4', 'input', '40px'),
											'class'	=> $style,
											'width'	=> '50%'
										)
									)
							);
		
		/** -----------------------------
		/**  Watermark Apply to thumbs
		/** -----------------------------*/
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$mid .= $this->EE->dsp->table_row(array(
									array(
											'text'	=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_wm_apply_thumbs')),
											'class'	=> $style,
											'width'	=> '50%'
										),
									array(
											'text'	=> $this->EE->lang->line('yes').NBS.$this->EE->dsp->input_radio('gallery_wm_apply_to_thumb', 'y', ($gallery_wm_apply_to_thumb == 'y') ? 1 : '').$this->EE->dsp->nbs(3).$this->EE->lang->line('no').NBS.$this->EE->dsp->input_radio('gallery_wm_apply_to_thumb', 'n', ($gallery_wm_apply_to_thumb == 'n') ? 1 : ''),
											'class'	=> $style,
											'width'	=> '50%'
										)
									)
							);
		
		/** -----------------------------
		/**  Watermark Apply to thumbs
		/** -----------------------------*/
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$mid .= $this->EE->dsp->table_row(array(
									array(
											'text'	=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_wm_apply_medium')),
											'class'	=> $style,
											'width'	=> '50%'
										),
									array(
											'text'	=> $this->EE->lang->line('yes').NBS.$this->EE->dsp->input_radio('gallery_wm_apply_to_medium', 'y', ($gallery_wm_apply_to_medium == 'y') ? 1 : '').$this->EE->dsp->nbs(3).$this->EE->lang->line('no').NBS.$this->EE->dsp->input_radio('gallery_wm_apply_to_medium', 'n', ($gallery_wm_apply_to_medium == 'n') ? 1 : ''),
											'class'	=> $style,
											'width'	=> '50%'
										)
									)
							);
	
		$mid .= $this->EE->dsp->table_close();
	
		// END --------		
		// -----------------------------------------------------------------------		
		


		/** -----------------------------
		/**  Text Watermark
		/** -----------------------------*/
		
		$txt = $this->EE->dsp->table_open(array('class' => 'tableBorderSides', 'width' => '100%'));
		
		/** -----------------------------
		/**  Watermark Text
		/** -----------------------------*/
		
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$txt .= $this->EE->dsp->table_row(array(
									array(
											'text'	=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_wm_text')),
											'class'	=> $style,
											'width'	=> '50%'
										),
									array(
											'text'	=> $this->EE->dsp->input_text('gallery_wm_text', $gallery_wm_text, '40', '100', 'input', '100%'),
											'class'	=> $style,
											'width'	=> '50%'
										)
									)
							);

		/** -----------------------------
		/**  Watermark Use Font
		/** -----------------------------*/
		
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$txt .= $this->EE->dsp->table_row(array(
									array(
											'text'	=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_wm_use_font')),
											'class'	=> $style,
											'width'	=> '50%'
										),
									array(
											'text'	=> $this->EE->lang->line('yes').NBS.$this->EE->dsp->input_radio('gallery_wm_use_font', 'y', ($gallery_wm_use_font == 'y') ? 1 : '',' onclick="showhide_tablerow(\'wm_font_size_cell1\',\'wm_font_size_cell2\');"').$this->EE->dsp->nbs(3).$this->EE->lang->line('no').NBS.$this->EE->dsp->input_radio('gallery_wm_use_font', 'n', ($gallery_wm_use_font == 'n') ? 1 : '', ' onclick="showhide_tablerow(\'wm_font_size_cell1\',\'wm_font_size_cell2\');"'),
											'class'	=> $style,
											'width'	=> '50%'
										)
									)
							);
					

		/** -----------------------------
		/**  Watermark Font
		/** -----------------------------*/
		
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$txt .= $this->EE->dsp->table_row(array(
									array(
											'text'	=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_wm_font')),
											'class'	=> $style,
											'width'	=> '50%'
										),
									array(
											'text'	=> $this->fetch_fontlist($gallery_wm_font),
											'class'	=> $style,
											'width'	=> '50%'
										)
									)
							);
					
		
		/** -----------------------------
		/**  Watermark Font Size
		/** -----------------------------*/
		
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$txt .= $this->EE->dsp->table_row(array(
									array(
											'text'	=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_wm_font_size')),
											'class'	=> $style,
											'id' => 'wm_font_size_cell1',
											'width'	=> '50%'
										),
									array(
											'text'	=> $this->EE->dsp->input_text('gallery_wm_font_size', $gallery_wm_font_size, '6', '4', 'input', '40px'),
											'class'	=> $style,
											'id' => 'wm_font_size_cell2',
											'width'	=> '50%'
										)
									)
							);
							
		/** -----------------------------
		/**  Watermark Text Color
		/** -----------------------------*/
		
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$txt .= $this->EE->dsp->table_row(array(
									array(
											'text'	=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_wm_text_color')),
											'class'	=> $style,
											'width'	=> '50%'
										),
									array(
											'text'	=> $this->EE->dsp->input_text('gallery_wm_text_color', $gallery_wm_text_color, '10', '7', 'input', '65px').NBS."<a href=\"\" onclick=\"javascript:color_launch('gallery_wm_text_color'); return false;\"><img src='".PATH_CP_GBL_IMG."colorbox.gif' width='16' height='16' border='0' title='Open Color Browser' /></a>",
											'class'	=> $style,
											'width'	=> '50%'
										)
									)
							);
					
		/** -----------------------------
		/**  Watermark Use Drop
		/** -----------------------------*/
		
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$txt .= $this->EE->dsp->table_row(array(
									array(
											'text'	=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_wm_use_drop_shadow')),
											'class'	=> $style,
											'width'	=> '50%'
										),
									array(
											'text'	=> $this->EE->lang->line('yes').$this->EE->dsp->nbs().$this->EE->dsp->input_radio('gallery_wm_use_drop_shadow', 'y', ($gallery_wm_use_drop_shadow == 'y') ? 1 : '').$this->EE->dsp->nbs(3).$this->EE->lang->line('no').$this->EE->dsp->nbs().$this->EE->dsp->input_radio('gallery_wm_use_drop_shadow', 'n', ($gallery_wm_use_drop_shadow == 'n') ? 1 : '').$this->EE->dsp->nbs(3),
											'class'	=> $style,
											'width'	=> '50%'
										)
									)
							);
					

		/** -----------------------------
		/**  Watermark DS Distance
		/** -----------------------------*/
		
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$txt .= $this->EE->dsp->table_row(array(
									array(
											'text'	=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_wm_shadow_distance')),
											'class'	=> $style,
											'width'	=> '50%'
										),
									array(
											'text'	=> $this->drop_menu_builder('gallery_wm_shadow_distance', 0, 20, $gallery_wm_shadow_distance),
											'class'	=> $style,
											'width'	=> '50%'
										)
									)
							);
					
		/** -----------------------------
		/**  Watermark DS Color
		/** -----------------------------*/
		
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$txt .= $this->EE->dsp->table_row(array(
									array(
											'text'	=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_wm_shadow_color')),
											'class'	=> $style,
											'width'	=> '50%'
										),
									array(
											'text'	=> $this->EE->dsp->input_text('gallery_wm_shadow_color', $gallery_wm_shadow_color, '10', '7', 'input', '65px').NBS."<a href=\"\" onclick=\"javascript:color_launch('gallery_wm_shadow_color'); return false;\"><img src='".PATH_CP_GBL_IMG."colorbox.gif' width='16' height='16' border='0' title='Open Color Browser' /></a>",
											'class'	=> $style,
											'width'	=> '50%'
										)
									)
							);


		// Close the table
		$txt .= $this->EE->dsp->table_close();

		// END --------		
		// -----------------------------------------------------------------------		
		
		/** -----------------------------
		/**  Graphic Watermark Heading
		/** -----------------------------*/
		
		$gfx = $this->EE->dsp->table_open(array('class' => 'tableBorderSides', 'width' => '100%'));
		
		/** -----------------------------
		/**  Watermark Text
		/** -----------------------------*/
		
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$gfx .= $this->EE->dsp->table_row(array(
									array(
											'text'	=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_wm_image_path')),
											'class'	=> $style,
											'width'	=> '50%'
										),
									array(
											'text'	=> $this->EE->dsp->input_text('gallery_wm_image_path', $gallery_wm_image_path, '40', '100', 'input', '100%'),
											'class'	=> $style,
											'width'	=> '50%'
										)
									)
							);

		/** -----------------------------
		/**  Watermark Opacity
		/** -----------------------------*/
		
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$gfx .= $this->EE->dsp->table_row(array(
									array(
											'text'	=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_wm_opacity')),
											'class'	=> $style,
											'width'	=> '50%'
										),
									array(
											'text'	=> $this->drop_menu_builder('gallery_wm_opacity', 100, 1, $gallery_wm_opacity),
											'class'	=> $style,
											'width'	=> '50%'
										)
									)
							);
				
		/** -----------------------------
		/**  Watermark transparancy color
		/** -----------------------------*/
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$gfx .= $this->EE->dsp->table_row(array(
									array(
											'text'	=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_wm_x_transp')),
											'class'	=> $style,
											'width'	=> '50%'
										),
									array(
											'text'	=> $this->EE->dsp->input_text('gallery_wm_x_transp', $gallery_wm_x_transp, '6', '4', 'input', '40px'),
											'class'	=> $style,
											'width'	=> '50%'
										)
									)
							);
		
		$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
		$gfx .= $this->EE->dsp->table_row(array(
									array(
											'text'	=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_wm_y_transp')),
											'class'	=> $style,
											'width'	=> '50%'
										),
									array(
											'text'	=> $this->EE->dsp->input_text('gallery_wm_y_transp', $gallery_wm_y_transp, '6', '4', 'input', '40px'),
											'class'	=> $style,
											'width'	=> '50%'
										)
									)
							);

		$gfx .= $this->EE->dsp->table_close();

		// END --------			
		// -------------------------------------------------------------------------------		


		// Check for validity of watermark testing
		
		$tst = '';
		
		if ($this->gallery_id != FALSE)
		{
			/** -----------------------------
			/**  Watermark Text
			/** -----------------------------*/
			$tst = $this->EE->dsp->table_open(array('class' => 'tableBorderSides', 'width' => '100%'));

			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			$tst .= $this->EE->dsp->table_row(array(
										array(
												'text'	=> $this->EE->dsp->qdiv('defaultBold', $this->EE->lang->line('gallery_wm_test_image_path')).$this->EE->dsp->qdiv('', $this->EE->lang->line('gallery_test_explain')),
												'class'	=> $style,
												'width'	=> '50%'
											),
										array(
												'text'	=> $this->EE->dsp->input_text('gallery_wm_test_image_path', $gallery_wm_test_image_path, '40', '100', 'input', '100%'),
												'class'	=> $style,
												'width'	=> '50%'
											)
										)
								);


			/** -----------------------------
			/**  Watermark Test Button
			/** -----------------------------*/

			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
			$tst .= $this->EE->dsp->table_row(array(
										array(
												'text'		=> $this->EE->dsp->qdiv('highlight_bold', $this->EE->lang->line('watermark_test_warning')).
																$this->EE->dsp->qdiv('itemWrapperTop', $this->EE->dsp->input_submit(NBS.$this->EE->lang->line('gallery_test_now').NBS, '', "onclick='return watermark_test();'")),
												'class'		=> $style,
												'width'		=> '50%',
												'colspan'	=> 2
											)
										)
								);

			$tst .= $this->EE->dsp->table_close();			
		} // end check for watermark test


		/** -----------------------------
		/**  Build the Watermark Form
		/** -----------------------------*/
		$wm  = $top;
		
		$wm .= '<div id="mid" style="display: none; padding:0;">';		
		$wm .= $mid;
		$wm .= '</div>';								
		
		$wm .= '<div id="text" style="display: none; padding:0;">';
		$wm .= $txt;
		$wm .= '</div>';								
		
		$wm .= '<div id="graphic" style="display: none; padding:0;">';
		$wm .= $gfx;
		$wm .= '</div>';								
			
		$wm .= '<div id="test" style="display: none; padding:0;">';
		$wm .= $tst;
		$wm .= '</div>';	
		
		$wm .= $this->EE->dsp->div_c();
						
		
		/** ---------------------------------------
		/**  Success Message
		/** ---------------------------------------*/
		
		if ($this->EE->input->get_post('action') == 'new')
		{
			$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('successBox', $this->EE->dsp->qdiv('success', $this->EE->lang->line('gallery_created'))));						 
		}
		elseif ($this->EE->input->get_post('action') == 'update')
		{
			$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('successBox', $this->EE->dsp->qdiv('success', $this->EE->lang->line('gallery_updated'))));						 
		}

		$r .= $this->EE->dsp->qdiv('tableHeading', $this->EE->lang->line('gallery_preferences'));
	
		if ($this->gallery_id == '')
		{
			$r .= $this->EE->dsp->qdiv('box', $this->EE->dsp->qdiv('highlight_alt', '<b>'.$this->EE->lang->line('gallery_pref_inst').'</b>'));
		}

		/** ---------------------------------------
		/**  Render the preference matrix
		/** ---------------------------------------*/
		
		$fixeroo = array('gallery_thumb_width', 'gallery_thumb_height', 'gallery_medium_width', 'gallery_medium_height');
		
		foreach($fixeroo as $fix_these)
		{
			if ($$fix_these == 0) $$fix_these = 1;
		}
		
		$r .= $this->EE->dsp->form_open(array('action' => 'C=modules'.AMP.'M=gallery'.AMP.'P=prefs_submission_handler', 'name' => 'prefs', 'id' => 'prefs'));
		$r .= $this->EE->dsp->input_hidden('gallery_id', $this->gallery_id);
		$r .= $this->EE->dsp->input_hidden('thumb_width_orig',  $gallery_thumb_width);
		$r .= $this->EE->dsp->input_hidden('thumb_height_orig', $gallery_thumb_height);
		$r .= $this->EE->dsp->input_hidden('medium_width_orig',  $gallery_medium_width);
		$r .= $this->EE->dsp->input_hidden('medium_height_orig', $gallery_medium_height);
		$r .= $this->EE->dsp->input_hidden('template_group', 	 ( ! isset($template_group)) ? '' : $template_group);

		if ($this->gallery_id == FALSE)
		{
			$r .= $this->EE->dsp->input_hidden('gallery_wm_test_image_path',  $gallery_wm_test_image_path);
		}
				
		foreach ($menu as $m_key => $m_val)
		{
			if ($m_key == 'gallery_watermark_prefs')
			{
				$r .= $wm;
				continue;
			}
			
			if ($m_key == 'gallery_custom_field_prefs')
			{
				$r .= $cs;
				continue;
			}

			$r .= '<div id="'.$m_key.'_on" style="display: block; padding:0; margin: 0;">';
			
			$r .= $this->EE->dsp->table_open(array('class' => 'tableBorder', 'width' => '100%'));
			$r .= $this->EE->dsp->table_row(array(
										array(
												'text'			=> $expand.NBS.NBS.$this->EE->lang->line($m_key).(($this->EE->config->item('demo_date') !== FALSE && $m_key == 'gallery_batch_prefs') ? $this->EE->dsp->qspan('alert', ' - Not Available With Demo') : '' ),
												'class'			=> 'tableHeadingAlt',
												'id'			=> $m_key.'1',
												'colspan'		=> 2,
												'onclick'		=> 'showhide_pref("'.$m_key.'");return false;',
												'onmouseover'	=> 'navTabOn("'.$m_key.'1", "tableHeadingAlt", "tableHeadingAltHover");',
												'onmouseout'	=> 'navTabOff("'.$m_key.'1", "tableHeadingAlt", "tableHeadingAltHover");'
											)
										)
								);
			
			$r .= $this->EE->dsp->table_close();
			$r .= $this->EE->dsp->div_c();	
			  
					  
			$r .= '<div id="'.$m_key.'_off" style="display: none; padding:0; margin: 0;">';			
			$r .= $this->EE->dsp->table_open(array('class' => 'tableBorder', 'width' => '100%'));
			
			$r .= $this->EE->dsp->table_row(array(
										array(
												'text'			=> $collapse.NBS.NBS.$this->EE->lang->line($m_key),
												'class'			=> 'tableHeadingAlt',
												'id'			=> $m_key.'2',
												'colspan'		=> 2,
												'onclick'		=> 'showhide_pref("'.$m_key.'");return false;',
												'onmouseover'	=> 'navTabOn("'.$m_key.'2", "tableHeadingAlt", "tableHeadingAltHover");',
												'onmouseout'	=> 'navTabOff("'.$m_key.'2", "tableHeadingAlt", "tableHeadingAltHover");'
											)
										)
								);
	
			$i = 0;
			foreach($m_val as $key => $val)
			{				
 				$inner = $this->EE->dsp->div((isset($val['2']) ? 'galleryPrefsN' : 'default'));

				if ($val['0'] == 't')
				{
					$inner .= $this->EE->dsp->input_text($key, $$key, '15', '100', 'input', '100%');
				}
				elseif ($val['0'] == 'p')
				{
					$inner .= $this->EE->dsp->input_text($key, $$key, '10', '4', 'input', '45px');
				}
				elseif ($val['0'] == 'x')
				{
					$inner .= $this->EE->dsp->input_text($key, $$key, '10', '4', 'input', '45px');
					$inner .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->input_checkbox('apply_expiration_to_existing', 'y', 0).' '.$this->EE->lang->line('gallery_update_expiration'));
				}
				elseif ($val['0'] == 'r')
				{
					foreach ($val['1'] as $k => $v)
					{
						$selected = ($k == $$key) ? 1 : '';
						$inner .= $this->EE->lang->line($v).$this->EE->dsp->nbs();
						$inner .= $this->EE->dsp->input_radio($key, $v, $selected).$this->EE->dsp->nbs(3);
					}
				}
				elseif ($val['0'] == 'd')
				{
					$inner .= $this->EE->dsp->input_select_header($key);
					foreach ($val['1'] as $k => $v)
					{					
						$inner .= $this->EE->dsp->input_select_option($k, $this->EE->lang->line($v), ($k == $$key) ? 1 : '');
					}
					$this->EE->dsp->body .= $this->EE->dsp->input_select_footer();
				}
				elseif ($val['0'] == 's')
				{
					$inner .= $this->EE->dsp->input_text('gallery_thumb_width', $gallery_thumb_width, '6', '4', 'input', '40px', " onchange=\"change_thumb_value(this.form, 'w', this.name);\" ").NBS;
					$inner .= $this->EE->dsp->input_text('gallery_thumb_height', $gallery_thumb_height, '6', '4', 'input', '40px', " onchange=\"change_thumb_value(this.form, 'h', this.name);\" ").NBS;
					$inner .= $this->EE->dsp->input_checkbox('constrain_thumb', 'y', 1).' '.$this->EE->lang->line('gallery_constrain');
				}
				elseif ($val['0'] == 'm')
				{
					$inner .= $this->EE->dsp->input_text('gallery_medium_width', $gallery_medium_width, '6', '4', 'input', '40px', " onchange=\"change_thumb_value(this.form, 'w', this.name);\" ").NBS;
					$inner .= $this->EE->dsp->input_text('gallery_medium_height', $gallery_medium_height, '6', '4', 'input', '40px', " onchange=\"change_thumb_value(this.form, 'h', this.name);\" ").NBS;
					$inner .= $this->EE->dsp->input_checkbox('constrain_medium', 'y', 1).' '.$this->EE->lang->line('gallery_constrain');
				}

				$sub = (isset($submenu[$key]))  ? $this->EE->dsp->qdiv('default', $this->EE->lang->line($submenu[$key])) : '';
				$req = (in_array($key, $required)) ? $this->EE->dsp->required() : '';
				
				$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo';
				$r .= $this->EE->dsp->table_row(array(
											array(
													'text'		=> $this->EE->dsp->qdiv('defaultBold', $req.$this->EE->lang->line($key)).$sub,
													'class'		=> $style,
													'width'		=> '50%',
													'valign'	=> 'middle'
												),
											array(
													'text'		=> $inner,
													'class'		=> $style,
													'width'		=> '50%',
													'valign'	=> 'middle'
												)
											)
									);

			}
	
			$r .= $this->EE->dsp->table_c();
			$r .= $this->EE->dsp->div_c();
			
		}	  	
		
		/** ---------------------------------------
		/**  Submit button
		/** ---------------------------------------*/
		
		$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->required(1));
		$r .= $this->EE->dsp->qdiv('', $this->EE->dsp->input_submit((($this->gallery_id == '') ? $this->EE->lang->line('submit') : $this->EE->lang->line('update'))));
		$r .= $this->EE->dsp->form_close();
 
		
		if ($this->EE->input->get_post('menu') == 1)
		{
			$this->content_wrapper($this->EE->lang->line('gallery_preferences'), $this->EE->dsp->crumb_item($this->EE->lang->line('gallery_preferences')), $r);
		}
		else
		{
			$this->EE->dsp->title  = $this->EE->lang->line('gallery_new_gallery_form');
			$this->EE->dsp->crumb  = $this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery', $this->EE->lang->line('gallery_image_galleries')).$this->EE->dsp->crumb_item($this->EE->lang->line('gallery_new_gallery_form'));
			
			$this->EE->dsp->body = $r;
		}
	}

	
	
	
	/** ----------------------------------------
	/**  Color picker
	/** ----------------------------------------*/
	
	function color_picker()
	{
		$this->EE->dsp->title = $this->EE->lang->line('gallery_color_picker');
		
		$field = $this->EE->input->get_post('field');
		
		
		ob_start();
		?>
		<script type="text/javascript">
			
			function colorchange(color)
			{
				opener.document.prefs.<?php echo $field; ?>.value = color;
				opener.document.prefs.<?php echo $field; ?>.focus;
			
				window.close();
			}
			
		</script>
		<?php
		
		$this->EE->dsp->extra_header = ob_get_contents();
				
		ob_end_clean(); 

		require APPPATH.'config/colors.php';
		
		$this->EE->dsp->breadcrumb = FALSE;
		
		$this->EE->dsp->body = "<table cellpadding='0' cellspacing='0' border='0' align='center'>";
							
		$i = 0;
		
		foreach ($colors as $val)
		{
			if ($i == 0)
			{
				$this->EE->dsp->body .= $this->EE->dsp->tr();
			}
		
			$this->EE->dsp->body .= "<td style='background-color:".$val.";width:24px;border:1px solid #333;' border='0' onmouseover=\"this.style.cursor='pointer'\" onclick=\"colorchange('".$val."');\">&nbsp;<br />&nbsp;</td>";
		
			$i++;
			
			if ($i == 10)
			{
				$this->EE->dsp->body .= $this->EE->dsp->tr_c();
				$i = 0;
			}
		}
		
		$this->EE->dsp->body .= $this->EE->dsp->table_c();	
	
	}

	
	
	
	/** ----------------------------------------
	/**  Fetch a list of installed fonts
	/** ----------------------------------------*/
	function fetch_fontlist($default)
	{
		$path = BASEPATH.'/fonts/';
		
		$r = '';

		if ($fp = @opendir($path))
		{ 
			$r .= $this->EE->dsp->input_select_header('gallery_wm_font');			
			
			while (FALSE !== ($file = readdir($fp)))
			{
				if (substr($file, -4) == '.ttf')
				{
					$name = substr($file, 0, -4);
				
					$selected = ($file == $default) ? 1 : '';
					
					$name = ucwords(str_replace("_", " ", $name));
					
					$r .= $this->EE->dsp->input_select_option($file, $name, $selected);
				}
			}		 
			
			$r .= $this->EE->dsp->input_select_footer();
			
			closedir($fp); 
		} 

		return $r;
	}

	
	
	
	/** ----------------------------------------
	/**  Numeric Drop-down menu builder
	/** ----------------------------------------*/
	function drop_menu_builder($name, $start, $end, $selected='')
	{
		$r  = $this->EE->dsp->input_select_header($name);
		
		if ($start < $end)
		{
			for ($i=$start; $i<$end+1; $i++)
			{
				$r .= $this->EE->dsp->input_select_option($i, $i, ($i==$selected) ? 1 : 0);
			}
		}
		else
		{
			for ($i=$start; $i>$end-1; $i--)
			{
				$r .= $this->EE->dsp->input_select_option($i, $i, ($i==$selected) ? 1 : 0);
			}
		}
		$r .= $this->EE->dsp->input_select_footer();
		
		return $r;
	}


	
	/** ----------------------------------------
	/**  Update Gallery Preferences
	/** ----------------------------------------*/
	
	function prefs_submission_handler()
	{
		// Error checks
		
		$errors = array();
		
		if ( ! $this->EE->input->post('gallery_full_name'))
		{
			$errors[] = $this->EE->lang->line('gallery_missing_full_name');
		}
		
		if ( ! $this->EE->input->post('gallery_short_name'))
		{
			$errors[] = $this->EE->lang->line('gallery_missing_short_name');
		}
		else
		{
			if ( ! preg_match("#^[a-zA-Z0-9_\-/]+$#i", $this->EE->input->post('gallery_short_name')))
			{
				$errors[] = $this->EE->lang->line('gallery_illegal_shortname');
			}
		}
		
		if ( ! $this->EE->input->post('gallery_url'))
		{
			$errors[] = $this->EE->lang->line('gallery_missing_full_url');
		}
		
		if (count($errors) > 0)
		{
			return $this->EE->dsp->error_message($errors);
		}
		
		// Make sure the upload and batch folder is part of the path
		
		$_POST['gallery_upload_folder'] =  trim(str_replace("/", "", $_POST['gallery_upload_folder']));
		
		$paths = array(
						'gallery_upload_path'	=> 'gallery_upload_folder', 
						'gallery_image_url' 	=> 'gallery_upload_folder',
						'gallery_batch_path' 	=> 'gallery_batch_folder',
						'gallery_batch_url'		=> 'gallery_batch_folder'
						 );
		
		foreach($paths as $key => $val)
		{
			if ($_POST[$key] != '' AND $_POST[$val] != '')
			{
				$dir  = $_POST[$val];
				$path = $_POST[$key];
				
				$path = rtrim($path, '/');
				
				$mpath = substr($path, 0, -strlen($dir)).$dir;
				
				if ($mpath != $path)
				{
					$path .= '/'.$dir;					
				}
				
				$_POST[$key] = $path;
			}
		}
		
		$_POST['gallery_upload_path']	= ($_POST['gallery_upload_path'] != '') ? $this->EE->functions->remove_double_slashes($_POST['gallery_upload_path'].'/') : '';
		$_POST['gallery_image_url']		= ($_POST['gallery_image_url'] != '') ? $this->EE->functions->remove_double_slashes($_POST['gallery_image_url'].'/') : '';
		$_POST['gallery_batch_path']	= ($_POST['gallery_batch_path'] != '') ? $this->EE->functions->remove_double_slashes($_POST['gallery_batch_path'].'/') : '';
		$_POST['gallery_batch_url']		= ($_POST['gallery_batch_url'] != '') ? $this->EE->functions->remove_double_slashes($_POST['gallery_batch_url'].'/') : '';
		
		if (strncmp($this->prefs['gallery_thumb_prefix'], '_', 1) != 0 && strncmp($this->prefs['gallery_thumb_prefix'], '-', 1) != 0)
		{
			$_POST['gallery_thumb_prefix'] = "_".$_POST['gallery_thumb_prefix'];			
		}	
		
		if (strncmp($this->prefs['gallery_medium_prefix'], '_', 1) != 0 && strncmp($this->prefs['gallery_medium_prefix'], '-', 1) != 0)
		{
			$_POST['gallery_medium_prefix'] = "_".$_POST['gallery_medium_prefix'];
		}
		
		$template_group = $_POST['template_group'];
		
		// Remove this once we add comment moderation
		
		if ( ! isset($_POST['gallery_comment_moderate']))
		{
			$_POST['gallery_comment_moderate'] = 'n';			
		}
		
		// Update comment expiration
			
		if (isset($_POST['apply_expiration_to_existing']))
		{			
			$this->update_comment_expiration($this->gallery_id, $_POST['gallery_comment_expiration']);
		}
		
		// Set default custom field labels
		
		foreach (array(
						'gallery_cf_one_label' 		=> 'Custom Field One',
						'gallery_cf_two_label' 		=> 'Custom Field Two',
						'gallery_cf_three_label'	=> 'Custom Field Three',
						'gallery_cf_four_label' 	=> 'Custom Field Four',
						'gallery_cf_five_label' 	=> 'Custom Field Five',
						'gallery_cf_six_label' 		=> 'Custom Field Six',
						
						) as $key => $val 
					)
					{
						if ($_POST[$key] == '')
							$_POST[$key] = $val;
					}
					
		foreach (array('gallery_thumb_width', 'gallery_thumb_height', 'gallery_medium_width', 'gallery_medium_height') as $value)
		{
			$_POST[$value] = floor($_POST[$value]);
			if ($_POST[$value] == 0) $_POST[$value] = 1;
		}
						
		
		// Unset the POST variables that we don't need
		
		unset($_POST['thumb_width_orig']);
		unset($_POST['thumb_height_orig']);
		unset($_POST['medium_width_orig']);
		unset($_POST['medium_height_orig']);
		unset($_POST['constrain_thumb']);
		unset($_POST['constrain_medium']);
		unset($_POST['template_group']);
		unset($_POST['apply_expiration_to_existing']);

		/** ------------------------------
		/**  Create New Gallery
		/** ------------------------------*/
  
		if ($this->gallery_id == '')
		{
			unset($_POST['gallery_id']);
					
			$this->EE->db->query($this->EE->db->insert_string('exp_galleries', $_POST));
			$gallery_id = $this->EE->db->insert_id();
			$action = 'new';		
		}
		else  // Update existing gallery
		{
			unset($_POST['gallery_id']);
					
			$this->EE->db->query($this->EE->db->update_string('exp_galleries', $_POST, "gallery_id='{$this->gallery_id}'"));
			$gallery_id = $this->gallery_id;
			$action = 'update';
		}
	
		/** ------------------------------
		/**  Create Templates
		/** ------------------------------*/
		
		if ($this->gallery_id == '')
		{
			require PATH_MOD.'gallery/tmpl.gallery'.EXT;
		
			$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_template_groups WHERE");
			$group_order = $query->row('count')  +1;
		
			$this->EE->db->query(
						$this->EE->db->insert_string(
											 'exp_template_groups', 
											  array(
													 'site_id'		=> $this->EE->config->item('site_id'),
													 'group_name'	  => $template_group,
													 'group_order'	 => $group_order,
													 'is_site_default' => 'n'
													)
											)	  
						);
						
			$group_id = $this->EE->db->insert_id();
		
				foreach ($template as $key => $val)
				{
					$type = (strpos($key, 'css') !== FALSE) ? 'css' : 'webpage';
					
					$val = str_replace('{TMPL_template_group_name}', $template_group, $val);
					$val = str_replace('{TMPL_gallery_name}', 		$_POST['gallery_short_name'], $val);
				
				$data = array(
								'site_id'		=> $this->EE->config->item('site_id'),
								'group_id'		=> $group_id,
								'template_name'  => $key,
								'template_type'  => $type,
								'template_data'  => $val,
								'edit_date'		 => $this->EE->localize->now
							 );
	
				$this->EE->db->query($this->EE->db->insert_string('exp_templates', $data));				
				}				
		}
		
		
		$this->EE->functions->redirect(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=gallery_prefs_form'.AMP.'gallery_id='.$gallery_id.AMP.'action='.$action.AMP.'menu=1');
		exit;
	}

  
  

	/** -------------------------------------------
	/**  Update entries with comment expiration
	/** -------------------------------------------*/
	function update_comment_expiration($gallery_id = '', $expiration = '')
	{
		if ($gallery_id == '')
		{
				return FALSE;
		}
		
		$time = $expiration * 86400;
		
		$query = $this->EE->db->query("SELECT entry_id, entry_date FROM exp_gallery_entries WHERE gallery_id = '$gallery_id'");
		
		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$expdate = ($time > 0) ? $row['entry_date'] + $time : 0;
			
				$this->EE->db->query("UPDATE exp_gallery_entries SET comment_expiration_date = '$expdate' WHERE entry_id = '".$row['entry_id']."'");
			}
		}
		
		return;	
	}

  
  
  
  

	/** ----------------------------------------
	/**  Delete Gallery - Confirmation Message
	/** ----------------------------------------*/
	function delete_gallery_confirm()
	{
		$this->EE->dsp->title = $this->EE->lang->line('gallery_delete');
		$this->EE->dsp->crumb  = $this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery', $this->EE->lang->line('gallery_image_galleries')).$this->EE->dsp->crumb_item($this->EE->lang->line('gallery_delete'));
		
		
		$this->EE->dsp->body = $this->EE->dsp->form_open(array('action' => 'C=modules'.AMP.'M=gallery'.AMP.'P=delete_gallery'))
					.$this->EE->dsp->input_hidden('gallery_id', $this->gallery_id)
					.$this->EE->dsp->qdiv('alertHeading', $this->EE->lang->line('gallery_delete'))
					.$this->EE->dsp->div('box')
					.$this->EE->dsp->qdiv('itemWrapper', '<b>'.$this->EE->lang->line('gallery_delete_confirmation').'</b>')
					.$this->EE->dsp->qdiv('itemWrapper', '<i>'.$this->prefs['gallery_full_name'].'</i>')
					.$this->EE->dsp->qdiv('alert', BR.$this->EE->lang->line('action_can_not_be_undone'))
					.$this->EE->dsp->qdiv('itemWrapper', BR.$this->EE->dsp->input_submit($this->EE->lang->line('delete')))
					.$this->EE->dsp->div_c()
					.$this->EE->dsp->form_close();

	}

  
  
	/** ----------------------------------------
	/**  Delete Gallery
	/** ----------------------------------------*/
	function delete_gallery()
	{
		$query = $this->EE->db->query("DELETE FROM exp_galleries WHERE gallery_id = '{$this->gallery_id}'");
		$query = $this->EE->db->query("DELETE FROM exp_gallery_categories WHERE gallery_id = '{$this->gallery_id}'");
		$query = $this->EE->db->query("DELETE FROM exp_gallery_entries WHERE gallery_id = '{$this->gallery_id}'");
		$query = $this->EE->db->query("DELETE FROM exp_gallery_comments WHERE gallery_id = '{$this->gallery_id}'");

		$message = $this->EE->dsp->qdiv('success', $this->EE->lang->line('gallery_deleted'));

		$this->index($message);
	}

  




	/** ----------------------------------------
	/**  Image Toolbox
	/** ----------------------------------------*/
	
	function image_toolbox()
	{
		/** ------------------------------------
		/**  Enable the horizontal navigation
		/** ------------------------------------*/
		
		$this->horizontal_nav = TRUE;
		
		$title = $this->EE->lang->line('gallery_image_toolbox');
		$crumb = $this->EE->dsp->crumb_item($this->EE->lang->line('gallery_image_toolbox'));
				
		/** ------------------------------------
		/**  Fetch Preferences
		/** ------------------------------------*/
	
		$menu_choice	= $this->EE->input->get_post('menu_choice');
		$directory_url	= $this->EE->functions->remove_double_slashes($this->prefs['gallery_image_url'].'/');
		$self_location	= BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=image_toolbox'.AMP.'gallery_id='.$this->gallery_id.AMP.'menu_choice=';

		if (strpos($menu_choice, '/') !== FALSE)
		{
			$x = explode("/", $menu_choice);		
			$file_name = end($x);
		}
		else
		{
			$file_name = $menu_choice;
		}

		$max_exceeded = str_replace("%s", $this->max_size, $this->EE->lang->line('gallery_max_size'));

		/** ------------------------------------
		/**  Fetch the JavaScript Libary
		/** ------------------------------------*/
		
		// We'll add it just inside the <body> tag
				
		if ($js = $this->EE->dsp->file_open(APPPATH.'javascript/wz_dragdrop.js'))
		{
			$this->EE->dsp->initial_body = '<script type="text/javascript">';
			$this->EE->dsp->initial_body .= '<!--';
			$this->EE->dsp->initial_body .= "\n";
			$this->EE->dsp->initial_body .= $js;
			$this->EE->dsp->initial_body .= '//-->';
			$this->EE->dsp->initial_body .= "\n";
			$this->EE->dsp->initial_body .= '</script>';
		}
		
		/** ------------------------------------
		/**  Instantiate Image Class
		/** ------------------------------------*/
		
		// We need this in order to get the image properties

		if ( ! class_exists('Image_lib'))
		{ 
			require APPPATH.'_to_be_replaced/lib.image_lib'.EXT;
		}
		
		$IM = new Image_lib();
								
		$IM->get_image_properties($this->EE->functions->remove_double_slashes($this->prefs['gallery_upload_path'].'/'.$menu_choice));		
		
		// We'll do a little calculation in order to set the height and the width
		// We want the initial box to be constrained to a 4:3 ratio.
				
		$scaled_width	= round($IM->src_width/4);
		$scaled_height	= round((($IM->src_width*.25)*3)/4);

		/** ------------------------------------
		/**  Fetch File list from server
		/** ------------------------------------*/
		
		if ( ! class_exists('File_Browser'))
		{ 
			require APPPATH.'cp/cp.filebrowser'.EXT;
		}
		
		$FP = new File_Browser();
		$FP->images_only = TRUE;
		$FP->ignore[] = $this->prefs['gallery_thumb_prefix'];
		$FP->ignore[] = $this->prefs['gallery_medium_prefix'];
		$FP->set_upload_path($this->prefs['gallery_upload_path']);
		$FP->create_filelist();
		
		/** ------------------------------------
		/**  Build the image selection pull-down
		/** ------------------------------------*/
		
		$menu  = $this->EE->dsp->div('itemWrapper')."<select class='select' onchange='window.open(this.options[this.selectedIndex].value, \"_top\");' >\n";
		$menu .= $this->EE->dsp->input_select_option($self_location, $this->EE->lang->line('gallery_select_file_to_edit'));

		foreach ($FP->filelist as $file_info)
		{
			$menu .= $this->EE->dsp->input_select_option($self_location.$file_info['name'], $file_info['name'], ($file_info['name'] == $menu_choice) ? 1 : 0);
		}

		$menu .= $this->EE->dsp->input_select_footer().$this->EE->dsp->div_c();
		
		/** ------------------------------------
		/**  Add cropping CSS to the <head>
		/** ------------------------------------*/
	
		// This gets added to the main CSS file
	
		$css = "#picture {
				width:".$IM->src_width."px; 
				height:".$IM->src_height."px; 
				z-index:1; 
				}				
				#overtop { 
				position: absolute;
				top: 50px;
				width:".$scaled_width."px; 
				height:".$scaled_height."px; 
				background-color: transparent;
				z-index:2; 
				}
				";
				
		$this->EE->dsp->manual_css = str_replace("\t", "", $css);
				
		/** ------------------------------------
		/**  Create the image selection menu
		/** ------------------------------------*/
		
		$r  = $this->EE->dsp->qdiv('tableHeading', $title);
		$r .= $this->EE->dsp->qdiv('box', $menu);

		/** ------------------------------------
		/**  Return output if first view
		/** ------------------------------------*/
		
		// When the toolbox is initially accessed the "menu_choice" $_GET variable 
		// will not be present.  If so, we're done with the page

		if ($menu_choice == '')
		{			
			return $this->content_wrapper($title, $crumb, $r);
		}

		/** ------------------------------------
		/**  Form Declaration
		/** ------------------------------------*/
		
		$r .= $this->EE->dsp->form_open(array('action' => 'C=modules'.AMP.'M=gallery'.AMP.'P=run_toolbox'.AMP.'gallery_id='.$this->gallery_id, 'name' => 'toolbox', 'id' => 'toolbox'));		
		$r .= $this->EE->dsp->input_hidden('menu_choice', $menu_choice);
		$r .= $this->EE->dsp->input_hidden('file_name', $file_name);
		$r .= $this->EE->dsp->input_hidden('return_loc', $self_location.$menu_choice);
		$r .= $this->EE->dsp->input_hidden('width_orig', $IM->src_width);
		$r .= $this->EE->dsp->input_hidden('height_orig', $IM->src_height);
						
		/** ------------------------------------
		/**  Build the Toolbox controls
		/** ------------------------------------*/
		
		$r .= $this->EE->dsp->div('box');

		ob_start();
		?>
		<table cellpadding="3" cellspacing="0" border="0" align="center">
		 <tr>
			<td>
				
			<fieldset class='galleryTools' id="image_cropping" name="image_cropping">
								
			<legend class="highlight"><input type="checkbox" name="enable_cropping" value="y" onclick="enableCropping();" /> <?php echo $this->EE->lang->line('gallery_enable_cropping');?></legend>
			
			<table cellpadding="3" cellspacing="3" border="0">
			 <tr>
				<td valign="top">
					<?php echo $this->EE->lang->line('gallery_width'); ?><br />
					<input type="text" class="galleryToolsInputOff" name="crop_width" value="<?php echo $scaled_width; ?>" id="thumbwidth" size="6" maxlength="4" onblur="if(window.dd && dd.elements) dd.elements.overtop.resizeTo(document.forms[0].crop_width.value, document.forms[0].crop_height.value);return false;" disabled /> 
				</td>
				<td>
					<?php echo $this->EE->lang->line('gallery_height'); ?><br />
					<input type="text" class="galleryToolsInputOff" name="crop_height" id="thumbheight" value="<?php echo $scaled_height; ?>" size="6"  onblur="if(window.dd && dd.elements) dd.elements.overtop.resizeTo(document.forms[0].crop_width.value, document.forms[0].crop_height.value);return false;" disabled />
				</td>
			 </tr>
			<tr>
				<td>
					<?php echo $this->EE->lang->line('gallery_top'); ?><br />
					<input type="text" class="galleryToolsInputOff" name="top" value="10" id="thumbtop" size="6" maxlength="4" onblur="setCoordinants(document.forms[0].top.value, document.forms[0].left.value);return false;" disabled /> 
				</td>
				<td>
					<?php echo $this->EE->lang->line('gallery_left'); ?><br />
					<input type="text" class="galleryToolsInputOff" name="left" id="thumbleft" value="10" size="6" maxlength="4" onblur="setCoordinants(document.forms[0].top.value, document.forms[0].left.value);return false;" disabled />
				</td>
			 </tr>
			</table>
			
			<div class="itemWrapper">
			<div class="itemWrapper"><div class="leftPad"><?php echo $this->EE->lang->line('gallery_43_aspect_ratio');?></div></div>
			<div class="leftPad">
			<input type="checkbox" name="constrain_4" onclick="constrain4();" checked="checked" disabled /> 4:3&nbsp;
			<input type="checkbox" name="constrain_3" onclick="constrain3();" disabled /> 3:2 &nbsp;
			<input type="checkbox" name="constrain_1" onclick="constrain1();" disabled /> 1:1 &nbsp;
			<input type="checkbox" name="constrain_0" onclick="constrain0();" disabled /> <?php echo $this->EE->lang->line('gallery_none'); ?>
			</div>
			</div>
			</fieldset>
		
		</td>
		<td>
			
			<fieldset class='galleryTools' id="image_resizing" name="image_resizing">
								
			<legend class="highlight"><input type="checkbox" name="enable_resizing" value="y" onclick="enableResizing();" /> <?php echo $this->EE->lang->line('gallery_enable_resizing');?></legend>
	
			<table cellpadding="3" cellspacing="3" border="0">
			 <tr>
				<td>
					<?php echo $this->EE->lang->line('gallery_resize_width'); ?><br />
					<input type="text"  class="galleryToolsInputOff" name="resize_width" id="resize_width" value="<?php echo $IM->src_width; ?>" size="6" maxlength="4" onchange="change_resize_value(this.form, 'w', this.name);" onblur="if(window.dd && dd.elements) dd.elements.picture.resizeTo(document.forms[0].resize_width.value, document.forms[0].resize_height.value);dd.elements.imagebg.resizeTo(dd.elements.imagebg.defw, dd.elements.picture.h+10);return false;" disabled /> 
				</td>
				<td>
					<?php echo $this->EE->lang->line('gallery_resize_height'); ?><br />
					<input type="text"  class="galleryToolsInputOff" name="resize_height" id="resize_height"  value="<?php echo $IM->src_height; ?>" size="6" maxlength="4" onchange="change_resize_value(this.form, 'h', this.name);"  onblur="if(window.dd && dd.elements) dd.elements.picture.resizeTo(document.forms[0].resize_width.value, document.forms[0].resize_height.value);dd.elements.imagebg.resizeTo(dd.elements.imagebg.defw, dd.elements.picture.h+10);return false;" disabled />
				</td>
			 </tr>
			</table>
			
			<div class="itemWrapper"><input type="checkbox"  name="constrain" value="y" onclick="constrainResize(this.value);" checked="checked" disabled /> <?php echo $this->EE->lang->line('gallery_constrain');?></div>
			
			</fieldset>
		
		</td>
			<td valign="top">
		
			<fieldset class='galleryTools' id="image_rotating" name="image_rotating">
								
			<legend class="highlight"><input type="checkbox" name="enable_rotation" value="y" onclick="enableRotation();" /> <?php echo $this->EE->lang->line('gallery_enable_rotation');?></legend>
	
			<table cellpadding="3" cellspacing="3" border="0">
			 <tr>
				<td><br /><br /><br />
					<select name="rotation" id="rotation" class="galleryToolsSelectOff" disabled >
					<option value="90"><?php echo $this->EE->lang->line('gallery_90l'); ?></option>
					<option value="270"><?php echo $this->EE->lang->line('gallery_90r'); ?></option>
					<option value="180"><?php echo $this->EE->lang->line('gallery_180'); ?></option>
					<option value="vrt"><?php echo $this->EE->lang->line('gallery_flip_vert'); ?></option>
					<option value="hor"><?php echo $this->EE->lang->line('gallery_flip_hor'); ?></option>
					</select>
				</td>
			 </tr>
			</table>
			
			</fieldset>

			</td>
		  </tr>
		</table>
		
				
		<?php
		
		$r .= ob_get_contents();
				
		ob_end_clean(); 
			
		/** ------------------------------------
		/**  Display The tool-tips
		/** ------------------------------------*/
		
		$r .= $this->EE->dsp->div_c(); // Close main box div

		$r .= '<div id="tooltips" class="galleryToolTips" style="visibility:hidden;">Look here my man</div>';

		/** ------------------------------------
		/**  Display Imgage
		/** ------------------------------------*/
		
		$r .= "<div class='galleryBG' id='imagebg'>";
		$r .= $this->EE->dsp->div('itemWrapper');

		$r .= "<div id='overtop'></div><img src='".$directory_url.$menu_choice."' id='picture' name='picture' width='".$IM->src_width."' height='".$IM->src_height."' border='0' title='".$menu_choice."' /></div>";

		$r .= $this->EE->dsp->div_c();
		$r .= $this->EE->dsp->div_c();
				
		/** ------------------------------------
		/**  File renaming form
		/** ------------------------------------*/
	
		$r .= $this->EE->dsp->div('box');
		
		$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qspan('defaultBold', $this->EE->lang->line('gallery_image_name')));
		$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->lang->line('gallery_create_copy'));
		
		$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->input_text('new_file_name', $file_name, '40', '100', 'input', '340px'));
		
		$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qspan('defaultBold', $this->EE->lang->line('gallery_quality')));
		$r .= $this->EE->dsp->qdiv('itemWrapper', '<input type="text" class="input" name="quality" id="quality" value="'.$this->prefs['gallery_thumb_quality'].'" size="6" maxlength="3" />');
		
		$r .= $this->EE->dsp->div_c();
		
		$r .= $this->EE->dsp->div('box');

		// Watermark Image
		
		if ($this->prefs['gallery_wm_type'] != 'n')
		{
			$watermark =  ($this->prefs['gallery_wm_type'] != 'n') ? 'y' : 'n';		
		
			$r .= $this->EE->dsp->qdiv('defaultBold', $this->EE->dsp->input_checkbox('apply_watermark', 'y', 0).NBS.$this->EE->lang->line('gallery_watermark_image'));	
		}

		$r .= $this->EE->dsp->div('itemWrapper');
		$r .= $this->EE->dsp->input_checkbox('update_thumbs', 'y').$this->EE->dsp->qspan('defaultBold', $this->EE->lang->line('gallery_update_thumbs'));
		$r .= $this->EE->dsp->div_c();
		$r .= $this->EE->dsp->qdiv('highlight', $this->EE->lang->line('gallery_thumb_update_note'));
		
		$r .= $this->EE->dsp->div_c();

		/** ------------------------------------
		/**  Submit button
		/** ------------------------------------*/
		$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->input_submit($this->EE->lang->line('gallery_modify_image')));
		
		$r .= $this->EE->dsp->form_close();
		
		/** --------------------------------------------------
		/**  JavaScript
		/** --------------------------------------------------*/
		//	The draggable layers are made possible by 
		//	Walter Zorn's superb DHTML library, found at
		//	http://www.walterzorn.com
		//
		//	The cropping interface was inpired by 
		//	Andrew Collington's neat Crop Canvas, found at 
		//	http://php.amnuts.com/
		// --------------------------------------------------

		$cp_img_path = PATH_CP_GBL_IMG;
		$transpixel = $cp_img_path.'transparent.gif';
		
		$croptip		= str_replace("&gt;", ">", str_replace("&lt;", "<", $this->EE->lang->line('gallery_crop_tip')));
		$resizetip	= str_replace("&gt;", ">", str_replace("&lt;", "<", $this->EE->lang->line('gallery_resize_tip')));
		$rotatetip  = $this->EE->lang->line('gallery_rotate_tip');
		
		$prefs = '"picture","overtop"+MAXOFFLEFT+0+MAXOFFRIGHT+'
				.$IM->src_width.'+MAXOFFTOP+0+MAXOFFBOTTOM+'
				.$IM->src_height.'+RESIZABLE'.'+MAXWIDTH+'
				.$IM->src_width.'+MAXHEIGHT+'
				.$IM->src_height.'+MINHEIGHT+20+MINWIDTH+20';

		$r .= <<< EOT
<script type="text/javascript">
<!--

	SET_DHTML($prefs);

	dd.elements.overtop.moveTo(dd.elements.picture.x+10, dd.elements.picture.y+10);
	dd.elements.overtop.setZ(dd.elements.picture.z+1);
	dd.elements.picture.addChild("overtop");
	dd.elements.picture.deftx = dd.elements.picture.x;
	dd.elements.picture.defty = dd.elements.picture.y;
	dd.elements.overtop.defx  = dd.elements.picture.x;
	dd.elements.overtop.defy  = dd.elements.picture.y;
	
	var spacer 		= "{$transpixel}";
	var croptip		= "{$croptip}";
	var resizetip	= "{$resizetip}";
	var rotatetip	= "{$rotatetip}";
	var scaledwidth	= "{$scaled_width}";
	var scaledheight	= "{$scaled_height}";
	var scaledthree	= 150;
	var scaledtwo	= 100;
	
	var detect = navigator.userAgent.toLowerCase();
	var OS, browser, version, total, thestring;
	
	if (checkIt('konqueror'))
	{
		browser = "Konqueror";
		OS = "Linux";
	}
	else if (checkIt('safari'))		browser = "Safari"
	else if (checkIt('omniweb'))		browser = "OmniWeb"
	else if (checkIt('opera'))		browser = "Opera"
	else if (checkIt('webtv'))		browser = "WebTV";
	else if (checkIt('icab'))		browser = "iCab"
	else if (checkIt('msie'))		browser = "IE"
	else if ( ! checkIt('compatible'))
	{
		browser = "Netscape"
		version = detect.charAt(8);
	}
	else browser = "Unknown";
	
	if ( ! version) version = detect.charAt(place + thestring.length);
	
	if (!OS)
	{
		if 		(checkIt('linux'))	OS = "Linux";
		else if (checkIt('x11')) 	OS = "Unix";
		else if (checkIt('mac'))	OS = "Mac"
		else if (checkIt('win'))	OS = "Win"
		else OS = "an unknown operating system";
	}
	
	function checkIt(string)
	{
		place = detect.indexOf(string) + 1;
		thestring = string;
		return place;
	}
			
	 function showTip(text) 
	 { 
		var el = document.getElementById("tooltips");
		
		el.style.visibility = "visible";
		el.firstChild.nodeValue=text;		
	 }
	 
	 function hideTip() 
	 { 
		var el = document.getElementById("tooltips");
	 		
		el.style.visibility = "visible";
		el.firstChild.nodeValue="\b";		
	 }
	 
	function setCoordinants(top, left)
	{			
		dd.elements.overtop.moveTo(dd.elements.picture.x+(left-1), dd.elements.picture.y+(top-1));
	}
	
	function constrain4()
	{		
		if (document.toolbox.constrain_4.checked)
		{
			document.toolbox.constrain_1.checked=false;
			document.toolbox.constrain_3.checked=false;
			document.toolbox.constrain_0.checked=false;
			dd.elements.overtop.defw = scaledwidth;
			dd.elements.overtop.defh = scaledheight;
			dd.elements.overtop.resizeTo(scaledwidth, scaledheight);
			dd.elements.overtop.scalable  = 1;
			dd.elements.overtop.resizable = 0;
			document.toolbox.crop_width.value	=	scaledwidth;
			document.toolbox.crop_height.value	=	scaledheight;			
		}
		else
		{
			dd.elements.overtop.scalable  = 0;
			dd.elements.overtop.resizable = 1;

			document.toolbox.constrain_0.checked=true;		
		}
	}
	
	function constrain3()
	{		
		if (document.toolbox.constrain_3.checked)
		{
			document.toolbox.constrain_4.checked=false;
			document.toolbox.constrain_1.checked=false;			
			document.toolbox.constrain_0.checked=false;			
			dd.elements.overtop.defw = scaledthree;
			dd.elements.overtop.defh = scaledtwo;
			dd.elements.overtop.resizeTo(scaledthree, scaledtwo);
			dd.elements.overtop.scalable  = 1;
			dd.elements.overtop.resizable = 0;
			document.toolbox.crop_width.value	=	scaledthree;
			document.toolbox.crop_height.value	=	scaledtwo;			
		}
		else
		{
			dd.elements.overtop.scalable  = 0;
			dd.elements.overtop.resizable = 1;
			document.toolbox.constrain_0.checked=true;
		}
	}

	function constrain1()
	{		
		if (document.toolbox.constrain_1.checked)
		{
			document.toolbox.constrain_4.checked=false;
			document.toolbox.constrain_3.checked=false;			
			document.toolbox.constrain_0.checked=false;			
			dd.elements.overtop.defw = scaledwidth;
			dd.elements.overtop.defh = scaledwidth;
			dd.elements.overtop.resizeTo(scaledwidth, scaledwidth);
			dd.elements.overtop.scalable  = 1;
			dd.elements.overtop.resizable = 0;
			document.toolbox.crop_width.value	=	scaledwidth;
			document.toolbox.crop_height.value	=	scaledwidth;			
		}
		else
		{
			dd.elements.overtop.scalable  = 0;
			dd.elements.overtop.resizable = 1;
			document.toolbox.constrain_0.checked=true;
		}
	}
	
	
	function constrain0()
	{		
		if (document.toolbox.constrain_0.checked)
		{
			document.toolbox.constrain_1.checked=false;
			document.toolbox.constrain_3.checked=false;
			document.toolbox.constrain_4.checked=false;
		
			dd.elements.overtop.scalable  = 0;
			dd.elements.overtop.resizable = 1;
		}
		else
		{
			document.toolbox.constrain_4.checked=true;
		
			dd.elements.overtop.scalable  = 1;
			dd.elements.overtop.resizable = 0;
		}		
	}
	
	
	function constrainResize()
	{		
		if (document.toolbox.constrain.checked)
		{
			dd.elements.picture.scalable  = 1;
			dd.elements.picture.resizable = 0;
		}
		else
		{
			dd.elements.picture.resizable = 1;
			dd.elements.picture.scalable  = 0;
		}
	}

	function my_DragFunc()
	{		
		dd.elements.overtop.maxoffr = dd.elements.picture.w - dd.elements.overtop.w;
		dd.elements.overtop.maxoffb = dd.elements.picture.h - dd.elements.overtop.h;
		dd.elements.overtop.maxw	= dd.elements.picture.w;
		dd.elements.overtop.maxh	= dd.elements.picture.w;
		
		dd.elements.picture.moveTo(dd.elements.picture.deftx, dd.elements.picture.defty);
	}

	function my_DropFunc()
	{		
		if (document.toolbox.enable_cropping.checked)
		{
			dd.elements.overtop.maximizeZ();
			document.toolbox.crop_width.value	=	dd.elements.overtop.w;
			document.toolbox.crop_height.value	=	dd.elements.overtop.h;
			document.toolbox.top.value			=	(dd.elements.overtop.y - dd.elements.picture.y);
			document.toolbox.left.value			=	(dd.elements.overtop.x - dd.elements.picture.x);				
		}
		
		if (document.toolbox.enable_resizing.checked)
		{ 
			dd.elements.picture.maximizeZ();									
			document.toolbox.resize_width.value	=	dd.elements.picture.w;
			document.toolbox.resize_height.value	=	dd.elements.picture.h;
		}
	}

	function my_ResizeFunc()
	{
		dd.elements.overtop.maxw = (dd.elements.picture.w + dd.elements.picture.x) - dd.elements.overtop.x;
		dd.elements.overtop.maxh = (dd.elements.picture.h + dd.elements.picture.y) - dd.elements.overtop.y;	
	}
		
	function enableResizing()
	{	
		if (document.toolbox.enable_resizing.checked)
		{ 
			dd.elements.picture.maximizeZ();
			dd.elements.picture.setCursor(CURSOR_MOVE);
			dd.elements.overtop.setCursor(CURSOR_MOVE);
			constrainResize();
				
			showTip(resizetip);
			
			document.toolbox.resize_width.disabled=false;
			document.toolbox.resize_height.disabled=false;
			document.toolbox.constrain.disabled=false;				
			document.getElementById('image_resizing').className = 'galleryToolsOn';
			document.getElementById('resize_width').className = "galleryToolsInputOn";
			document.getElementById('resize_height').className = "galleryToolsInputOn";
		}
		else
		{
			if (document.toolbox.enable_cropping.checked)
				showTip(croptip);
			else
				hideTip();
			
			dd.elements.picture.scalable  = 0;
			dd.elements.picture.resizable = 0;
			dd.elements.picture.setCursor(CURSOR_DEFAULT);
			document.toolbox.resize_width.disabled=true;
			document.toolbox.resize_height.disabled=true;
			document.toolbox.constrain.disabled=true;		
			document.getElementById('image_resizing').className = 'galleryTools';
			document.getElementById('resize_width').className = "galleryToolsInputOff";
			document.getElementById('resize_height').className = "galleryToolsInputOff";
		}
	}
	
	function enableCropping()
	{		
		if (document.toolbox.enable_cropping.checked)
		{	
			dd.elements.overtop.maximizeZ();	
			dd.elements.overtop.setCursor(CURSOR_MOVE);
			dd.elements.overtop.nodrag = 0;
			
			if ((dd.elements.picture.w < dd.elements.overtop.w) OR (dd.elements.picture.h < dd.elements.overtop.h))
			{
				dd.elements.overtop.resizeTo(document.toolbox.resize_width.value-2, document.toolbox.resize_height.value-2);
				dd.elements.overtop.moveTo(dd.elements.picture.x, dd.elements.picture.y);
				
				document.toolbox.crop_width.value	= document.toolbox.resize_width.value;
				document.toolbox.crop_height.value	= document.toolbox.resize_height.value;		
				document.toolbox.top.value			= 0;
				document.toolbox.left.value			= 0;	
			}
			
			constrain4();
			showTip(croptip);
			dd.elements.overtop.maxw	= dd.elements.picture.w;
			dd.elements.overtop.maxh	= dd.elements.picture.h;
			dd.elements.overtop.maxoffr = dd.elements.picture.w - dd.elements.overtop.w;
			dd.elements.overtop.maxoffb = dd.elements.picture.h - dd.elements.overtop.h;

			document.toolbox.crop_width.disabled=false;
			document.toolbox.crop_height.disabled=false;
			document.toolbox.top.disabled=false;
			document.toolbox.left.disabled=false;
			document.toolbox.constrain_4.disabled=false;
			document.toolbox.constrain_3.disabled=false;
			document.toolbox.constrain_1.disabled=false;
			document.toolbox.constrain_0.disabled=false;
				
			document.getElementById('overtop').style.border =  "2px solid #ffff00";
			document.getElementById('image_cropping').className = 'galleryToolsOn';
			document.getElementById('thumbwidth').className = "galleryToolsInputOn";
			document.getElementById('thumbheight').className = "galleryToolsInputOn";
			document.getElementById('thumbtop').className = "galleryToolsInputOn";
			document.getElementById('thumbleft').className = "galleryToolsInputOn";		
			
				
			if	((browser == 'Opera' && OS == 'Mac' && version >= 5) OR (browser == 'Opera' && OS == 'Win' && version >= 6) OR (browser == 'Opera' && OS == 'Unix' && version >= 6) OR (browser == 'Opera' && OS == 'Linux' && version >= 6) OR (browser == 'OmniWeb'	&& version >= 3.1) OR (browser == 'iCab' && version >= 1.9) OR (browser == 'Safari') OR (browser == 'WebTV') OR (browser == 'Netscape'))
			{
				document.getElementById('overtop').style.backgroundImage = "url({$cp_img_path}screen.png)";		
			}				
		}
		else
		{
			if (document.toolbox.enable_resizing.checked)
				showTip(resizetip);
			else
				hideTip();

			dd.elements.overtop.nodrag = 1;
			document.toolbox.crop_width.disabled=true;
			document.toolbox.crop_height.disabled=true;
			document.toolbox.top.disabled=true;
			document.toolbox.left.disabled=true;
			document.toolbox.constrain_4.disabled=true;
			document.toolbox.constrain_1.disabled=true;
			document.toolbox.constrain_0.disabled=true;
			
			document.getElementById('overtop').style.border =  "0";
			document.getElementById('overtop').style.backgroundImage = "";				
			document.getElementById('image_cropping').className = 'galleryTools';
			document.getElementById('thumbwidth').className = "galleryToolsInputOff";
			document.getElementById('thumbheight').className = "galleryToolsInputOff";
			document.getElementById('thumbtop').className = "galleryToolsInputOff";
			document.getElementById('thumbleft').className = "galleryToolsInputOff";
			
		}
	}
	
	function enableRotation()
	{	
		if (document.toolbox.enable_rotation.checked)
		{
			showTip(rotatetip);
			document.toolbox.rotation.disabled=false;
			document.getElementById('image_rotating').className = 'galleryToolsOn';
			document.getElementById('rotation').className = "galleryToolsSelectOn";
		}
		else
		{
			if (document.toolbox.enable_cropping.checked)
				showTip(croptip);
			else if (document.toolbox.enable_resizing.checked)
				showTip(resizetip);
			else
				hideTip();
		
			document.toolbox.rotation.disabled=true;
			document.getElementById('image_rotating').className = 'galleryTools';
			document.getElementById('rotation').className = "galleryToolsSelectOff";
		}
	}
	
	function change_resize_value(f, side)
	{
		var orig		= (side == "w") ? f.width_orig	: f.height_orig;
		var curr		= (side == "w") ? f.resize_width : f.resize_height;
		var t_orig	= (side == "h") ? f.width_orig	: f.height_orig;
		var t_curr	= (side == "h") ? f.resize_width	: f.resize_height;
	
		var ratio	= curr.value/orig.value;
		var res 		= Math.floor(ratio * t_orig.value);
	
		var max = {$this->max_size};
			
		if (res > max OR curr.value > max)
		{
			if (f.constrain.checked)
				t_curr.value = t_orig.value;
			
			curr.value = Math.min(curr.value, orig.value);
		}
		else
		{
			if (f.constrain.checked)
				t_curr.value = res;
		}
					
		return;
	}
			
//-->
</script>
EOT;

		// Spit the damn thing at the browser already!!
		
		return $this->content_wrapper($title, $crumb, $r);
	}



	
	/** ----------------------------------------
	/**  Run the Image Toolbox
	/** ----------------------------------------*/
	
	function run_toolbox()
	{
		
		if ( ! $menu_choice = $this->EE->input->post('menu_choice'))
		{
			return $this->EE->dsp->no_access_message();
		}
		
		/** ----------------------------------------
		/**  Were any of the processing buttons checked?
		/** ----------------------------------------*/
				
		if ( ! isset($_POST['enable_cropping']) AND ! isset($_POST['enable_resizing']) AND! isset($_POST['enable_rotation']))
		{
			$this->EE->functions->redirect($this->EE->input->post('return_loc'));
			exit;
		}

		/** -----------------------------------
		/**  Set up our base preferences
		/** -----------------------------------*/
						
		$folder = '';
		if (strpos($menu_choice, '/') !== FALSE)
		{	
			$xy = explode("/", $menu_choice);
			$folder = $this->EE->functions->remove_double_slashes(str_replace(end($xy), '', $menu_choice));			
		}
		
		$file_name		= $this->EE->input->get_post('file_name');
		$new_file_name	= ($file_name == $this->EE->input->post('new_file_name')) ? $file_name : $this->EE->input->get_post('new_file_name');
		$file_path		= $this->EE->functions->remove_double_slashes($this->prefs['gallery_upload_path'].'/'.$folder.'/');		
		$menu_chice		= ($folder != '') ? $folder.'/'.$new_file_name : $new_file_name;
		$dst_image_url	= $this->EE->functions->remove_double_slashes($this->prefs['gallery_image_url'].'/'.$folder.'/'.$new_file_name);	
		$quality		= ($this->EE->input->post('quality') == FALSE OR ! is_numeric($this->EE->input->get_post('quality'))) ? '100' : $this->EE->input->get_post('quality');

		/** -----------------------------------
		/**  Is the filepath writable?
		/** -----------------------------------*/

		if ( ! is_really_writable($file_path.$file_name)) 
		{ 
			return $this->EE->dsp->error_message(array($this->EE->lang->line('gallery_nonwritable_path')));
		}
		
		/** -----------------------------------
		/**  Assign Thumb data
		/** -----------------------------------*/
		
		// Since each process will potentially have to create thumbs
		// after processing the main image we will create an array
		// containing the thumbnail and medium sized image width and height
		// values.  We'll use this array to loop through each process.
				
		if (isset($_POST['update_thumbs']))
		{
			if ($this->prefs['gallery_create_thumb'] == 'y')
				$thumbs['thumb'] = array($this->prefs['gallery_thumb_prefix'],  $this->prefs['gallery_thumb_width'],  $this->prefs['gallery_thumb_height'], $this->prefs['gallery_thumb_quality']);
			
			if ($this->prefs['gallery_create_medium'] == 'y')
				$thumbs['med'] = array($this->prefs['gallery_medium_prefix'], $this->prefs['gallery_medium_width'], $this->prefs['gallery_medium_height'], $this->prefs['gallery_medium_quality']);		
		}
		
		
		/** --------------------------------
		/**  Invoke the Image Lib Class
		/** --------------------------------*/
		require APPPATH.'_to_be_replaced/lib.image_lib'.EXT;
		$IM = new Image_lib();
						
		/** --------------------------------
		/**  Resize the image
		/** --------------------------------*/
		if (isset($_POST['enable_resizing']))
		{				
			$res = $IM->set_properties(			
										array(
												'resize_protocol'	=> $this->prefs['gallery_image_protocal'],
												'libpath'			=> $this->prefs['gallery_image_lib_path'],
												'thumb_prefix'		=> '',
												'file_path'			=> $file_path,
												'file_name'			=> $file_name,
												'new_file_name'		=> $new_file_name,
												'quality'			=> $quality,
												'dst_width'			=> $_POST['resize_width'],
												'dst_height'		=> $_POST['resize_height']
												)
										);
			if ($res === FALSE OR ! $IM->image_resize())
			{
				return $IM->show_error();
			}
		}


		/** --------------------------------
		/**  Crop the image
		/** --------------------------------*/
				
		if (isset($_POST['enable_cropping']))
		{
			$IM->initialize();
		
			$res = $IM->set_properties(			
										array(
												'resize_protocol'	=> $this->prefs['gallery_image_protocal'],
												'libpath'			=> $this->prefs['gallery_image_lib_path'],
												'thumb_prefix'		=> '',
												'file_path'			=> $file_path,
												'file_name'			=> (isset($_POST['enable_resizing'])) ? $new_file_name : $file_name,
												'new_file_name'		=> $new_file_name,
												'quality'			=> $quality,
												'x_axis'			=> $_POST['left'],
												'y_axis'			=> $_POST['top'],
												'dst_width'			=> $_POST['crop_width'],
												'dst_height'		=> $_POST['crop_height'],
												'maintain_ratio'	=> FALSE
												)
										);
								
			if ($res === FALSE OR ! $IM->image_crop())
			{
				return $IM->show_error();
			}
		}

		/** --------------------------------
		/**  Rotate the Image
		/** --------------------------------*/
						
		if (isset($_POST['enable_rotation']))
		{		
			$IM->initialize();

			$res = $IM->set_properties(			
										array(
												'resize_protocol'	=> $this->prefs['gallery_image_protocal'],
												'libpath'			=> $this->prefs['gallery_image_lib_path'],
												'thumb_prefix'		=> '',
												'file_path'			=> $file_path,
												'file_name'			=> (isset($_POST['enable_resizing']) OR isset($_POST['enable_cropping'])) ? $new_file_name : $file_name,
												'new_file_name'		=> $new_file_name,
												'rotation'			=> $_POST['rotation'],
												'quality'			=> $quality
												)
										);
								
			if ($res === FALSE OR ! $IM->image_rotate())
			{
				return $IM->show_error();
			}
		}

		/** --------------------------------
		/**  Do the thumbs require watermark?
		/** --------------------------------*/
		
		// We need to determine if ether the thumbnail
		// or the medium sized image require the watermark.
		// If the do, we will add the watermark to the full-sized
		// image and create our thumbs from it.  If not, we will
		// create a temporary copy of the full-sized image without
		// the watermark and use it as the basis for the thumbs.
		// Since the thumb and medium image can have separate prefs
		// we have to test for each individually.
		
		$temp_marker		 = 'yg49Zxdsg4848MJMq';
		$create_tmp_copy = FALSE;
		$tmp_thumb_name  = $new_file_name;
		$tmp_medium_name = $new_file_name;
		
		if ($this->prefs['gallery_create_thumb'] == 'y' AND $this->prefs['gallery_wm_apply_to_thumb'] == 'n')
		{
			$create_tmp_copy = TRUE;
			$tmp_thumb_name = $temp_marker.$new_file_name;			
		}
		
		if ($this->prefs['gallery_create_medium'] == 'y' AND $this->prefs['gallery_wm_apply_to_medium'] == 'n')
		{
			$create_tmp_copy = TRUE;
			$tmp_medium_name = $temp_marker.$new_file_name;			
		}
		
		if ($create_tmp_copy == TRUE)
		{
			copy($file_path.$new_file_name, $file_path.$temp_marker.$new_file_name);
		}
	
		/** --------------------------------
		/**  Apply Watermark to main image
		/** --------------------------------*/
	
		if (isset($_POST['apply_watermark']) AND $this->prefs['gallery_wm_type'] != 'n')
		{		
			$res = $IM->set_properties(	
									array (
										'resize_protocol'		=> $this->prefs['gallery_image_protocal'],
										'libpath'				=> $this->prefs['gallery_image_lib_path'],
										'file_path'				=>	$file_path,
										'file_name'				=>	$new_file_name,
										'wm_image_path'			=>	$this->prefs['gallery_wm_image_path'],	
										'wm_use_font'			=>	($this->prefs['gallery_wm_use_font'] == 'y') ? TRUE : FALSE,
										'dynamic_output'			=>	FALSE,
										'wm_font'				=>	$this->prefs['gallery_wm_font'],
										'wm_font_size'			=>	$this->prefs['gallery_wm_font_size'],	
										'wm_text_size'			=>	5,
										'wm_text'				=>	$this->prefs['gallery_wm_text'],
										'wm_vrt_alignment'		=>	$this->prefs['gallery_wm_vrt_alignment'],	
										'wm_hor_alignment'		=>	$this->prefs['gallery_wm_hor_alignment'],
										'wm_padding'				=>	$this->prefs['gallery_wm_padding'],
										'wm_x_offset'			=>	$this->prefs['gallery_wm_x_offset'],
										'wm_y_offset'			=>	$this->prefs['gallery_wm_y_offset'],
										'wm_x_transp'			=>	$this->prefs['gallery_wm_x_transp'],
										'wm_y_transp'			=>	$this->prefs['gallery_wm_y_transp'],
										'wm_text_color'			=>	$this->prefs['gallery_wm_text_color'],
										'wm_use_drop_shadow'		=>	($this->prefs['gallery_wm_use_drop_shadow']) ? TRUE : FALSE,
										'wm_shadow_color'		=>	$this->prefs['gallery_wm_shadow_color'],
										'wm_shadow_distance'		=>	$this->prefs['gallery_wm_shadow_distance'],
										'wm_opacity'				=>	$this->prefs['gallery_wm_opacity']
								  )
							);
			
			$type = ($this->prefs['gallery_wm_type']	 == 't') ? 'text_watermark' : 'image_watermark';
												
			if ( ! $res OR  ! $IM->$type())
			{
				return $IM->show_error();
			}
		}

		/** --------------------------------
		/**  Are thumbs required?
		/** --------------------------------*/
		
		if (isset($_POST['update_thumbs']) AND isset($thumbs) AND count($thumbs) > 0)
		{
			$IM->initialize();
		
			foreach ($thumbs as $key => $val)
			{
				$res = $IM->set_properties(			
									array(
											'resize_protocol'	=> $this->prefs['gallery_image_protocal'],
											'libpath'			=> $this->prefs['gallery_image_lib_path'],
											'maintain_ratio'	=> ($this->prefs['gallery_maintain_ratio'] == 'y') ? TRUE : FALSE,
											'thumb_prefix'		=> $val['0'],
											'file_path'			=> $file_path,
											'file_name'			=> ($key == 'thumb') ? $tmp_thumb_name : $tmp_medium_name,
											'new_file_name'		=> $new_file_name,
											'quality'			=> $quality,
											'dst_width'			=> $val['1'],
											'dst_height'		=> $val['2']
											)
									);
									
				if ($res === FALSE OR ! $IM->image_resize())
				{
					return $IM->show_error();
				}					
			}
		}			


		if ($create_tmp_copy == TRUE)
		{
			unlink($file_path.$temp_marker.$new_file_name);
		}


		return $this->image_refresher($dst_image_url, $menu_chice);
	}



	
	/** ----------------------------------------
	/**  Image Refresher
	/** ----------------------------------------*/
	function image_refresher($image_url = '', $menu_choice = '')
	{
		$vars = array('image_url', 'menu_choice', 'prefix');
			
		foreach ($vars as $val)
		{
			if (isset($_GET[$val]))
			{
				$$val = $_GET[$val];
			}
		}	
		
		// Strip "http://" from URL string
		// to prevent a security error
		$prefix = ( ! isset($prefix) OR $prefix == '') ? 0 : $prefix;
		
		if (strpos($image_url, 'http://') !== FALSE)
		{
			$image_url = str_replace('http://', '', $image_url);
			$prefix = 1;
		}
			
		$r  = $this->EE->dsp->qdiv('tableHeading', $this->EE->lang->line('gallery_image_toolbox'));
		$r .= $this->EE->dsp->qdiv('successBox', $this->EE->dsp->qdiv('success', $this->EE->lang->line('gallery_successful_processing')));
		$r .= $this->EE->dsp->div('box'); 
		$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('highlight', $this->EE->lang->line('gallery_image_caching_notice')));
		$r .= $this->EE->dsp->div('itemWrapper');
		$r .= $this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=image_refresher'.AMP.'gallery_id='.$this->gallery_id.AMP.'menu_choice='.$menu_choice.AMP.'image_url='.$image_url.AMP.'prefix='.$prefix, $this->EE->lang->line('gallery_image_refresh'));
		$r .= NBS.NBS.NBS.'|'.NBS.NBS.NBS;
		$r .= $this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=image_toolbox'.AMP.'gallery_id='.$this->gallery_id.AMP.'menu_choice='.$menu_choice, $this->EE->lang->line('gallery_continue'));
		$r .= $this->EE->dsp->div_c();		
		$r .= $this->EE->dsp->div_c();
		$r .= $this->EE->dsp->qdiv('defaultSmall', '');
		
		if ($prefix == 1)
		{
			$image_url = 'http://'.$image_url;			
		}
			
		$r .= $this->EE->dsp->qdiv('galleryBG', $this->EE->dsp->qdiv('itemWrapper', "<img src='{$image_url}' border='0' />"));
		
		$title  = $this->EE->lang->line('gallery_image_toolbox');
		$crumb  = $this->EE->dsp->crumb_item($this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=image_toolbox'.AMP.'gallery_id='.$this->gallery_id.AMP.'menu_choice='.$menu_choice, $this->EE->lang->line('gallery_image_toolbox')));
		$crumb .= $this->EE->dsp->crumb_item($this->EE->lang->line('gallery_image_processing'));

		$this->horizontal_nav = TRUE;

		return $this->content_wrapper($title, $crumb, $r);	
	}






	/** ----------------------------------------
	/**  View Comments
	/** ----------------------------------------*/
	function view_comments($entry_id = '')
	{
		// Base variables
		$return 			= '';
		$current_page		= '';
		$qstring			= $this->EE->uri->query_string;
		$uristr				= $this->EE->uri->uri_string;
		$switch 			= array();
		
		// Pagination variables
		
		$paginate			= FALSE;
		$paginate_data		= '';
		$pagination_links	= '';
		$page_next			= '';
		$page_previous		= '';
		$total_pages		= 1;
		$search_link		= '';
		$limit				= 35;
		$qm 				= ($this->EE->config->item('force_query_string') == 'y') ? '' : '?';		

		if ($entry_id == '')
		{
			if ( ! $entry_id = $this->EE->input->get('entry_id'))
			{
				return FALSE;
			}
		}
	
		if ( ! $cat_id = $this->EE->input->get('cat_id'))
		{
			return FALSE;
		}

		/** ---------------------------------------
		/**  Assign page header and breadcrumb
		/** ---------------------------------------*/
		
		$query = $this->EE->db->query("SELECT title FROM exp_gallery_entries WHERE entry_id = '{$entry_id}'");
		
		if ($query->num_rows() == 0)
		{
			return '';
		}
		
		$title = $this->EE->lang->line('gallery_comments');	  
		$crumb = $this->EE->dsp->crumb_item($this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=view_files'.AMP.'cat_id='.$cat_id.AMP.'gallery_id='.$this->gallery_id.AMP.'mode=view', $this->EE->lang->line('gallery_view_entries'))).$this->EE->dsp->crumb_item($query->row('title') );
		
		$r = $this->EE->dsp->qdiv('tableHeading', $this->EE->lang->line('gallery_comments'));
		
		$msg = '';
		
		if ($msg = $this->EE->input->get_post('msg'))
		{
			switch($msg)
			{
				case 'update'	:	$msg = $this->EE->lang->line('gallery_comment_updated');
					break;
				case 'status'	:	$msg = $this->EE->lang->line('gallery_status_changed');
					break;
				case 'deleted'	:	$msg = $this->EE->lang->line('gallery_comment_deleted');
					break;	
			}
		}
		
		if ($msg != '')
		{
			$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->qdiv('successBox', $this->EE->dsp->qdiv('success', $msg)));
		}
		
		/** ---------------------------------------
		/**  Fetch comment display preferences
		/** ---------------------------------------*/
	
		$query = $this->EE->db->query("SELECT gallery_comment_text_formatting, 
									gallery_comment_html_formatting,
									gallery_comment_allow_img_urls,
									gallery_comment_auto_link_urls
									FROM exp_galleries 
									WHERE gallery_id = '".$this->EE->db->escape_str($this->gallery_id)."'");
		
		
		if ($query->num_rows() == 0)
		{
			return '';
		}
		
		foreach ($query->row_array() as $key => $val)
		{
			$$key = $val;
		}
			

		/** ----------------------------------------
		/**  Fetch comment ID numbers
		/** ----------------------------------------*/
		

		$sql = "SELECT comment_id 
				FROM exp_gallery_comments 
				WHERE entry_id = '$entry_id' 
				ORDER BY comment_date desc";
									
		$query = $this->EE->db->query($sql);
				
		if ($query->num_rows() == 0)
		{
			$r .= $this->EE->dsp->div('box');
			$r .= $this->EE->dsp->qdiv('highlight', $this->EE->lang->line('gallery_no_comments'));
			$r .= $this->EE->dsp->qdiv('itemWrapper', $this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=view_files'.AMP.'cat_id='.$cat_id.AMP.'gallery_id='.$this->gallery_id.AMP.'mode=view', $this->EE->lang->line('back')));
			$r .= $this->EE->dsp->div_c();
			
			return $this->content_wrapper($title, $crumb, $r);
		}
		
		/** ---------------------------------
		/**  Do we need pagination?
		/** ---------------------------------*/
		
		if ($query->num_rows() > $limit)
		{
			$current_page = ( ! $this->EE->input->get_post('row')) ? 0 : $this->EE->input->get_post('row');
						
			$base_url = BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=view_comments'.AMP.'gallery_id='.$this->gallery_id.AMP.'entry_id='.$entry_id.AMP.'cat_id='.$cat_id;
					
			$pagination_links = $this->EE->dsp->pager(  $base_url,
											  $query->num_rows, 
											  $limit,
											  $current_page,
											  'row'
											);
			 
			$sql .= " LIMIT ".$current_page.', '.$limit;			
			
			$query = $this->EE->db->query($sql);	
		}
		

		/** -----------------------------------
		/**  Build Final Query
		/** -----------------------------------*/
	
		$sql = "SELECT 
				exp_gallery_comments.comment_id, exp_gallery_comments.entry_id, exp_gallery_comments.gallery_id, exp_gallery_comments.author_id, exp_gallery_comments.name, exp_gallery_comments.email, exp_gallery_comments.url, exp_gallery_comments.location as c_location, exp_gallery_comments.ip_address, exp_gallery_comments.comment_date, exp_gallery_comments.edit_date, exp_gallery_comments.status, exp_gallery_comments.comment, exp_gallery_comments.notify,
				exp_members.location, exp_members.interests, exp_members.aol_im, exp_members.yahoo_im, exp_members.msn_im, exp_members.icq, exp_members.group_id, exp_members.member_id,
				exp_member_data.*,
				exp_galleries.gallery_comment_text_formatting, exp_galleries.gallery_comment_html_formatting, exp_galleries.gallery_comment_allow_img_urls, exp_galleries.gallery_comment_auto_link_urls 
				FROM exp_gallery_comments 
				LEFT JOIN exp_galleries ON exp_gallery_comments.gallery_id = exp_galleries.gallery_id 
				LEFT JOIN exp_members ON exp_members.member_id = exp_gallery_comments.author_id 
				LEFT JOIN exp_member_data ON exp_member_data.member_id = exp_members.member_id
				WHERE exp_gallery_comments.comment_id  IN (";
				
		foreach ($query->result_array() as $row)
		{
			$sql .= $row['comment_id'].',';
		}
		
		$sql = substr($sql, 0, -1).")";
		
		$query = $this->EE->db->query($sql);
		
		
		/** ---------------------------------------
		/**  Instantiate the Typography class
		/** ---------------------------------------*/
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();
		
		$this->EE->lang->loadfile('content');

		$r .= $this->EE->dsp->toggle();
		$this->EE->dsp->body_props .= ' onload="magic_check()" ';
		$r .= $this->EE->dsp->magic_checkboxes();
		
		$r .= $this->EE->dsp->form_open(
								array(
										'action' => 'C=modules'.AMP.'M=gallery'.AMP.'P=delete_comment_confirm', 
										'name'	=> 'target',
										'id'	=> 'target'
									),
								array(
										'gallery_id'	=> $this->gallery_id,
										'entry_id'		=> $entry_id,
										'cat_id'		=> $cat_id,
										'row'			=> $current_page
										)
									
							);
		
		
		$r .= $this->EE->dsp->table('tableBorder', '0', '', '100%').
			  $this->EE->dsp->tr().
			  $this->EE->dsp->table_qcell('tableHeadingAlt', $this->EE->lang->line('comment')).
			  $this->EE->dsp->table_qcell('tableHeadingAlt', $this->EE->lang->line('author')).
			  $this->EE->dsp->table_qcell('tableHeadingAlt', $this->EE->lang->line('email')).
			  $this->EE->dsp->table_qcell('tableHeadingAlt', $this->EE->lang->line('date')).
			  $this->EE->dsp->table_qcell('tableHeadingAlt', $this->EE->lang->line('comment_ip')).
			  $this->EE->dsp->table_qcell('tableHeadingAlt', $this->EE->lang->line('status')).
			  $this->EE->dsp->table_qcell('tableHeadingAlt', $this->EE->dsp->input_checkbox('toggleflag', '', '', "onclick=\"toggle(this);\"").NBS.$this->EE->lang->line('action')).
			  $this->EE->dsp->tr_c();
		
		/** -------------------------------
		/**  Show comments
		/** -------------------------------*/
			
			$i = 0;
		foreach ($query->result_array() as $row)
		{		
			$style = ($i % 2) ? 'tableCellOne' : 'tableCellTwo'; $i++;
		
			if ($row['status'] == 'c')
			{
				$status = 'open';
				$status_label = $this->EE->lang->line('closed');
			}
			else
			{
				$status = 'close';	
				$status_label = $this->EE->lang->line('open');
			}
						
			$status_change = $this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=change_comment_status'.AMP.'gallery_id='.$this->gallery_id.AMP.'entry_id='.$entry_id.AMP.'cat_id='.$cat_id.AMP.'author_id='.$row['author_id'].AMP.'comment_id='.$row['comment_id'].AMP.'row='.$current_page.AMP.'status='.$status, $status_label);
			
			$id = $row['comment_id'];
			
			$text = $this->EE->functions->char_limiter(trim(strip_tags(str_replace(array("\t","\n","\r"), '', $row['comment']))), 25);
			
			$r .= $this->EE->dsp->tr().
				  $this->EE->dsp->table_qcell($style, $this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=edit_comment'.AMP.'gallery_id='.$this->gallery_id.AMP.'entry_id='.$entry_id.AMP.'row='.$current_page.AMP.'comment_id='.$row['comment_id'].AMP.'cat_id='.$cat_id, $text)).
				  $this->EE->dsp->table_qcell($style,  $row['name']).
				  $this->EE->dsp->table_qcell($style, ($row['email'] == '') ? '' : $this->EE->dsp->mailto($row['email'], $row['email'])).
				  $this->EE->dsp->table_qcell($style, $this->EE->localize->set_human_time($row['comment_date'])).
				  $this->EE->dsp->table_qcell($style, $row['ip_address']).
				  $this->EE->dsp->table_qcell($style, $status_change).
				  $this->EE->dsp->table_qcell($style, $this->EE->dsp->input_checkbox('toggle[]', $id, '', "id='delete_box_{$id}'")).
				  $this->EE->dsp->tr_c();
		}
		// END FOREACH
		
		$r .= $this->EE->dsp->table_c();
		
		$options =  $this->EE->dsp->input_select_header('action').
					$this->EE->dsp->input_select_option('close', $this->EE->lang->line('close_selected')).
					$this->EE->dsp->input_select_option('open', $this->EE->lang->line('open_selected')).
					$this->EE->dsp->input_select_option('delete', $this->EE->lang->line('delete_selected')).
					$this->EE->dsp->input_select_footer();
		
		$r .= 		$this->EE->dsp->table('', '0', '', '100%')
				.		$this->EE->dsp->tr()
			  	.			$this->EE->dsp->td('defaultRight')
			  	.				$this->EE->dsp->input_submit($this->EE->lang->line('submit')).NBS.NBS.$options
			  	.			$this->EE->dsp->td_c()
			  	.		$this->EE->dsp->tr_c()
			  	.	$this->EE->dsp->table_c()
			  	.$this->EE->dsp->form_close();
		
		if ($pagination_links != '')
		{
			$r .= $this->EE->dsp->qdiv('itemWrapper', $pagination_links);
		}
		
		return $this->content_wrapper($title, $crumb, $r);		
		}

	
	
	/** -----------------------------------------
	/**  Edit Comment
	/** -----------------------------------------*/
	function edit_comment_form()
	{
		if ( ! $comment_id 	= $this->EE->input->get_post('comment_id'))
		{
			return $this->EE->dsp->no_access_message();
		} 

		if ( ! is_numeric($comment_id))
		{
			return FALSE;
		}

		$entry_id	= $this->EE->input->get_post('entry_id');
		$row		= $this->EE->input->get_post('row');		
		$cat_id		= $this->EE->input->get_post('cat_id');	
		
		if ( ! is_numeric($entry_id))
			return FALSE;
			
		if ( ! is_numeric($cat_id))
			return FALSE;
		
		$this->EE->lang->loadfile('content');
		
		
		$sql = "SELECT exp_gallery_entries.author_id
				FROM	exp_gallery_entries, exp_gallery_comments
				WHERE  exp_gallery_entries.entry_id = exp_gallery_comments.entry_id
				AND	exp_gallery_comments.comment_id = '$comment_id'";
		
		$query = $this->EE->db->query($sql);
		
		if ($this->EE->session->userdata['group_id'] != 1)
		{
			if ($query->row('author_id')  != $this->EE->session->userdata('member_id'))
			{
				return $this->EE->dsp->no_access_message();
			}
			}
			
		$query = $this->EE->db->query("SELECT * FROM exp_gallery_comments WHERE comment_id = '$comment_id'");
		
		if ($query->num_rows() == 0)
		{
				return FALSE;
		}
		
		foreach ($query->row_array() as $key => $val)
		{
			$$key = $val;
		}
		
		
		$r  = $this->EE->dsp->form_open(array('action' => 'C=modules'.AMP.'M=gallery'.AMP.'P=update_comment'.AMP.'gallery_id='.$this->gallery_id));
		$r .= $this->EE->dsp->input_hidden('comment_id',	$comment_id);
		$r .= $this->EE->dsp->input_hidden('author_id',	$author_id);
		$r .= $this->EE->dsp->input_hidden('row',  		$row);
		$r .= $this->EE->dsp->input_hidden('entry_id',	$entry_id);
		$r .= $this->EE->dsp->input_hidden('cat_id',		$cat_id);
				
		$r .= $this->EE->dsp->qdiv('tableHeading', $this->EE->lang->line('edit_comment'));
		
		if ($author_id == 0)
		{
			$r .= $this->EE->dsp->itemgroup(
									$this->EE->dsp->required().NBS.$this->EE->lang->line('name', 'name'),
									$this->EE->dsp->input_text('name', $name, '40', '100', 'input', '300px')
								  );
												
			$r .= $this->EE->dsp->itemgroup(
									$this->EE->dsp->required().NBS.$this->EE->lang->line('email', 'email'),
									$this->EE->dsp->input_text('email', $email, '35', '100', 'input', '300px')
								  );
		 
	
			$r .= $this->EE->dsp->itemgroup(
									$this->EE->lang->line('url', 'url'),
									$this->EE->dsp->input_text('url', $url, '40', '100', 'input', '300px')
								  );
								  
			$r .= $this->EE->dsp->itemgroup(
									$this->EE->lang->line('location', 'location'),
									$this->EE->dsp->input_text('location', $location, '40', '100', 'input', '300px')
								  );
		 }	
		 
			$r .= $this->EE->dsp->input_textarea('comment', $comment, '14', 'textarea', '100%');
		
		// Submit button	
		
		$r .= $this->EE->dsp->itemgroup( '',
								$this->EE->dsp->required(1).$this->EE->dsp->br(2).$this->EE->dsp->input_submit($this->EE->lang->line('submit'))
							  );
		$r .= $this->EE->dsp->form_close();

		$title = $this->EE->lang->line('edit_comment');
		$crumb = $this->EE->dsp->crumb_item($this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=view_comments'.AMP.'entry_id='.$entry_id.AMP.'cat_id='.$cat_id.AMP.'gallery_id='.$this->gallery_id, $this->EE->lang->line('gallery_view_entries'))).$this->EE->dsp->crumb_item($this->EE->lang->line('edit_comment'));

		return $this->content_wrapper($title, $crumb, $r);
	}

	
	
	/** -----------------------------------------
	/**  Update Comment
	/** -----------------------------------------*/
	function update_comment()
	{
		if (($comment_id = $this->EE->input->post('comment_id')) === FALSE)
		{
			return $this->EE->dsp->no_access_message();
		}	

		if (($author_id = $this->EE->input->post('author_id')) === FALSE)
		{
			return $this->EE->dsp->no_access_message();
		}  
		
		
		$entry_id	= $this->EE->input->get_post('entry_id');						
		$row			= $this->EE->input->get_post('row');						
		$cat_id		= $this->EE->input->get_post('cat_id');						
		
		$this->EE->lang->loadfile('content');
		
		$sql = "SELECT exp_gallery_entries.author_id
				FROM	exp_gallery_entries, exp_gallery_comments
				WHERE  exp_gallery_entries.entry_id = exp_gallery_comments.entry_id
				AND	exp_gallery_comments.comment_id = '$comment_id'";
		
		$query = $this->EE->db->query($sql);

		if ($this->EE->session->userdata['group_id'] != 1)
		{
			if ($query->row('author_id')  != $this->EE->session->userdata('member_id'))
			{
				return $this->EE->dsp->no_access_message();
			}
			}
		
		/** ----------------------------------------
		/**  Fetch gallery preferences
		/** ----------------------------------------*/
		
		$sql = "SELECT exp_galleries.gallery_comment_require_email
				FROM	exp_gallery_entries, exp_galleries
				WHERE  exp_gallery_entries.gallery_id = exp_galleries.gallery_id
				AND	exp_gallery_entries.entry_id = '".$this->EE->db->escape_str($entry_id)."'";
				
		$query = $this->EE->db->query($sql);		
		
		unset($sql);
				
		if ($query->num_rows() == 0)
		{
			return FALSE;
		}
		
		foreach ($query->row_array() as $key => $val)
		{
			$$key = $val;
		}

		/** -------------------------------------
		/**  Error checks
		/** -------------------------------------*/
		
		$error = array();

		if ($author_id == 0)
		{
			// Fetch language file
			
			$this->EE->lang->loadfile('myaccount');
			
			if ($gallery_comment_require_email == 'y')
			{
				/** -------------------------------------
				/**  Is email missing?
				/** -------------------------------------*/
				
				if ($_POST['email'] == '')
				{
					$error[] = $this->EE->lang->line('missing_email');
				}
				
				/** -------------------------------------
				/**  Is email valid?
				/** -------------------------------------*/

				$this->EE->load->helper('email');

				if ( ! valid_email($_POST['email']))
				{
					$error[] = $this->EE->lang->line('invalid_email_address');
				}
				
				
				/** -------------------------------------
				/**  Is email banned?
				/** -------------------------------------*/
				
				if ($this->EE->session->ban_check('email', $_POST['email']))
				{
					$error[] = $this->EE->lang->line('banned_email');
				}
			}
		}

		/** -------------------------------------
		/**  Is comment missing?
		/** -------------------------------------*/
		
		if ($_POST['comment'] == '')
		{
			$error[] = $this->EE->lang->line('missing_comment');
		}

		
		/** -------------------------------------
		/**  Display error is there are any
		/** -------------------------------------*/
		 if (count($error) > 0)
		 {
			$msg = '';
			
			foreach($error as $val)
			{
				$msg .= $val.'<br />';  
			}
			
			return $this->EE->dsp->error_message($msg);
		 }

		// Build query
		
		if ($author_id == 0)
		{
			$data = array(
							'name'		=> $_POST['name'],	
							'email'		=> $_POST['email'],	
							'url'		=> $_POST['url'],	
							'location'	=> $_POST['location'],	
							'comment'	=> $_POST['comment']	
						 );
		}
		else
		{
		
			$data = array(
							'comment'	=> $_POST['comment']	
						 );
		}

			
		$this->EE->db->query($this->EE->db->update_string('exp_gallery_comments', $data, "comment_id = '$comment_id'")); 

		$this->EE->functions->redirect(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=view_comments'.AMP.'gallery_id='.$this->gallery_id.AMP.'entry_id='.$entry_id.AMP.'cat_id='.$cat_id.AMP.'row='.$row.AMP.'msg=update');
		exit;
	}



	/** -----------------------------------------
	/**  Change Comment Status
	/** -----------------------------------------*/
	function change_comment_status()
	{
  
		$entry_id	= $this->EE->input->get_post('entry_id');
		$author_id	= $this->EE->input->get_post('author_id');
		$cat_id		= $this->EE->input->get_post('cat_id');
		$row		= $this->EE->input->get_post('row');
  
		if ( ! is_numeric($entry_id))
			return FALSE;
		if ( ! is_numeric($cat_id))
			return FALSE;
			
		// Change status

		if (is_numeric($this->EE->input->get_post('comment_id')))	
		{
			$status = (isset($_GET['status']) AND $_GET['status'] == 'close') ? 'c' : 'o'; 
			$this->EE->db->query("UPDATE exp_gallery_comments SET status = '".$status."' WHERE comment_id = '".$this->EE->db->escape_str($this->EE->input->get_post('comment_id'))."'");
		}
		else
		{
			$status = ($this->EE->input->get_post('action') == 'close') ? 'c' : 'o';
			foreach ($_POST['toggle'] as $key => $val)
			{		
				$this->EE->db->query("UPDATE exp_gallery_comments SET status = '".$status."' WHERE comment_id = '".$this->EE->db->escape_str($val)."'");
			}
		}
		
		/** ------------------------------------------------
		/**  UPDATE Info for Entry
		/** ------------------------------------------------*/
		
		$query = $this->EE->db->query("SELECT MAX(comment_date) AS max_date FROM exp_gallery_comments 
							 WHERE status = 'o' AND entry_id = '$entry_id'");
		$comment_date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;
		
		$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_gallery_comments WHERE status = 'o' AND entry_id = '$entry_id' ");
		$total_comments = $query->row('count') ;
		
		$this->EE->db->query("UPDATE exp_gallery_entries SET total_comments = '$total_comments', recent_comment_date = '$comment_date' WHERE entry_id = '$entry_id'");
	
		/** ------------------------------------------------
		/**  Update comment total and "recent comment" date
		/** ------------------------------------------------*/
	 
		$query = $this->EE->db->query("SELECT comment_date FROM exp_gallery_comments egc, exp_gallery_entries ege WHERE egc.entry_id = ege.entry_id AND egc.status = 'o' AND ege.cat_id = '{$cat_id}' ORDER BY egc.comment_date desc LIMIT 1");
		$comment_date = ($query->num_rows() == 0) ? 0 : $query->row('comment_date') ;
		
		$query = $this->EE->db->query("SELECT COUNT(egc.comment_id) AS count FROM exp_gallery_comments egc, exp_gallery_entries ege WHERE egc.entry_id = ege.entry_id AND egc.status = 'o' AND ege.cat_id = '{$cat_id}'");
		$total_comments = $query->row('count') ;

		$this->EE->db->query("UPDATE exp_gallery_categories SET total_comments = '{$total_comments}', recent_comment_date = '$comment_date' WHERE cat_id = '{$cat_id}'");				
	 
		/** ----------------------------------------
		/**  Update member comment total and date
		/** ----------------------------------------*/
		
		if ($author_id != 0)
		{
			$query = $this->EE->db->query("SELECT MAX(comment_date) AS max_date FROM exp_comments 
								 WHERE status = 'o' AND author_id = '$author_id'");
			$comment_date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;
			
			$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_comments WHERE status = 'o' AND author_id = '$author_id'");
			$comment_count = $query->row('count') ;
				
			$query = $this->EE->db->query("SELECT MAX(comment_date) AS max_date FROM exp_gallery_comments 
								 WHERE status = 'o' AND author_id = '$author_id'");
			$g_comment_date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;
			
			$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_gallery_comments WHERE status = 'o' AND author_id = '$author_id'");
			$gcomment_count = $query->row('count') ;
				
			$date  = ($comment_date > $g_comment_date) ? $comment_date : $g_comment_date;
			$total = $comment_count + $gcomment_count;
			
			$this->EE->db->query("UPDATE exp_members SET total_comments = {$total}, last_comment_date = '$date' WHERE member_id = '$author_id'");
		}			
				
		$this->EE->functions->redirect(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=view_comments'.AMP.'gallery_id='.$this->gallery_id.AMP.'entry_id='.$entry_id.AMP.'cat_id='.$cat_id.AMP.'row='.$row.AMP.'msg=status');
		exit;
	}



	/** -----------------------------------------
	/**  Delete comment confirmation
	/** -----------------------------------------*/
	function delete_comment_confirm()
	{
		if ( ! $entry_id = $this->EE->input->get_post('entry_id'))
		{
			return FALSE;
		}  
		
		if ( ! $cat_id = $this->EE->input->get_post('cat_id'))
		{
			return FALSE;
		}  
		
		 if ( ! $this->EE->input->post('toggle'))
		{
			return $this->EE->functions->redirect(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=view_comments'.AMP.'gallery_id='.$this->gallery_id.AMP.'entry_id='.$entry_id.AMP.'cat_id='.$cat_id.AMP.'row='.$this->EE->input->get_post('row'));
		}
		
		if ($this->EE->input->get_post('action') != 'delete')
		{
			return $this->change_comment_status();
		}

		
		$this->EE->lang->loadfile('content');
	
	
		// Grabe the comment ID numbers
		
		$comments = array();
		foreach ($_POST['toggle'] as $key => $val)
		{		
			$comments[] = $val;
		}
		
		// Verify the the user has permission to delete.		

		if ( ! $this->EE->dsp->allowed_group('can_delete_all_comments'))
		{
			if ( ! $this->EE->dsp->allowed_group('can_delete_own_comments'))
			{	 
				return $this->EE->dsp->no_access_message();
			}
			else
			{
				if (count($comments) > 0)
				{
					$sql = "SELECT exp_gallery_entries.author_id
							FROM	exp_gallery_entries, exp_gallery_comments
							WHERE  exp_gallery_entries.entry_id = exp_gallery_comments.entry_id
							AND	exp_gallery_comments.comment_id IN ('".implode("','", $comments)."')";
				}
				
				$comments	= array();
				
				$query = $this->EE->db->query($sql);
				
				if ($query->num_rows() > 0)
				{
					foreach($query->result_array() as $row)
					{
						if ($row['author_id'] != $this->EE->session->userdata('member_id'))
						{
							$comments[] = $row['comment_id'];
						}
					}
				}
			}
		}

	
	
	
	
	
	
		$r  = $this->EE->dsp->form_open(array('action' => 'C=modules'.AMP.'M=gallery'.AMP.'P=del_comment'.AMP.'gallery_id='.$this->gallery_id));
		$r .= $this->EE->dsp->input_hidden('comment_ids', implode('|', $comments));
		$r .= $this->EE->dsp->input_hidden('entry_id', 	$entry_id);
		$r .= $this->EE->dsp->input_hidden('cat_id', 		$cat_id);
		$r .= $this->EE->dsp->input_hidden('row', 		$this->EE->input->get_post('row'));
						
		$r .= $this->EE->dsp->qdiv('alertHeading', $this->EE->lang->line('delete_confirm'));
		$r .= $this->EE->dsp->div('box');
		
		$r .= '<b>'.$this->EE->lang->line('delete_comments_confirm').'</b>';
		
		$r .= $this->EE->dsp->br(2).
			  $this->EE->dsp->qdiv('alert', $this->EE->lang->line('action_can_not_be_undone')).
			  $this->EE->dsp->br().
			  $this->EE->dsp->input_submit($this->EE->lang->line('delete')).
			  $this->EE->dsp->div_c().
			  $this->EE->dsp->form_close();
			  
			  
		$title = $this->EE->lang->line('delete_confirm');			
		$crumb = $this->EE->dsp->crumb_item($this->EE->dsp->anchor(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=view_comments'.AMP.'entry_id='.$entry_id.AMP.'cat_id='.$cat_id.AMP.'gallery_id='.$this->gallery_id, $this->EE->lang->line('gallery_view_entries'))).$this->EE->dsp->crumb_item($this->EE->lang->line('delete_confirm'));

		return $this->content_wrapper($title, $crumb, $r);
	}




	/** ----------------------------------------
	/**  Delete a Comment
	/** ----------------------------------------*/
	function delete_comment()
	{
		if ( ! $comment_ids = $this->EE->input->post('comment_ids'))
		{
			return FALSE;
		}
		
		if ( ! $entry_id = $this->EE->input->post('entry_id'))
		{
			return FALSE;
		}  
		
		if ( ! $cat_id = $this->EE->input->post('cat_id'))
		{
			return FALSE;
		}  
			
		if ( ! is_numeric($cat_id))
			return FALSE;		
		if ( ! is_numeric($entry_id))
			return FALSE;	
			
		$IDS = array();
		
		foreach(explode('|', $comment_ids) as $id)
		{
			$IDS[] = $this->EE->db->escape_str($id);
		}
		
		$this->EE->lang->loadfile('content');
		
		$sql = "SELECT exp_gallery_entries.author_id as entry_author, exp_gallery_comments.comment_id, exp_gallery_comments.author_id as comment_author
				FROM	exp_gallery_entries, exp_gallery_comments
				WHERE  exp_gallery_entries.entry_id = exp_gallery_comments.entry_id
				AND	exp_gallery_comments.comment_id IN ('".implode("','", $IDS)."')";
		
		$query = $this->EE->db->query($sql);
		
		if ($query->num_rows() == 0)
		{
			return $this->EE->dsp->no_access_message();
		}
		
		$IDS = array();
		$authors = array();
		
		foreach($query->result_array() as $row)
		{	
			if ($this->EE->session->userdata['group_id'] != 1)
			{
				if ( ! $this->EE->dsp->allowed_group('can_delete_all_comments'))
				{
					if ( ! $this->EE->dsp->allowed_group('can_delete_own_comments'))
					{
						return $this->EE->dsp->no_access_message();
					}
					elseif ($row['entry_author'] != $this->EE->session->userdata('member_id'))
					{
						return $this->EE->dsp->no_access_message();
					}
				}
			}
			
			$IDS[] = $row['comment_id'];
			
			if ($row['comment_author'] != '0')
			{
				$authors[] = $row['comment_author'];
			}
		}
		
		if (count($IDS) == 0)
		{
			return $this->EE->dsp->no_access_message();
		}
		
		$this->EE->db->query("DELETE FROM exp_gallery_comments WHERE comment_id IN ('".implode("','", $IDS)."')");
		
		/** ----------------------------------------
		/**  Update authors'
		/** ----------------------------------------*/
		
		foreach(array_unique($authors) AS $author_id) 
		{
			$query = $this->EE->db->query("SELECT MAX(comment_date) AS max_date FROM exp_comments 
								 WHERE status = 'o' AND author_id = '$author_id'");
			$comment_date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;
			
			$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_comments WHERE status = 'o' AND author_id = '$author_id'");
			$comment_count = $query->row('count') ;
				
			$query = $this->EE->db->query("SELECT MAX(comment_date) AS max_date FROM exp_gallery_comments 
								 WHERE status = 'o' AND author_id = '$author_id'");
			$g_comment_date = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;
			
			$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_gallery_comments WHERE status = 'o' AND author_id = '$author_id'");
			$gcomment_count = $query->row('count') ;
				
			$date  = ($comment_date > $g_comment_date) ? $comment_date : $g_comment_date;
			$total = $comment_count + $gcomment_count;
			
			$this->EE->db->query("UPDATE exp_members SET total_comments = {$total}, last_comment_date = '$date' WHERE member_id = '$author_id'");
		}
		
		$query = $this->EE->db->query("SELECT MAX(comment_date) AS max_date FROM exp_gallery_comments 
							 WHERE status = 'o' AND entry_id = '$entry_id'");
		
		$comment_date  = ($query->num_rows() == 0 OR ! is_numeric($query->row('max_date') )) ? 0 : $query->row('max_date') ;
		
		$query = $this->EE->db->query("SELECT COUNT(*) AS count FROM exp_gallery_comments WHERE entry_id = '$entry_id' AND status = 'o'");
		
		$this->EE->db->query("UPDATE exp_gallery_entries SET total_comments = '".($query->row('count') )."', recent_comment_date = '$comment_date' WHERE entry_id = '$entry_id'");	  
	 
		$query = $this->EE->db->query("SELECT total_comments FROM exp_gallery_categories WHERE cat_id = '{$cat_id}'");

		$this->EE->db->query("UPDATE exp_gallery_categories SET total_comments = '".($query->row('total_comments')  - 1)."', recent_comment_date = '$comment_date' WHERE cat_id = '{$cat_id}'");				
	
		$this->EE->functions->redirect(BASE.AMP.'C=modules'.AMP.'M=gallery'.AMP.'P=view_comments'.AMP.'gallery_id='.$this->gallery_id.AMP.'entry_id='.$entry_id.AMP.'cat_id='.$cat_id.AMP.'row='.$this->EE->input->post('row').AMP.'msg=deleted');
		exit;
	}

}
// END CLASS

/* End of file mcp.gallery.php */
/* Location: ./system/expressionengine/modules/gallery/mcp.gallery.php */