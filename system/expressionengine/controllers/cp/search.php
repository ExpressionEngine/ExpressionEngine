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
class Search extends CI_Controller {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		$this->lang->loadfile('admin');
		$this->load->library('Cp_search');
	}
	
	// --------------------------------------------------------------------

	/**
	 * Index function
	 *
	 * @access	public
	 * @return	void
	 */	
	function index($message = '')
	{
		$this->load->helper('html');
		$this->load->helper('search');
		
		$vars['cp_page_title'] = $this->lang->line('search_results');
		$this->cp->set_variable('cp_page_title', $vars['cp_page_title']);
		
		// Saved search
		if ($search = $this->input->get('saved'))
		{
			$search = base64_decode(rawurldecode($search));
		}
		else
		{
			$search = $this->input->get_post('cp_search_keywords', TRUE);
		}

		if ( ! $this->cp_search->_check_index())
		{
			// Save the search
			$search = rawurlencode(base64_encode($search));
			
			if ($this->input->get('ajax'))
			{
				// Force a js redirect
				$url = str_replace('&amp;', '&', BASE).'&C=search&M=build_index&saved='.$search;
				echo '<script type="text/javascript">window.location="'.$url.'";</script>';
				exit;
			}

			// Degrade 'nicely'
			$this->functions->redirect(BASE.AMP.'C=search'.AMP.'M=build_index'.AMP.'saved='.$search);
		}
		
		
		$vars['keywords'] = sanitize_search_terms($search);
		$vars['can_rebuild'] = $this->cp->allowed_group('can_access_utilities');
		$vars['search_data'] = $this->cp_search->generate_results($search);
		$vars['num_rows'] = count($vars['search_data']);

		if ($this->input->get('ajax'))
		{
			echo $this->load->view('search/sidebar', $vars, TRUE);
			exit;
		}
		
		$this->javascript->compile();
		$this->load->view('search/results', $vars);
	}

	// --------------------------------------------------------------------

	/**
	 * Build Index
	 * 
	 * Shows a 'working' page and orchestrates the rebuilding process
	 *
	 * @access	public
	 * @return	mixed
	 */
	function build_index()
	{
		// Did they specify a language
		$language = $this->input->get('language');
		$language = $language ? $language : $this->config->item('language');
		
		// Show an intermediate page so they don't refresh and make sure we keep any saved searches
		$flag = $this->input->get('working');
		$saved = $this->input->get('saved') ? AMP.'saved='.$this->input->get('saved') : '';
		
		if ( ! $flag)
		{
			$vars['cp_page_title'] = 'Rebuilding Index';
			$this->cp->set_variable('cp_page_title', $vars['cp_page_title']);
			
			// Meta refresh to start the process
			$meta = '<meta http-equiv="refresh" content="1;url='.BASE.AMP.'C=search'.AMP.'M=build_index'.AMP.'language='.$language.AMP.'working=y'.$saved.'" />';
			$this->cp->add_to_head($meta);
			$this->load->view('search/rebuild', $vars);
		}
		elseif ($flag == 'y')
		{
			// Clear all keywords for this language
			$this->db->where('language', $language);
			$this->db->delete('cp_search_index');
			
			// And we're on our way
			$this->cp_search->_build_index($language);			
			$this->functions->redirect(BASE.AMP.'C=search'.AMP.'M=build_index'.AMP.'working=n'.$saved);
		}
		else
		{
			if ($saved)
			{
				$this->functions->redirect(BASE.AMP.'C=search'.$saved);
			}
			
			$this->functions->redirect(BASE.AMP.'C=homepage');
		}
	}

}

/* End of file search.php */
/* Location: ./system/expressionengine/controllers/cp/search.php */