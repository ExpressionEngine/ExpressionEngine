<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.5
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Rich Text Editor Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Rte {

	public $return_data	= '';
	
	/**
	  * Constructor
	  */
	public function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		if ($this->EE->config->item('rte_enabled') == 'n')
		{
			return;
		}
	}
	
	// ------------------------------------------------------------------------

	/**
	 * Outputs the RTE's toolset JS. Called via an ACT.
	 * 
	 * @access	public
	 * @return	mixed 	The JS
	 */
	public function get_js()
	{
		// Selector is required
		if ( ! $selector = $this->EE->input->get('selector', TRUE))
		{
			return;
		}

		$toolset_id 	= (int)$this->EE->input->get('toolset_id');
		$include 		= explode(',', $this->EE->input->get('include', TRUE));
		
		// all allowed includes default to FALSE
		foreach (array('jquery', 'jquery_ui') as $allowed)
		{
			$includes[$allowed] = in_array($allowed, $include);
		}

		// try to be nice and swap double quotes for single
		$selector = urldecode(str_replace('"', "'", $selector));

		$this->EE->load->library('rte_lib');
		$js = $this->EE->rte_lib->build_js($toolset_id, $selector, $includes);

		$this->EE->output->enable_profiler(FALSE);
		$this->EE->output->out_type = 'js';
		$this->EE->output->set_header("Content-Type: text/javascript");
		$this->EE->output->set_output($js);
	}

	// ------------------------------------------------------------------------

	/**
	 * Returns the action URL for the RTE JavaScript
	 *
	 * @access	public
	 * @return	string 	The ACT URL
	 */
	public function script_url()
	{
		$toolset_id = (int)$this->EE->TMPL->fetch_param('toolset_id', 0);
		$selector 	= $this->EE->TMPL->fetch_param('selector', '.rte');
		$includes	= array();

		$url = $this->EE->functions->fetch_site_index().QUERY_MARKER
				.'ACT='.$this->EE->functions->fetch_action_id('Rte', 'get_js')
				.'&toolset_id='.$toolset_id
				.'&selector='.urlencode($selector);

		if ($this->EE->TMPL->fetch_param('include_jquery') != 'no')
		{
			$includes[] = 'jquery';
			$includes[] = 'jquery_ui';
		}

		if (count($includes))
		{
			$url .= '&include='.urlencode(implode(',', $includes));
		}

		return $url;
	}
}
// END CLASS

/* End of file mod.rte.php */
/* Location: ./system/expressionengine/modules/rte/mod.rte.php */