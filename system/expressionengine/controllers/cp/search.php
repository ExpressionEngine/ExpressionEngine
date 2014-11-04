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
 * ExpressionEngine CP Home Page Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Search extends CP_Controller {

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();

		ee()->lang->loadfile('admin');
		ee()->load->library('Cp_search');
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
		ee()->load->helper('html');
		ee()->load->helper('search');

		$vars['cp_page_title'] = lang('search_results');
		ee()->view->cp_page_title = $vars['cp_page_title'];

		// Saved search
		if ($search = ee()->input->get('saved'))
		{
			$search = base64_decode(rawurldecode($search));
		}
		else
		{
			$search = ee()->input->get_post('cp_search_keywords', TRUE);
		}

		if ( ! ee()->cp_search->_check_index())
		{
			// Save the search
			$search = rawurlencode(base64_encode($search));

			if (AJAX_REQUEST)
			{
				// Force a js redirect
				$url = cp_url('search/build_index', array(
					'saved' => $search
				));
				$url = str_replace('&amp;', '&', $url);
				echo '<script type="text/javascript">window.location="'.$url.'";</script>';
				exit;
			}

			// Degrade 'nicely'
			ee()->functions->redirect(cp_url('search/build_index', array(
					'saved' => $search
				)));
		}


		$vars['keywords'] = sanitize_search_terms($search);
		$vars['can_rebuild'] = ee()->cp->allowed_group('can_access_utilities');
		$vars['search_data'] = ee()->cp_search->generate_results($search);
		$vars['num_rows'] = count($vars['search_data']);

		if (AJAX_REQUEST)
		{
			echo ee()->load->view('search/sidebar', $vars, TRUE);
			exit;
		}

		ee()->cp->render('search/results', $vars);
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
		$language = ee()->input->get('language') ?: ee()->config->item('deft_lang');

		// Show an intermediate page so they don't refresh and make sure we keep any saved searches
		$working = ee()->input->get('working');
		$saved   = ee()->input->get('saved') ?: '';

		if ( ! $working)
		{
			$vars['cp_page_title'] = 'Rebuilding Index';
			ee()->view->cp_page_title = $vars['cp_page_title'];

			// Meta refresh to start the process
			$refresh_url = cp_url('search/build_index', array(
				'language' => $language,
				'working'  => 'y',
				'saved'    => $saved
			));
			$meta = '<meta http-equiv="refresh" content="3;url='.$refresh_url.'" />';
			ee()->cp->add_to_head($meta);
			ee()->cp->render('search/rebuild', $vars);
		}
		elseif ($working == 'y')
		{
			// Clear all keywords for this language
			ee()->db->where('language', $language);
			ee()->db->delete('cp_search_index');

			// And we're on our way
			ee()->cp_search->_build_index($language);
			ee()->functions->redirect(
				cp_url('search/build_index', array(
					'working' => 'n',
					'saved'   => $saved
				))
			);
		}
		else
		{
			if ( ! empty($saved))
			{
				ee()->functions->redirect(cp_url('search', array(
					'saved' => $saved
				)));
			}

			ee()->functions->redirect(cp_url('homepage'));
		}
	}

}

/* End of file search.php */
/* Location: ./system/expressionengine/controllers/cp/search.php */
