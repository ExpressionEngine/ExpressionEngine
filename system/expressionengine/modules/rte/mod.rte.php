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
 * ExpressionEngine Rich Text Editor Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		Aaron Gustafson
 * @link		http://easy-designs.net
 */
class Rte {

	public $return_data	= '';
	private $module 	= 'rte';
	
	/**
	  * Constructor
	  */
	public function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}
	
	public function embed( $selector='.rte', $toolset_id=FALSE )
	{
		$this->EE->load->library('javascript');
		
		# get the selector
		if ( $temp = $this->EE->TMPL->fetch_param('selector') ) $selector = $temp;
		# toolset id
		if ( $temp = $this->EE->TMPL->fetch_param('toolset_id') ) $toolset_id = $temp;
		
		# include the module
		include_once( APPPATH.'modules/'.$this->module.'/'.'mcp.'.$this->module.'.php' );
		$class_name	= ucfirst($this->module).'_mcp';
		$RTE		= new $class_name();
		
		$this->return_data = '<script>' .
								str_replace( '.rte', $selector, $RTE->build_js($toolset_id) ) .
							 '</script>';
		
		return $this->return_data;
	}

}
// END CLASS

/* End of file mod.rte.php */
/* Location: ./system/expressionengine/modules/rte/mod.rte.php */