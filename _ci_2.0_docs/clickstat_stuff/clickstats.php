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
 * ExpressionEngine CP Clickstats Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Clickstats extends Controller {

	function Clickstats()
	{
		parent::Controller();

		// Does the "core" class exist?  Normally it's initialized
		// automatically via the autoload.php file.  If it doesn't
		// exist it means there's a problem.
		if ( ! isset($this->core) OR ! is_object($this->core))
		{
			show_error('The ExpressionEngine Core was not initialized.  Please make sure your autoloader is correctly set up.');
		}

		// Clickstats page has no descrete permissions, so let's make sure they have cp access and be done
		if ( ! $this->cp->allowed_group('can_access_cp'))
		{
			show_error($this->lang->line('unauthorized_access'));
		}

		// Everything this controller does should be behind the scenes
		// Don't need this wrecking AJAX output by accident
		$this->output->enable_profiler(FALSE);
	}

	// --------------------------------------------------------------------

	function index()
	{
		// Nothing here, just in case this controller is visited... which is shouldn't be.
		// Let's take this opportunity to have fun with some poetry...
		//
		// Haikus Are Easy,
		// But Sometimes They Don't Make Any Sense
		// Refrigerator
	}

	// --------------------------------------------------------------------

	function save_click()
	{
		$clickstats = array(
			'unique_id'	=> xss_clean($this->session->userdata('session_id')),
			'class'		=> $this->input->post('class'),
			'method'	=> $this->input->post('method'),
			'x'			=> (int) $this->input->post('x')-10, // account for size of pointer by removing 10 px
			'y'			=> (int) $this->input->post('y')-10, // account for size of pointer by removing 10 px
			'sub_nav'	=> (int) $this->input->post('sub_nav'),
			'user_type'	=> (int) $this->input->post('user_type')
		);

		// We aren't using a model here since clickstats is a currently a one off project for the beta
		$this->db->insert('clickstats', $clickstats);
	}

	// --------------------------------------------------------------------

	function show_clicks()
	{
		// sleep(2); // fake a slow server for now @todo: remove

		$this->db->where('class', $this->input->post('class'));
		$this->db->where('method', $this->input->post('method'));
		$this->db->select('x, y, sub_nav');
		$clicks = $this->db->get('clickstats');

		$r = '';

		foreach ($clicks->result() as $click)
		{
			if ($click->sub_nav)
			{
				$r .= '<div class="breadcrumb" style="left:'.$click->x.'px;top:'.$click->y.'px"></div>';
			}
			else
			{
				$r .= '<div style="left:'.$click->x.'px;top:'.$click->y.'px"></div>';
			}
		}

		echo $r;
	}


}
// END CLASS

/* End of file clickstats.php */
/* Location: ./system/expressionengine/controllers/cp/clickstats.php */