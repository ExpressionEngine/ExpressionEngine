<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.4
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Pagination Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Pagination
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */

class EE_Pagination extends CI_Pagination {
	
	var $paginate				= FALSE;
	var $field_pagination		= FALSE;
	var $paginate_data			= '';
	var $pagination_links		= '';
	var $basepath				= '';
	var $page_next				= '';
	var $page_previous			= '';
	var $total_pages			= 1;
	var $multi_fields			= array();
	var $pager_sql				= '';
	
	
	public function __construct()
	{
		$this->EE =& get_instance();
		parent::__construct();
	}
	

}

class Pagination_object {
	public $paginate			= FALSE;
	public $paginate_data		= '';
	public $field_pagination	= FALSE;
	public $multi_fields		= '';
	public $total_pages			= 1;
	public $current_page		= 1;
	public $offset				= '';
	public $page_next			= '';
	public $page_previous		= '';
	public $pagination_links	= '';
	public $pagination_array	= array();
	public $total_rows			= 0;
	public $per_page			= 0;
	public $basepath			= '';
	private $calling_class 		= '';
	
	public function __construct($calling_class)
	{
		$this->calling_class = $calling_class;
	}
}

// END Pagination class

/* End of file Pagination.php */
/* Location: ./system/expressionengine/libraries/Pagination.php */