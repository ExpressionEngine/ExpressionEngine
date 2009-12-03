<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2009, EllisLab, Inc.
 * @license		http://expressionengine.com/docs/license.html
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
class Content_files extends Controller {


	function Content_files()
	{
		// Call the Controller constructor.  
		// Without this, the world as we know it will end!
		parent::Controller();

		// Does the "core" class exist?  Normally it's initialized
		// automatically via the autoload.php file.  If it doesn't
		// exist it means there's a problem.
		if ( ! isset($this->core) OR ! is_object($this->core))
		{
			show_error('The ExpressionEngine Core was not initialized.  Please make sure your autoloader is correctly set up.');
		}

		if (! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->lang->loadfile('filemanager');
		$this->load->vars(array('cp_page_id'=>'content_files'));
		
		if (isset($_GET['ajax']))
        {
            $this->output->enable_profiler(FALSE);
        }
		
		$this->javascript->compile();
	}

	// --------------------------------------------------------------------

	/**
	 * Index function
	 * 
	 * @access	public
	 * @return	mixed
	 */
	function index()
	{
		if (! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->helper(array('form', 'string', 'url', 'file'));
		$this->load->library('table');
		$this->load->library('encrypt');
		$this->load->model('tools_model');

		$this->cp->set_variable('cp_page_title', $this->lang->line('content_files'));
		
		$this->cp->add_js_script(array(
		            'plugin'    => array('fancybox', 'tablesorter', 'ee_upload')
		    )
		);
		
		$this->cp->add_to_head('<link type="text/css" rel="stylesheet" href="'.BASE.AMP.'C=css'.AMP.'M=fancybox" />');

		$this->jquery->tablesorter('.mainTable', '{
			headers: {4: {sorter: false}, 5: {sorter: false}, 6: {sorter: false}},
			widgets: ["zebra"],
			sortList: [[0,0]] 
		}');

		$this->javascript->output(array(
			$this->javascript->show("#file_tools")
		));

		$this->javascript->output('
			$("#download_selected").css("display", "block");

			function show_file_info(file) {
				$("#file_information_hold").slideDown("fast");
				$("#file_information_header").removeClass("closed");
				$("#file_information_header").addClass("open");

				$("#file_information_hold").html("<p style=\"text-align: center;\"><img src=\"'. $this->cp->cp_theme_url.'images/indicator.gif\" alt=\"'.$this->lang->line('loading').'\" /><br />'.$this->lang->line('loading').'...</p>");

				$.get("'.str_replace('&amp;', '&', BASE).'&C=content_files&M=file_info",
					{file: file},
					function(data){
						$("#file_information_hold").html(data);
					}
				);
			}

			$("#showToolbarLink a").toggle(
				function(){
					$("#file_manager_tools").hide();
					$("#showToolbarLink a span").text("'.$this->lang->line('show_toolbar').'");
					$("#showToolbarLink").animate({
						marginRight: "20"
					});
					$("#file_manager_holder").animate({
						marginRight: "10"
					});
				}, function (){
					$("#showToolbarLink a span").text("'.$this->lang->line('hide_toolbar').'");
					$("#showToolbarLink").animate({
						marginRight: "314"
					});
					$("#file_manager_holder").animate({
						marginRight: "300"
					}, function(){
						$("#file_manager_tools").show();
					});
				}
			);

			$("#file_manager_tools h3 a").toggle(
				function(){
					$(this).parent().next("div").slideUp();
					$(this).toggleClass("closed");
				}, function(){
					$(this).parent().next("div").slideDown();
					$(this).toggleClass("closed");
				}
			);

			$("#file_manager_list h3").toggle(
				function(){
					document.cookie="exp_hide_upload_"+$(this).next().attr("id")+"=true";
					$(this).next().slideUp();
					$(this).toggleClass("closed");
				}, function(){
					document.cookie="exp_hide_upload_"+$(this).next().attr("id")+"=false";
					$(this).next().slideDown();
					$(this).toggleClass("closed");
				}
			);

			// collapse sidebar and folder list by default
			$("#file_manager_tools h3.closed").next("div").hide();
			$("#file_manager_tools h3.closed a").click();

			function upload_fail(message)
			{
				// change status and fade it out
				$("#progress").html("<span class=\"notice\">"+message+"</span>");
			}
		
			$("input[type=file]").ee_upload({
				url: "'.str_replace('&amp;', '&', BASE).'&C=content_files&M=upload_file&is_ajax=true",
				onStart:function(el) {
					$("#progress").html("<p><img src=\"'. $this->cp->cp_theme_url.'images/indicator.gif\" alt=\"'.$this->lang->line('loading').'\" />Uploading File...</p>").show();
					
					dir_id = $("#upload_dir").val();
					return {upload_dir: dir_id};
				},
				onComplete: function(res, el, opt) {
					if (typeof(res) == "object") {
						if (res.success) {
							var directory_container = "#dir_id_"+opt.upload_dir;

							// @confirm this is a bit ugly - cannot think of an easy way to send this as part of the
							// response without forcing a layout
							var refresh_url = "'.str_replace('&amp;', '&', BASE).'&C=content_files&ajax=true&directory="+opt.upload_dir+"&enc_path="+res.enc_path;

							$.get(refresh_url, function(response) {
								var tmp = $("<div></div>");
								tmp.append(response);
								tmp = tmp.find("tbody tr");

								$(directory_container+" tbody").append(tmp);

								// remove row with warning message if its there
								$(directory_container+" tbody .no_files_warning").parent().remove();

								// let the tablesorter plugin know that we have an update
								$(directory_container+" table").trigger("update");

								// Reset sort to force re-stripe
								var sorting = [[0,0]]; 
								$("table").trigger("sorton",[sorting]);

								setup_events(tmp);

								$("#progress").html(res).slideUp("slow");
							}, "html");
							
						}
						else {
							upload_fail(res.error);
						}
					}
				}
			});
		');

		// tools
		$this->javascript->click('#download_selected a', '
			$("#files_form").attr("action", $("#files_form").attr("action").replace(/delete_files_confirm/, "download_files"))
			$("#files_form").submit();
		');

		$this->javascript->click('a#email_files', 'alert("not yet functional");');

		$this->javascript->click('#delete_selected_files a', '
			// these may be been downloaded: ensure the action attr is correct
			$("#files_form").attr("action", $("#files_form").attr("action").replace(/download_files/, "delete_files_confirm"))
			$("#files_form").submit();
		');

		$this->javascript->output('
			$(".toggle_all").toggle(
				function(){
					$(".mainTable tbody tr").addClass("selected");
					$("input[class=toggle]").each(function() {
						this.checked = true;
					});
				}, function (){
					$(".mainTable tbody tr").removeClass("selected");
					$("input[class=toggle]").each(function() {
						this.checked = false;
					});
				}
			);

			$("input[class=toggle]").each(function() {
				this.checked = false;
			});

			function setup_events(el) {

				$("td.fancybox a").unbind("click").
					fancybox({
						"showEditLink": true
					}).
					click(function(e){
						show_file_info($(this).attr("rel"));
					});

					// Set the row as "selected"
					$(".toggle").unbind("click").click(function(e){
						$(this).parent().parent().toggleClass("selected");
					});

					$(".mainTable td").unbind("click").click(function(e){
						// if the control or command key was pressed, select the file
						if (e.ctrlKey || e.metaKey)
						{
							$(this).parent().toggleClass("selected"); // Set row as selected

							if ( ! $(this).parent().find(".file_select :checkbox").attr("checked"))
							{
								$(this).parent().find(".file_select :checkbox").attr("checked", "true");
							}
							else
							{
								$(this).parent().find(".file_select :checkbox").attr("checked", "");
							}
						}
					});
			}
			setup_events();
		');

		$vars = array();

		if ($this->input->get_post('ajax') == 'true')
		{
			$id = $this->input->get_post('directory');
			$path = $this->input->get_post('enc_path');
			$vars = array_merge($vars, $this->_map($id, $path));
			
			$vars['EE_view_disable'] = TRUE;
		}
		else
		{
			$vars = array_merge($vars, $this->_map());
		}

		$this->javascript->compile();

		$this->load->view('content/file_browse', $vars);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Get a directory map
	 *
	 * Creates an array of directories and their content
	 * 
	 * @access	public
	 * @param	int		optional directory id (defaults to all)
	 * @return	mixed
	 */
	function _map($dir_id = FALSE, $enc_path = FALSE)
	{
		$upload_directories = $this->tools_model->get_upload_preferences($this->session->userdata('member_group'));
		// if a user has no directories available to them, then they have no right to be here
		if ($this->session->userdata['group_id'] != 1 && $upload_directories->num_rows() == 0)
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$vars['file_list'] = array(); // will hold the list of directories and files
		$vars['upload_directories'] = array();

		$this->load->helper('directory');
		
		if ($enc_path)
		{
			$enc_path = $this->encrypt->decode(rawurldecode($enc_path), $this->session->sess_crypt_key);
		}

		foreach ($upload_directories->result() as $dir)
		{
			if ($dir_id && $dir->id != $dir_id)
			{
				continue;
			}
			
			// we need to know the dirs for the purposes of uploads, so grab them here
			$vars['upload_directories'][$dir->id] = $dir->name;

			$vars['file_list'][$dir->id]['id'] = $dir->id;
			$vars['file_list'][$dir->id]['name'] = $dir->name;
			$vars['file_list'][$dir->id]['url'] = $dir->url;
			$vars['file_list'][$dir->id]['display'] = ($this->input->cookie('hide_upload_dir_id_'.$dir->id) == 'true') ? 'none' : 'block';
			$files = $this->tools_model->get_files($dir->server_path, $dir->allowed_types);

			$file_count = 0;
			$vars['file_list'][$dir->id]['files'] = array(); // initialize so empty dirs don't throw errors

			// construct table row arrays
			foreach($files as $file)
			{
				if ($enc_path && $enc_path != $file['relative_path'].$file['name'])
				{
					continue;
				}

				if ($file['name'] == '_thumbs' OR $file['name'] == 'folder')
				{
					continue;
				}

				$enc_url_path = rawurlencode($this->encrypt->encode($dir->url.$file['name'], $this->session->sess_crypt_key)); //needed for displaying image in edit mode

				// the extension check is not needed, as allowed_types takes care of that.
				// if (strncmp($file['mime'], 'image', 5) == 0 AND (in_array(strtolower(substr($file['name'], -3)), array('jpg', 'gif', 'png'))))

				if (strncmp($file['mime'], 'image', 5) == 0)
				{
					$vars['file_list'][$dir->id]['files'][$file_count] = array(
						array(
							'class'=>'fancybox', 
							'data' => '<a class="fancybox" id="img_'.str_replace(".", '', $file['name']).'" href="'.$dir->url.$file['name'].'" title="'.$file['name'].NBS.'" rel="'.$file['encrypted_path'].'">'.$file['name'].'</a>',
						),
						array(
							'class'=>'fancybox align_right', 
							'data' => number_format($file['size']/1000, 1).NBS.lang('file_size_unit'),
						),
						array(
							'class'=>'fancybox', 
							'data' => $file['mime'],
						),
						array(
							'class'=>'fancybox', 
							'data' => date('M d Y - H:ia', $file['date']),
						),
						array(
							'id' => 'edit_img_'.str_replace(".", '', $file['name']), 
							'data' => '<a href="'.BASE.AMP.'C=content_files'.AMP.'M=prep_edit_image'.AMP.'url_path='.$enc_url_path.AMP.'file='.$file['encrypted_path'].'" title="'.$file['name'].'">'.lang('edit').'</a>'
						),
						'<a href="'.BASE.AMP.'C=content_files'.AMP.'M=download_files'.AMP.'file='.$file['encrypted_path'].'" title="'.$file['name'].'">'.lang('file_download').'</a> | '.
						'<a href="'.BASE.AMP.'C=content_files'.AMP.'M=delete_files_confirm'.AMP.'file='.$file['encrypted_path'].'" title="'.$file['name'].'">'.lang('delete').'</a>',
						array(
							'class' => 'file_select', 
							'data' => form_checkbox('file[]', $file['encrypted_path'], FALSE, 'class="toggle"')
						)
					);
				}
				else
				{
					$vars['file_list'][$dir->id]['files'][$file_count] = array(
						$file['name'],
						array(
							'class'=>'align_right', 
							'data' => number_format($file['size']/1000, 1).NBS.lang('file_size_unit'),
						),
						$file['mime'],
						date('M d Y - H:ia', $file['date']),
						'--',
						'<a href="'.BASE.AMP.'C=content_files'.AMP.'M=download_files'.AMP.'file='.$file['encrypted_path'].'" title="'.$file['name'].'">'.lang('file_download').'</a> | '.
						'<a href="'.BASE.AMP.'C=content_files'.AMP.'M=delete_files_confirm'.AMP.'file='.$file['encrypted_path'].'" title="'.$file['name'].'">'.lang('delete').'</a>',
						array(
							'class' => 'file_select', 
							'data' => form_checkbox('file[]', $file['encrypted_path'], FALSE, 'class="toggle"')
						)
					);
				}

				$file_count++;
			}

		}

		return $vars;
	}

	// --------------------------------------------------------------------

	/**
	 * File Info
	 *
	 * Used in the file previews ajax call
	 * 
	 * @access	public
	 * @return	mixed
	 */
	function file_info()
	{
		if (! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->output->enable_profiler(FALSE);
		$this->load->helper(array('file', 'html'));

		$this->load->library('encrypt');
		$file = $this->encrypt->decode(rawurldecode($this->input->get_post('file')), $this->session->sess_crypt_key);

		$file_info = get_file_info($file, array('name', 'size', 'fileperms'));

		if ( ! $file_info)
		{
			exit($this->lang->line('no_file'));
		}
		else
		{
			$file_type = get_mime_by_extension($file);
			$where = str_replace(str_replace(SYSDIR.'/', '', BASEPATH), '', substr($file, 0, strrpos($file, '/')).'/');

			$output = '<ul>';

			$output .= '<li class="file_name">'.$file_info['name'].'</li>';
			$output .= '<li><span>'.$this->lang->line('size').':</span> '.number_format($file_info['size']/1000, 1).'KB</li>';

			if ($file_type != FALSE)
			{
				$output .= '<li><span>'.$this->lang->line('kind').':</span> '.$file_type.'</li>';
			}

			$output .= '<li><span>'.$this->lang->line('where').':</span> '.$where.'</li>';
			$output .= '<li><span>'.$this->lang->line('permissions').':</span> '.symbolic_permissions($file_info['fileperms']).'</li>';
			$output .= '</ul>';

//			$output .= '<div id="file_tags"></div>'; // not currently used, but in there for potential future compatibility

			exit($output);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Upload File
	 *
	 * @access	public
	 * @return	mixed
	 */
	function upload_file()
	{
		if (! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		// @todo: this function should be migrated to file manager lib
		$this->load->library('filemanager');
		$this->load->model('tools_model');
		// get upload dir info
		$upload_id = $this->input->get_post('upload_dir');

		$upload_dir_result = $this->tools_model->get_upload_preferences($this->session->userdata('member_group'), $upload_id);
		$upload_dir_prefs = $upload_dir_result->row();

		switch($upload_dir_prefs->allowed_types)
		{
			case 'all' : $config['allowed_types'] = '*';
				break;
			case 'img' : $config['allowed_types'] = 'jpg|png|gif';
				break;
			default :
				$config['allowed_types'] = $upload_dir_prefs->allowed_types;
		}

		$config['upload_path'] = $upload_dir_prefs->server_path;
		$config['max_size']	= $upload_dir_prefs->max_size;
		$config['max_width']  = $upload_dir_prefs->max_width;
		$config['max_height']  = $upload_dir_prefs->max_height;

		$this->load->library('upload', $config);

		$try_upload = $this->upload->do_upload();

		// We use an iframe to simulate asynchronous uploading.  Files submitted
		// in this way will have the "is_ajax" field, otherwise they where normal
		// file upload submissions.

		if ( ! $try_upload)
		{
			if ($this->input->get_post('is_ajax') == 'true')
			{
				echo '<script type="text/javascript">parent.EE_uploads.'.$this->input->get('frame_id').' = '.$this->javascript->generate_json(array('error' => $this->upload->display_errors())).';</script>';
				exit;
			}
			else
			{
				$this->session->set_flashdata('message_failure', $this->upload->display_errors());
				$this->functions->redirect(BASE.AMP.'C=content_files');
			}
		}
		else
		{
			$this->load->library('encrypt');
			$file_info = $this->upload->data();
			$encrypted_path = rawurlencode($this->encrypt->encode($file_info['full_path'], $this->session->sess_crypt_key));

			// upload sucessful, now create thumb
			$thumb_path = $file_info['file_path'].'_thumbs'.DIRECTORY_SEPARATOR;

			if ( ! is_dir($thumb_path))
			{
				mkdir($thumb_path);
			}

			$resize['source_image']		= $file_info['full_path'];
			$resize['new_image']		= $thumb_path.'thumb_'.$file_info['file_name'];
			$resize['maintain_ratio']	= FALSE;
			$resize['image_library']	= $this->config->item('image_resize_protocol');
			$resize['library_path']		= $this->config->item('image_library_path');
			$resize['width']			= 73;
			$resize['height']			= 60;

			$this->load->library('image_lib', $resize);

			$thumb_errors = '';

			if ( ! $this->image_lib->resize())
			{
				// @todo find a good way to display errors
				$thumb_errors = $this->image_lib->display_errors();
			}

			if ($this->input->get_post('is_ajax') == 'true')
			{
				$response = array(
					'success'		=> $this->lang->line('upload_success'),
					'enc_path'		=> $encrypted_path,
					'filename'		=> $file_info['file_name'],
					'filesize'		=> $file_info['file_size'],
					'filetype'		=> $file_info['file_type'],
					'date'			=> date('M d Y - H:ia')
				);

				echo '<script type="text/javascript">parent.EE_uploads.'.$this->input->get('frame_id').' = '.$this->javascript->generate_json($response).';</script>';
			}
			else
			{
				$this->session->set_flashdata('message_success', $this->lang->line('upload_success'));
				$this->functions->redirect(BASE.AMP.'C=content_files');
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Download Files
	 *
	 * If a single file is passed, it is offered as a download, but if an array
	 * is passed, they are zipped up and offered as download
	 * 
	 * @access	public
	 * @return	mixed
	 */
	function download_files($files = array())
	{
		if (! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ($this->input->get_post('file') == '')
		{
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}

		$this->load->library('encrypt');

		if (is_array($this->input->get_post('file')))
		{

			// extract each filename, add to files array
			foreach ($this->input->get_post('file') as $scrambled_file)
			{
				$files[] = $this->encrypt->decode(rawurldecode($scrambled_file), $this->session->sess_crypt_key);
			}
		}
		else
		{
			$file_offered = $this->encrypt->decode(rawurldecode($this->input->get_post('file')), $this->session->sess_crypt_key);

			if ($file_offered != '')
			{
				$files = array($file_offered);
			}
		}

		$files_count = count($files);

		if ($files_count == 0)
		{
			return; // move along
		}
		elseif ($files_count == 1)
		{
			// no point in zipping for a single file... let's just send the file

			$this->load->helper('download');

			$data = file_get_contents($files[0]);
			$name = substr(strrchr($files[0], '/'), 1);
			force_download($name, $data);
		}
		else
		{
			// its an array of files, zip 'em all

			$this->load->library('zip');

			foreach ($files as $file)
			{
				$this->zip->read_file($file);
			}

			$this->zip->download('images.zip'); 
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Delete Files Confirm
	 *
	 * Used to confirm deleting files
	 *
	 * @access	public
	 * @return	mixed
	 */
	function delete_files_confirm()
	{
		if (! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->helper('form');

		$files = $this->input->get_post('file');
		
		if ( ! is_array($files))
		{
			$files = array($files);
		}

		$vars['files'] = $files;

		if ($vars['files'] == 1)
		{
			$vars['del_notice'] =  'confirm_del_file';
		}
		else
		{
			$vars['del_notice'] = 'confirm_del_files';
		}

		$this->cp->set_variable('cp_page_title', $this->lang->line('delete_selected_files'));

		$this->javascript->compile();

		$this->load->view('content/file_delete_confirm', $vars);

	}

	// --------------------------------------------------------------------

	/**
	 * Delete Files
	 * 
	 * @access	public
	 * @return	mixed
	 */
	function delete_files()
	{
		if (! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->library('encrypt');

		$files = $this->input->get_post('file');

		if ($files == '')
		{
			// nothing for you here
			$this->session->set_flashdata('message_failure', $this->lang->line('choose_file'));
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}

		$delete_problem = 0;

		foreach($files as $file)
		{
			$file = rtrim($this->encrypt->decode(rawurldecode($file), $this->session->sess_crypt_key), DIRECTORY_SEPARATOR);

			$path = substr($file, 0, strrpos($file, DIRECTORY_SEPARATOR)+1);
			$thumb = substr($file, strrpos($file, DIRECTORY_SEPARATOR)+1);
			$thumb_path = $path.'_thumbs'.DIRECTORY_SEPARATOR.'thumb_'.$thumb;

			if ( ! @unlink($file))
			{
				$delete_problem++;
			}

			// Delete thumb also
			if (file_exists($thumb_path))
			{
				@unlink($thumb_path);
			}
		}

		if ($delete_problem == 0)
		{
			// no problems
			$this->session->set_flashdata('message_success', $this->lang->line('delete_success'));
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}
		else
		{
			// something's up.
			$this->session->set_flashdata('message_failure', $this->lang->line('delete_fail'));
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Show an Image
	 * 
	 * @access	public
	 * @return	mixed
	 */
	function display_image()
	{
		if (! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		$this->load->library('encrypt');
		$this->load->helper('file');

		$this->output->set_header('Content-Type: image/png');
		$file = $this->encrypt->decode(rawurldecode($this->input->get_post('file')), $this->session->sess_crypt_key);
		echo read_file($file);
	}

	// --------------------------------------------------------------------

	/**
	 * Show Image Editing Screen
	 * 
	 * @access	public
	 * @return	mixed
	 */
	function prep_edit_image()
	{
		if (! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}
		
		$this->load->helper(array('form', 'string', 'url', 'file'));
		$this->load->library('encrypt');

		$vars['file'] = $this->encrypt->decode(rawurldecode($this->input->get_post('file')), $this->session->sess_crypt_key);
		$vars['url_path'] = $this->encrypt->decode(rawurldecode($this->input->get_post('url_path')), $this->session->sess_crypt_key).'?f='.time();

		$this->jquery->ui(BASE.AMP.'C=javascript'.AMP.'M=load'.AMP.'ui=resizable', TRUE);
		$this->jquery->plugin(BASE.AMP.'C=javascript'.AMP.'M=load'.AMP.'plugin=jcrop', TRUE);
		$this->jquery->plugin(BASE.AMP.'C=javascript'.AMP.'M=load'.AMP.'plugin=simplemodal', TRUE);

		$this->javascript->output('
			// Some page setup

			// hide the detailed data
			$(".edit_option").hide();

			// if this is read, then js is enabled... set up the ajax-y goodness...
//			$("#ajax").val("TRUE");

			// if JS is enabled, switch to icons for rotate by hiding the 
			// dropdown and revealing the list of icons (hidden via css)
			$("#rotate_fieldset p").hide();
			$("#rotate_fieldset .icons").show();

			$("#showToolbarLink a").toggle(
				function(){
					$("#file_manager_tools").hide();
					$("#showToolbarLink a span").text("'.$this->lang->line('show_toolbar').'");
					$("#showToolbarLink").animate({
						marginRight: "20"
					});
					$("#file_manager_holder").animate({
						marginRight: "10"
					});
				}, function (){
					$("#showToolbarLink a span").text("'.$this->lang->line('hide_toolbar').'");
					$("#showToolbarLink").animate({
						marginRight: "314"
					});
					$("#file_manager_holder").animate({
						marginRight: "300"
					}, function(){
						$("#file_manager_tools").show();
					});
				}
			);

			$("#file_manager_tools h3 a").toggle(
				function(){
					$(this).parent().next("div").slideUp();
					$(this).toggleClass("closed");
				}, function(){
					$(this).parent().next("div").slideDown();
					$(this).toggleClass("closed");
				}
			);

			$("#file_manager_list h3").toggle(
				function(){
					$(this).next().slideUp();
					$(this).toggleClass("closed");
				}, function(){
					$(this).next().slideDown();
					$(this).toggleClass("closed");
				}
			);

			function cropCoords(coords)
			{
				$("#crop_x").val(Math.floor(coords.x));
				$("#crop_y").val(Math.floor(coords.y));
				$("#crop_width").val(Math.floor(coords.w));
				$("#crop_height").val(Math.floor(coords.h));
			};

			function clearBoxes(reveal)
			{
				$(".edit_option").hide();

				if (reveal != undefined)
				{
					$("#"+reveal+"_fieldset").fadeIn();
				}

				$("#crop_x").val("");
				$("#crop_y").val("");
				$("#crop_width").val("");
				$("#crop_height").val("");
				$("#resize_width").val("");
				$("#resize_height").val("");
			}

			function resizeImage(size)
			{
				if (size == undefined){
					$("#resize_width").val($("#edit_image").width());
					$("#resize_height").val($("#edit_image").height());
				}
				else
				{
					edit_mode = true; // just a global var to indicate if its the first time we hit this..

					$("#resize_width").val(Math.floor(size.width));
					$("#resize_height").val(Math.floor(size.height));
				}
			}

			function confirm(message, callback_true, callback_false) {
				$("#confirm").modal({
					close:false, 
					overlayId:"confirmModalOverlay",
					containerId:"confirmModalContainer", 
					onShow: function (dialog) {
						dialog.data.find(".message").append(message);

						// if the user clicks "yes"
						dialog.data.find(".yes").click(function () {
							// call the callback
							if ($.isFunction(callback_true)) {
								callback_true.apply();
							}

							// close the dialog
							$.modal.close();
						});

						// if the user clicks "no"
						dialog.data.find(".no").click(function () {
							// call the callback
							if ($.isFunction(callback_false)) {
								callback_false.apply();
							}

							// close the dialog
							$.modal.close();
						});
					}
				});
			}

			// edit_mode is simply a flag to tell if the user has started editing anything at this time
			// its use is really just to prevent the confirm dialog from popping up if they switched to
			// an edit mode, but did not use it
			edit_mode = false;

			function confirm_win(mode)
			{
				if (edit_mode != false)
				{
					confirm(
						"'.$this->lang->line('exit_apply_changes').'", 
						function () {
							// "true" function, used if they say "yes"
//							$("#image_edit_form").submit(); 	// forcing the submit is not working oddly
							$("#edit_file_submit").click(); 	//we click the submit button instead
							return true;
						},
						function () {
							// "false" function, used if they say "no"
							change_mode(mode);
						}
					);
				}
				else
				{
					change_mode(mode);
				}
			}

			// OK, lets explain this. Some browsers (chrome) fire off too quickly, and the ui
			// information is not available in time, resulting in a width and height of zero.
			// This is just a work around.
			function resize_sleep()
			{
				clearBoxes("resize");

				resizeImage(); // reset boxes

				$("#edit_image").resizable({ 
					handles: "all",
					animate: true, 
					ghost: true,
					aspectRatio: true,
					knobHandles: true,
					resize: function (e, ui) {
						resizeImage(ui.size);
					}
				});
			}

			function change_mode(mode, crop_coords_array)
			{
				if (crop_coords_array == undefined)
				{
					crop_coords_array = [ 50, 50, 100, 100 ];
				}

				$("#edit_image").resizable("destroy"); // turn off resize
				$("#edit_image_holder").html("<img src=\"'.$vars['url_path'].'\" alt=\"\" id=\"edit_image\" />"); // replace image

				if (mode == "rotate")
				{
					clearBoxes("rotate");
				}
				else if (mode == "resize")
				{
					setTimeout(resize_sleep, 250);
				}
				else
				{
					clearBoxes("crop");
					$("#edit_image").Jcrop({
						setSelect: crop_coords_array,
						onChange: cropCoords,
						onSelect: function(){edit_mode = true;}
					});
				}
			}

			$("#rotate_fieldset li img").click(function() {
				$("#rotate").val($(this).attr("alt"));
				$("#submit").click(); //we click the submit button instead
			});

			var image_ratio_width = $("#edit_image").height()/$("#edit_image").width();
			var image_ratio_height = $("#edit_image").width()/$("#edit_image").height();

			function changeDimValue(dim, master_dim)
			{
/*
//				var max 	= (side == "h") ? <?php echo $max_w; ?>	: <?php echo $max_h; ?>;
				var max 	= (side == "h") ? 800 : 600;
				var unit	= "pixels"; //(side == "w") ? f.width_unit	: f.height_unit;
				var orig	= (side == "w") ? f.width_orig	: f.height_orig;
				var curr	= (side == "w") ? f.width 		: f.height;
				var t_unit	= "pixels"; //(side == "h") ? f.width_unit	: f.height_unit;
				var t_orig	= (side == "h") ? f.width_orig	: f.height_orig;
				var t_curr	= (side == "h") ? f.width		: f.height;

				var res = Math.floor((curr.value/orig.value) * t_orig.value);

				if (res > max)
				{
					t_curr.value = t_orig.value;

					curr.value	 = Math.min(curr.value, orig.value);
				}
				else
				{
					t_curr.value = res;
				}
*/
				var max = 800;
				ratio = (master_dim == "height") ? image_ratio_height : image_ratio_width;
				result = Math.floor(ratio * dim);
				return result;
			}
		');

		$this->javascript->click("#crop_mode", '
			confirm_win("crop");
		');

		$this->javascript->click("#resize_mode", '
			confirm_win("resize");
		');

		$this->javascript->keyup('.crop_dim', '
			change_mode("crop", [$("#crop_x").val(), $("#crop_y").val(), parseInt($("#crop_x").val())+parseInt($("#crop_width").val()), parseInt($("#crop_y").val())+parseInt($("#crop_height").val())]);
		');

		$this->javascript->keyup('#resize_width', '
			width = parseInt($("#resize_width").val());
			height = changeDimValue(width, "width");

			$("#edit_image, .ui-wrapper").width(width);
			$("#edit_image, .ui-wrapper").height(height);
			$("#resize_height").val(height);
		');

		$this->javascript->keyup('#resize_height', '
			height = parseInt($("#resize_height").val());
			width = changeDimValue(height, "height");

			$("#edit_image, .ui-wrapper").width(width);
			$("#edit_image, .ui-wrapper").height(height);
			$("#resize_width").val(width);
		');

		$this->javascript->click("#rotate_mode", '
			confirm_win("rotate");
		');

		$this->javascript->change("#rotate", '
			edit_mode = true;
		');

		if ($vars['file'] == '')
		{
			// nothing for you here
			$this->session->set_flashdata('message_failure', $this->lang->line('choose_file'));
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}

		$this->cp->set_variable('cp_page_title', $this->lang->line('edit') .' '.substr(strrchr($vars['file'], '/'), 1));

		// a bit of a breadcrumb override is needed
		$this->cp->set_variable('cp_breadcrumbs', array(
			BASE.AMP.'C=content_files'=> $this->lang->line('content_files')
		));

		$vars['form_hidden']['file'] = $this->input->get_post('file');
		$vars['form_hidden']['url_path'] = $this->input->get_post('url_path');

		$vars['resize_width'] = array(
										'autocomplete' => 'off',
										'name' => 'resize_width',
										'id' => 'resize_width',
										'size' => 5,
										'value' => 0
									);
		$vars['resize_height'] = array(
										'autocomplete' => 'off',
										'name' => 'resize_height',
										'id' => 'resize_height',
										'size' => 5,
										'value' => 0
									);
		$vars['crop_width'] = array(
										'autocomplete' => 'off',
										'name' => 'crop_width',
										'id' => 'crop_width',
										'class' => 'crop_dim',
										'size' => 5,
										'value' => 0
									);
		$vars['crop_height'] = array(
										'autocomplete' => 'off',
										'name' => 'crop_height',
										'id' => 'crop_height',
										'class' => 'crop_dim',
										'size' => 5,
										'value' => 0
									);
		$vars['crop_x'] = array(
										'autocomplete' => 'off',
										'name' => 'crop_x',
										'id' => 'crop_x',
										'class' => 'crop_dim',
										'size' => 5,
										'value' => 0
									);
		$vars['crop_y'] = array(
										'autocomplete' => 'off',
										'name' => 'crop_y',
										'id' => 'crop_y',
										'class' => 'crop_dim',
										'size' => 5,
										'value' => 0
									);

		$vars['rotate_options'] = array(
										"none"			=> lang('none'),
										"90"			=> lang('rotate_90r'),
										"270"			=> lang('rotate_90l'),
										"180"			=> lang('rotate_180'),
										"vrt"			=> lang('rotate_flip_vert'),
										"hor"			=> lang('rotate_flip_hor'),
										);

		$vars['rotate_selected'] = 'none';

		$this->javascript->compile();

		$this->load->view('content/prep_edit_image', $vars);
	}

	// --------------------------------------------------------------------

	// @todo: this method is duplicated in the filemanager library. Remove it from here
	// after tools > filemanager has been migrated to that lib.
	/**
	 * Handle the edit actions
	 * 
	 * @access	public
	 * @return	mixed
	 */
	function edit_image()
	{
		if (! $this->cp->allowed_group('can_access_content')  OR ! $this->cp->allowed_group('can_access_files'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		if ($this->input->get_post('edit_done'))
		{
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}

		$this->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
		$this->output->set_header("Pragma: no-cache");

		$this->load->library('encrypt');

		$file = $this->encrypt->decode(rawurldecode($this->input->get_post('file')), $this->session->sess_crypt_key);

		if ($file == '')
		{
			// nothing for you here
			$this->session->set_flashdata('message_failure', $this->lang->line('choose_file'));
			$this->functions->redirect(BASE.AMP.'C=content_files');
		}

		// crop takes precendence over resize
		// we need at least a width
		if ($this->input->get_post('crop_width') != '' AND $this->input->get_post('crop_width') != 0)
		{

			$config['width'] = $this->input->get_post('crop_width');
			$config['maintain_ratio'] = FALSE;
			$config['x_axis'] = $this->input->get_post('crop_x');
			$config['y_axis'] = $this->input->get_post('crop_y');
			$action = 'crop';

			if ($this->input->get_post('crop_height') != '')
			{
				$config['height'] = $this->input->get_post('crop_height');
			}
			else
			{
				$config['master_dim'] = 'width';
			}
		}
		elseif ($this->input->get_post('resize_width') != '' AND $this->input->get_post('resize_width') != 0)
		{
			$config['width'] = $this->input->get_post('resize_width');
			$config['maintain_ratio'] = $this->input->get_post("constrain");
			$action = 'resize';

			if ($this->input->get_post('resize_height') != '')
			{
				$config['height'] = $this->input->get_post('resize_height');
			}
			else
			{
				$config['master_dim'] = 'width';
			}
		}
		elseif ($this->input->get_post('rotate') != '' AND $this->input->get_post('rotate') != 'none')
		{
			$action = 'rotate';
			$config['rotation_angle'] = $this->input->get_post('rotate');
		}
		else
		{
			if ($this->input->get_post('is_ajax'))
			{
				header('HTTP', true, 500);
				exit($this->lang->line('width_needed'));
			}
			else
			{
				show_error($this->lang->line('width_needed'));
			}
		}

		$config['image_library'] = $this->config->item('image_resize_protocol');
		$config['source_image'] = $file;
		$config['new_image'] = $file;

//		$config['dynamic_output'] = TRUE;

		$this->load->library('image_lib', $config);

		$errors = '';

		// Cropping and Resizing
		if ($action == 'resize')
		{
			if ( ! $this->image_lib->resize())
			{
		    	$errors = $this->image_lib->display_errors();
			}
		}
		elseif ($action == 'rotate')
		{

			if ( ! $this->image_lib->rotate())
			{
			    $errors = $this->image_lib->display_errors();
			}
		}
		else
		{
			if ( ! $this->image_lib->crop())
			{
			    $errors = $this->image_lib->display_errors();
			}
		}

		// Any reportable errors? If this is coming from ajax, just the error messages will suffice
		if ($errors != '')
		{
			if ($this->input->get_post('is_ajax'))
			{
				header('HTTP', true, 500);
				echo $errors;
				exit;
			}
			else
			{
				show_error($errors);
			}
		}

		$dimensions = $this->image_lib->get_image_properties('', TRUE);
		$this->image_lib->clear();

		// Rebuild thumb
		// @todo
		// $this->create_thumb(array('server_path'=>substr($file, 0, strrpos($file, DIRECTORY_SEPARATOR))), array('name'=>$image_reference));

		$url = BASE.AMP.'C=content_files'.AMP.'M=prep_edit_image'.AMP.'file='.rawurlencode($this->input->get_post('file')).AMP.'url_path='.rawurlencode($this->input->get_post('url_path'));

		if ($this->input->get_post('is_ajax'))
		{
			echo 'width="'.$dimensions['width'].'" height="'.$dimensions['height'].'" ';
			exit;
		}
		else
		{
			$this->functions->redirect($url);
		}
	}
}
// END CLASS

/* End of file content_files.php */
/* Location: ./system/expressionengine/controllers/cp/content_files.php */