<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
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
class Rte_tool_model extends CI_Model {

	private $tools;
	
	/**
	 * Constructor
	 * 
	 * @access	public
	 */
	public function __construct()
	{
		$this->_load_tools_into_db();
	}
	
	/**
	 * Gets all RTE tools
	 * 
	 * @access	public
	 * @param	bool
	 * @return	array
	 */
	public function get_all( $list=FALSE )
	{
		# get the tools from the DB
		$results = $this->db
						->get('rte_tools')
						->result_array();
		# decide what to return
		return $list ? $this->_make_list( $results ) : $results;
	}
	
	/**
	 * Gets all enabled RTE tools
	 * 
	 * @access	public
	 * @param	bool
	 * @return	array
	 */
	public function get_available( $list=FALSE )
	{
		# get the tools from the DB
		$results = $this->db
						->get_where( 'rte_tools', array('enabled' => 'y') )
						->result_array();
		# decide what to return
		return $list ? $this->_make_list( $results ) : $results;
	}
	
	/**
	 * Gets the tool IDs for the supplied tools
	 * 
	 * @access	public
	 * @param	array
	 * @return	array
	 */
	public function get_tool_ids( $tools=array() )
	{
		$tool_ids = array();
		# make sure we have tools
		if ( count( $tools ) )
		{
			# make sure the class name is correct
			foreach ( $tools as &$tool )
			{
				$tool = ucfirst( strtolower( $tool ) ).'_rte';
			}
			# get the tools
			$results = $this->db
							->select(array('rte_tool_id','class'))
							->where_in('class', $tools)
							->get('rte_tools')
							->result_array();
			# extract the ids
			foreach ( $results as $row )
			{
				# set the indexes according to the original array
				$tool_ids[array_search($row['class'],$tools)] = $row['rte_tool_id'];
			}
			# sort them appropriately
			ksort($tool_ids, SORT_NUMERIC);
		}
		# return the IDs
		return $tool_ids;
	}
	
	/**
	 * Gets the JS for a specific tool
	 * 
	 * @access	public
	 * @param	number
	 * @return	string
	 */
	public function get_tool_js( $tool_id = FALSE )
	{
		# get the tool
		$results = $this->db->get_where(
			'rte_tools',
			array(
				'rte_tool_id'	=> $tool_id,
				'enabled'		=> 'y'
			)
		);
		
		if ( $results->num_rows() > 0 )
		{
			$tool		= $results->row();
			$tool_name	= strtolower( str_replace( ' ', '_', $tool->name ) );
			$tool_class	= ucfirst( $tool_name ).'_rte';
			
			$globals	= array();
			$styles		= '';
			$scripts	= '';
			$tools		= '';
			
			# find the RTE tool file
			foreach ( array(PATH_RTE, PATH_THIRD) as $tmp_path )
			{
				$file = $tmp_path.$tool_name.'/rte.'.$tool_name.'.php';
				if ( file_exists($file) )
				{
					# load it in, instantiate the tool & add the definition
					require_once( $file );
					$TOOL = new $tool_class();
					
					# Styles?
					if ( $TOOL->styles )
					{
						$styles .= $TOOL->styles;
					}
					
					# Globals?
					if ( count( $TOOL->globals ) )
					{
						$globals = array_merge( $globals, $TOOL->globals );
					}
					
					# Scripts?
					if ( count( $TOOL->scripts ) )
					{
						$scripts .= $this->_load_js_files( $TOOL->scripts );
					}
					
					# get the tool definition
					$tools .= $TOOL->definition();

					break;
				}
			}
		}
		
		# compile it all
		$js	= '';
		
		if ( count( $globals ) )
		{
			$js .= 'if ( typeof EE === "undefined" ){ EE = {}; }';
			foreach ( $globals as $key => $val )
			{
				$parts	= explode( '.', $key );
				$i		= 0;
				$length = count( $parts ) - 1;
				while ( $i < $length )
				{
					# prefix
					$j		= 0;
					$prefix	= '';
					while ( $j < $i ){ $prefix .= $parts[$j++]; }
					$var = 'EE.' . ( ! empty($prefix) ? $prefix . '.' : '' ) . $parts[$i++];
					$js .= 'if ( typeof ' . $var . ' === "undefined" ){ ' . $var . ' = {}; }';
				}
				$js .= "EE.{$key} = '{$val}';\r\n";
			}
		}
		
		$js .= '$("<style>' . preg_replace( '/\\s+/', ' ', $styles ) . '</style>").appendTo("head");';
		
		$js .= $scripts;
		$js .= $tools;
		
		return $js;
	}

	/**
	 * Gets the JS for a specific tool
	 * 
	 * @access	public
	 * @param	array
	 * @param	number
	 * @return	number
	 */
	public function save( $tool=array(), $tool_id=FALSE )
	{
		# update or insert?
		$sql = $tool_id	? $this->db->update_string( 'rte_tools', $tool, array( 'rte_tool_id' => $tool_id ) )
						: $this->db->insert_string( 'rte_tools', $tool );
		# run it
		$this->db->query( $sql );
		# return the affected rows
		return $this->db->affected_rows();
	}
	
	/**
	 * Make the results array into an <option>-compatible list
	 * 
	 * @access	private
	 * @param	array
	 * @return	array
	 */
	private function _make_list( $result )
	{
		$return = array();
		
		foreach ( $result as $r )
		{
			$return[$r['rte_tool_id']] = $r['name'];
		}
		
		return $return;
	}
	
	/**
	 * Load tools into the DB
	 * 
	 * @access	private
	 * @param	array
	 * @return	array
	 */
	private function _load_tools_into_db()
	{
		$this->load->library('addons');
		
		# get the file list and the installed tools list
		$files		= $this->addons->get_files('rte_tools');
		$installed	= $this->addons->get_installed('rte_tools');
		$classes	= array();
		
		# add new tools
		foreach ( $files as $package => $details )
		{
			$classes[] = $details['class'];
			if ( ! isset($installed[$package]) )
			{
				# make a record of the add-on in the DB
				$this->db->insert(
					'rte_tools',
					array(
						'name'		=> $details['name'],
						'class'		=> $details['class'],
						'enabled'	=> 'y'
					)
				);
			}
		}
		
		# cleanup removed tools
		$this->db
			->where_not_in( 'class', $classes )
			->delete('rte_tools');
	}
	
	/**
	 * Loads JS library files
	 * 
	 * Note: This is partially borrowed from the combo loader
	 * 
	 * @access	private
	 * @param	array
	 * @return	array
	 */
	private function _load_js_files( $load=array() )
	{
		$folder = $this->config->item('use_compressed_js') == 'n' ? 'src' : 'compressed';
		if ( ! defined('PATH_JQUERY'))
		{
			define('PATH_JQUERY', PATH_THEMES.'javascript/'.$folder.'/jquery/');
		}
		$types	= array(
			'effect'	=> PATH_JQUERY.'ui/jquery.effects.',
			'ui'		=> PATH_JQUERY.'ui/jquery.ui.',
			'plugin'	=> PATH_JQUERY.'plugins/',
			'file'		=> PATH_THEMES.'javascript/'.$folder.'/',
			'package'	=> PATH_THIRD,
			'fp_module'	=> PATH_MOD
		);
		
		$contents = '';
		
		foreach ( $types as $type => $path )
		{
			if ( isset( $load[$type] ) )
			{
				$files = $load[$type];
				if ( ! is_array( $files ) )
				{
					$files = array( $files );
				}
				foreach ( $files as $file )
				{
					if ( $type == 'package' OR $type == 'fp_module' )
					{
						$file = $file.'/javascript/'.$file;
					}
					elseif ( $type == 'file' )
					{
						$parts = explode('/', $file);
						$file = array();

						foreach ($parts as $part)
						{
							if ($part != '..')
							{
								$file[] = $this->security->sanitize_filename($part);
							}
						}

						$file = implode('/', $file);
					}
					else
					{
						$file = $this->security->sanitize_filename($file);
					}

					$file = $path.$file.'.js';
					if (file_exists($file))
					{
						$contents .= file_get_contents($file)."\n\n";
					}
				}
			}
		}
		return $contents;
	}

}
// END CLASS

/* End of file rte_toolset_model.php */
/* Location: ./system/expressionengine/modules/rte/models/rte_tool_model.php */