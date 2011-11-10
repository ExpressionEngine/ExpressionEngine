<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine News and Stats Accessory
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Accessories
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class News_and_stats_acc {
	
	var $name			= 'News and Stats';
	var $id				= 'newsAndStats';
	var $version		= '1.0';
	var $description	= 'ExpressionEngine News and Stats';
	var $sections		= array();

	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->lang->loadfile('homepage');
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
		$this->sections['News'] = $this->_fetch_news();
		$this->sections[$this->EE->lang->line('site_statistics')] = $this->_fetch_stats();
		
		$this->EE->javascript->output('
			$("#newsAndStats").find("a.entryLink").click(function() {
				$(this).siblings(".fullEntry").toggle();
				return false;
			});
		');
	}

	// --------------------------------------------------------------------
	
	/**
	 * Fetch News
	 *
	 * @access	public
	 * @return	string
	 */
	function _fetch_news()
	{
		$ret = ''; 
		
		if ( ! file_exists(PATH_PI.'pi.magpie.php'))
		{
			return '';
		}
		
		if ( ! defined('MAGPIE_CACHE_AGE'))
		{
			define('MAGPIE_CACHE_AGE', 60*60*24*3); // set cache to 3 days			
		}
		
		if ( ! defined('MAGPIE_CACHE_DIR'))
		{
			define('MAGPIE_CACHE_DIR', APPPATH.'cache/magpie_cache/');			
		}
		
		if ( ! defined('MAGPIE_DEBUG'))
		{
			define('MAGPIE_DEBUG', 0);
		}

		if ( ! class_exists('Magpie'))
		{
			require PATH_PI.'pi.magpie.php';
		}

		$feed = fetch_rss('http://expressionengine.com/feeds/rss/cpnews/', 60*60*24*3); // set cache to 3 days

		$i = 0;

		if ( ! is_object($feed) OR count($feed->items) == 0)
		{
			return '';
		}
		else
		{
			// Load typography class
			$this->EE->load->library('typography');
			$this->EE->typography->initialize();

			$obj = new stdClass;

			for ($i = 0, $total = count($feed->items); $i < $total, $i < 3; $i++)
			{
				$title = $feed->items[$i]['title'];

				$date = $feed->items[$i]['pubdate'];
				$date = $this->EE->localize->set_human_time(strtotime(preg_replace(
					"/(20[10][0-9]\-[0-9]{2}\-[0-9]{2})T([0-9]{2}:[0-9]{2}:[0-9]{2})Z/", 
					'\\1 \\2 UTC',
					$date
				)));
				
				$content = $feed->items[$i]['description'];
				$link = $feed->items[$i]['link'];

				$content = $this->EE->typography->parse_type($content, 
												  		array(
																'text_format'   => 'xhtml',
																'html_format'   => 'all',
																'auto_links'    => 'y',
																'allow_img_url' => 'y'
																)
								 			);
				$ret .= "<div class='entry'>
							<a class='entryLink' href='{$link}'>{$title}</a>
							<div class='entryDate'>{$date}</div>
							<div class='fullEntry'>
								{$content}
							</div>
						</div>";
			}
	
			$qm = ($this->EE->config->item('force_query_string') == 'y') ? '' : '?';			
			$ret .= '<div><a onclick="window.open(this.href); return false;" href="'.$this->EE->functions->fetch_site_index().$qm.'URL=http://expressionengine.com/blog/'.'">'.$this->EE->lang->line('more_news').'</a></div>';
			
			return $ret;
		}
	}

	// --------------------------------------------------------------------
	
	/**
	 * Fetch Stats
	 *
	 * @access	public
	 * @return	string
	 */
	function _fetch_stats()
	{
		// default array for our "Values" data cells.  We'll just set the 'data' for each row
		// and save ourselves a bit of repeated code applying the class
		$values = array('data' => '', 'class' => 'values');
		
		$this->EE->load->library('table');
		$this->EE->load->helper(array('url', 'snippets'));
//		$this->EE->table->set_heading($this->EE->lang->line('site_statistics'), array('data' => $this->EE->lang->line('value'), 'class' => 'values'));
		
		if ($this->EE->session->userdata['group_id'] == 1)
		{
			$values['data'] = ($this->EE->config->item('is_system_on') == 'y') ? '<strong>'.$this->EE->lang->line('online').'</strong>' : '<strong>'.$this->EE->lang->line('offline').'</strong>';
			$this->EE->table->add_row($this->EE->lang->line('system_status'), $values);

			if ($this->EE->config->item('multiple_sites_enabled') == 'y')
			{
				$values['data'] = ($this->EE->config->item('is_site_on') == 'y' && $this->EE->config->item('is_system_on') == 'y') ? '<strong>'.$this->EE->lang->line('online').'</strong>' : '<strong>'.$this->EE->lang->line('offline').'</strong>';
				$this->EE->table->add_row($this->EE->lang->line('site_status'), $values);
			}
			
			$this->EE->lang->loadfile('modules');
			$values['data'] = APP_VER;
			$this->EE->table->add_row($this->EE->lang->line('module_version'), $values);
		}
		
		// total entries and comments
		$this->EE->db->where(array('site_id' => $this->EE->config->item('site_id')));
		$query = $this->EE->db->get('stats');
		
		$row = $query->row();
		
		$values['data'] = $row->total_entries;
		$this->EE->table->add_row($this->EE->lang->line('total_entries'), $values);

		$values['data'] = $row->total_comments;
		$this->EE->table->add_row($this->EE->lang->line('total_comments'), $values);
		
		// total template hits
		$this->EE->db->select_sum('templates.hits', 'total');
		$this->EE->db->from(array('templates'));
		$query = $this->EE->db->get();
		
		$row = $query->row();
		$values['data'] = $row->total;
		$this->EE->table->add_row($this->EE->lang->line('total_hits'), $values);

		// member stats
		if ($this->EE->session->userdata('group_id') == 1)
		{
			// total members
			$values['data'] = $this->EE->db->count_all_results('members');
			$this->EE->table->add_row($this->EE->lang->line('total_members'), $values);

			// total members waiting validation
			$values['data'] = 0;

			if ($this->EE->config->item('req_mbr_activation') == 'manual')
			{
				$this->EE->db->where('group_id', '4');
				$values['data'] = $this->EE->db->count_all_results('members');
			}
			
			$this->EE->load->helper(array('url', 'snippets'));
			
			$l = anchor(BASE.AMP.'C=members&M=member_validation',
				   required(
						$this->EE->lang->line('total_validating_members')
					)
				);
			
			$link = ($values['data'] > 0) ? $l : $this->EE->lang->line('total_validating_members');

			$this->EE->table->add_row($link, $values);
		}

		// total comments waiting validation
		if ($this->EE->cp->allowed_group('can_moderate_comments'))
		{
			$this->EE->load->model('addons_model');

			// is the comment module installed?
			if ($this->EE->addons_model->module_installed('comments'))
			{
				$values['data'] = 0;
			
				$this->EE->db->where(array('status' => 'p', 'site_id' => $this->EE->config->item('site_id')));
				$values['data'] = $this->EE->db->count_all_results('comments');
				
				$l = anchor(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=index'.AMP.'status=p',
					   required(
							$this->EE->lang->line('total_validating_comments')
						)
					);

				$link = ($values['data'] > 0) ? $l : $this->EE->lang->line('total_validating_comments');
				$this->EE->table->add_row($link, $values);
			}
		}
		
		$tmpl = array(
						'table_open'		=> '<table border="0" cellpadding="0" cellspacing="0">',
						);
						
		$this->EE->table->set_template($tmpl);
		$ret = $this->EE->table->generate();
		$this->EE->table->clear();
		
		return $ret;
	}

	// --------------------------------------------------------------------
	
}
// END CLASS

/* End of file acc.news_and_stats.php */
/* Location: ./system/expressionengine/accessories/acc.news_and_stats.php */