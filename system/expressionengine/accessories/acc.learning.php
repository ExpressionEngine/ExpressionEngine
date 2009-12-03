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
 * ExpressionEngine Learning Accessory
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Accessories
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
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
	function Learning_acc()
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
								$this->EE->lang->line('videos')	=> '<ul class="videos">
																<li><a href="#">'.$this->EE->lang->line('installing_ee').'</a></li>
																<li><a href="#">'.$this->EE->lang->line('introduction_to_templates').'</a></li>
																<li><a href="#">'.$this->EE->lang->line('channel_custom_fields').'</a></li>
																<li><a href="#">'.$this->EE->lang->line('channel_template_relationship').'</a></li>
															</ul>',
								$this->EE->lang->line('community_tutorials')	 => 	'<ul>
																<li><a href="http://www.boyink.com/splaat/comments/building-an-expressionengine-site-chapter-1/">'.$this->EE->lang->line('building_ee_site_01').'</a></li>
																<li><a href="http://www.boyink.com/splaat/comments/designing-an-expressionengine-architecture/">'.$this->EE->lang->line('designing_ee_architecture').'</a></li>
																<li><a href="http://www.eehowto.com/howto/info/troubleshooting-problems-with-file-uploads/">'.$this->EE->lang->line('troubleshooting_file_uploads').'</a></li>
																<li><a href="#">'.$this->EE->lang->line('ee_cp_overview').'</a></li>
																<li><a href="http://loweblog.com/freelance/article/ee-search-bookmarklet/">'.$this->EE->lang->line('ee_seach_bookmarklet').'</a></li>
															</ul>',
								$this->EE->lang->line('support') => '<ul>
																<li><a href="http://expressionengine.com/docs/">'.$this->EE->lang->line('documentation').'</a></li>
																<li><a href="http://expressionengine.com/forums/">'.$this->EE->lang->line('support_forums').'</a></li>
																<li><a href="http://expressionengine.com/wiki/">'.$this->EE->lang->line('wiki').'</a></li>
															</ul>'
							);
	}

	// --------------------------------------------------------------------

}
// END CLASS

/* End of file acc.learning.php */
/* Location: ./system/expressionengine/accessories/acc.learning.php */