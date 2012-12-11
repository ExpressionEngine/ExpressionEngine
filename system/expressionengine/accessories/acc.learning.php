<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Learning Accessory
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Accessories
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Learning_acc {
	
	var $name			= 'Learning EE';
	var $id				= 'learningEE';
	var $version		= '1.0';
	var $description	= 'Educational Resources for ExpressionEngine';
	var $sections		= array();
	
	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Set Sections
	 *
	 * Set content for the accessory
	 *
	 * @access	public
	 * @return	void
	 */
	function set_sections()
	{
		$this->sections = array(
			
			$this->EE->lang->line('community_tutorials')	 => 	'<ul>
						<li><a href="'.$this->EE->cp->masked_url('http://train-ee.com/').'" title="'.$this->EE->lang->line('train_ee').'">'.$this->EE->lang->line('train_ee').'</a></li>
						<li><a href="'.$this->EE->cp->masked_url('http://www.eescreencasts.com/').'" title="'.$this->EE->lang->line('ee_screencasts').'">'.$this->EE->lang->line('ee_screencasts').'</a></li>
						<li><a href="'.$this->EE->cp->masked_url('http://loweblog.com/freelance/article/ee-search-bookmarklet/').'" title="'.$this->EE->lang->line('ee_seach_bookmarklet').'">'.$this->EE->lang->line('ee_seach_bookmarklet').'</a></li>
					</ul>'
						,
						
			$this->EE->lang->line('community_resources') => '<ul>
						<li><a href="'.$this->EE->cp->masked_url('http://eeinsider.com/').'" title="'.$this->EE->lang->line('ee_insider').'">'.$this->EE->lang->line('ee_insider').'</a></li>
						<li><a href="'.$this->EE->cp->masked_url('http://devot-ee.com/').'" title="'.$this->EE->lang->line('devot_ee').'">'.$this->EE->lang->line('devot_ee').'</a></li>
						<li><a href="'.$this->EE->cp->masked_url('http://ee-podcast.com/').'" title="'.$this->EE->lang->line('ee_podcast').'">'.$this->EE->lang->line('ee_podcast').'</a></li>
						<li><a href="'.$this->EE->cp->masked_url('http://show-ee.com/').'" title="Show-EE">Show-EE</a></li>
					</ul>
			',
			$this->EE->lang->line('support') => '<ul>
						<li><a href="'.$this->EE->cp->masked_url($this->EE->config->item('doc_url')).'" title="'.$this->EE->lang->line('documentation').'">'.$this->EE->lang->line('documentation').'</a></li>
						<li><a href="'.$this->EE->cp->masked_url('http://ellislab.com/forums/').'" title="'.$this->EE->lang->line('support_forums').'">'.$this->EE->lang->line('support_forums').'</a></li>
					</ul>'			
		);
	}

	// --------------------------------------------------------------------

}
// END CLASS

/* End of file acc.learning.php */
/* Location: ./system/expressionengine/accessories/acc.learning.php */