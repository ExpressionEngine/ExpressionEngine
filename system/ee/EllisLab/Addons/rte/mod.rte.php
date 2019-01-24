<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Rich Text Editor Module
 */
class Rte {

	public $return_data	= '';

	/**
	 * Outputs the RTE's toolset JS. Called via an ACT.
	 *
	 * @access	public
	 * @return	mixed 	The JS
	 */
	public function get_js()
	{
		// Selector is required
		if ( ! $selector = ee()->input->get('selector', TRUE))
		{
			return;
		}

		$toolset_id 	= (int)ee()->input->get('toolset_id');
		$include 		= explode(',', ee()->input->get('include', TRUE));

		// all allowed includes default to FALSE
		foreach (array('jquery', 'jquery_ui') as $allowed)
		{
			$includes[$allowed] = in_array($allowed, $include);
		}

		// try to be nice and swap double quotes for single
		$selector = urldecode(str_replace('"', "'", $selector));

		ee()->load->library('rte_lib');
		$js = ee()->rte_lib->build_js($toolset_id, $selector, $includes, REQ == 'CP');

		ee()->output->enable_profiler(FALSE);
		ee()->output->out_type = 'js';
		ee()->output->set_header("Content-Type: text/javascript");
		ee()->output->set_output($js);
	}

	/**
	 * Returns the action URL for the RTE JavaScript
	 *
	 * @access	public
	 * @return	string 	The ACT URL
	 */
	public function script_url()
	{
		$toolset_id = (int)ee()->TMPL->fetch_param('toolset_id', 0);
		$selector 	= ee()->TMPL->fetch_param('selector', '.rte');
		$includes	= array();

		$url = ee()->functions->fetch_site_index().QUERY_MARKER
				.'ACT='.ee()->functions->fetch_action_id('Rte', 'get_js')
				.'&toolset_id='.$toolset_id
				.'&selector='.urlencode($selector);

		if (ee()->TMPL->fetch_param('include_jquery') != 'no')
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

// EOF
