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
		$this->EE->load->model(array('rte_toolset_model','rte_tool_model'));
		
		# get the selector
		if ( $temp = $this->EE->TMPL->fetch_param('selector') ) $selector = $temp;
		# toolset id
		if ( $temp = $this->EE->TMPL->fetch_param('toolset_id') ) $toolset_id = $temp;
		
		# get the tools
		if ( ! $toolset_id )
		{
			$toolset_id = $this->EE->rte_toolset_model->get_member_toolset();
		}
		$js = '';
		
		# make sure we should load the JS
		if ( $toolset_id &&
		     $this->EE->config->item('rte_enabled') == 'y' )
		{
			
			# load the tools
			$bits	= array(
				'globals'		=> array(
					'rte.update_event' => 'WysiHat-editor:change'
				),
				'libraries'		=> array(
					'ui'		=> array(
						'core', 'widget'
					),
					'plugin'	=> array(
						'wysihat'
					)
				),
				'styles'		=> '',
				'definitions'	=> ''
			);
			$tools	= $this->EE->rte_toolset_model->get_tools( $toolset_id );
			foreach ( $tools as $tool_id )
			{
				$tool = $this->EE->rte_tool_model->get_tool($tool_id);
				
				# load the globals
				if ( count( $tool['globals'] ) )
				{
					$bits['globals'] = array_merge( $bits['globals'], $tool['globals'] );
				}
				
				# load any libraries we need
				if ( count( $tool['libraries'] ) )
				{
					$bits['libraries'] = array_merge_recursive( $bits['libraries'], $tool['libraries'] );
				}
				
				# add any styles we need
				if ( ! empty( $tool['styles'] ) )
				{
					$bits['styles'] .= $tool['styles'];
				}
				
				# load in the definition
				if ( ! empty( $tool['definition'] ) )
				{
					$bits['definitions'] .= $tool['definition'];
				}
			}
			
			# kick off the JS
			$jquery = $this->EE->config->item('theme_folder_url') . 'javascript/' .
					  ( $this->EE->config->item('use_compressed_js') == 'n' ? 'src' : 'compressed' ) .
					  '/jquery/jquery.js';
			$uicss	= $this->EE->config->item('theme_folder_url') . 'javascript/' .
					  ( $this->EE->config->item('use_compressed_js') == 'n' ? 'src' : 'compressed' ) .
					  '/jquery/themes/default/ui.all.css';
			$rtecss	= $this->EE->config->item('theme_folder_url') . 'cp_themes/default/css/rte.css';
			$js .= '
			<script>
				(function(){
					// make sure we have jQuery
					var interval = null;
					if ( typeof jQuery === "undefined" )
					{
						var j = document.createElement("script");
						j.setAttribute("src","' . $jquery . '");
						document.getElementsByTagName("head")[0].appendChild(j);
						
						interval = setInterval( loadRTE, 10);
					}
					else
					{
						loadRTE();
					}
					
					function loadRTE()
					{
						// make sure jQuery is loaded
						if ( typeof jQuery === "undefined" ){ return; }
						clearInterval( interval );
						
						var $ = jQuery;
						
						// library code
						' . $this->_load_js_files( $bits['libraries'] ) . '

						// load in the styles (including base jQuery UI and RTE)
						$("<link rel=\"stylesheet\" href=\"' . $uicss . '\"/>")
							.add( $("<link rel=\"stylesheet\" href=\"' . $rtecss . '\"/>") )
							.add( $("<style>' . preg_replace( '/\\s+/', ' ', $bits['styles'] ) . '</style>") )
							.appendTo("head");

						// globals
						' . $this->_set_globals( $bits['globals'] ) . '

						$("' . $selector . '").each(function(){
							var
							$field	= $(this),
							$parent	= $field.parent(),

							// set up the editor
							$editor	= WysiHat.Editor.attach($field),

							// establish the toolbar
							toolbar	= new WysiHat.Toolbar();

							toolbar.initialize($editor); 

							// tools
							' . $bits['definitions'] . '

						});
					}
				})();
			</script>';
		}
		
		$this->return_data = $js;
		return $this->return_data;
	}
	
	/**
	 * Loads JS library files
	 * 
	 * Note: This is partially borrowed from the combo loader
	 * 
	 * @access	private
	 * @param	array
	 * @return	string
	 */
	private function _load_js_files( $load=array() )
	{
		$folder = $this->EE->config->item('use_compressed_js') == 'n' ? 'src' : 'compressed';
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
								$file[] = $this->EE->security->sanitize_filename($part);
							}
						}

						$file = implode('/', $file);
					}
					else
					{
						$file = $this->EE->security->sanitize_filename($file);
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

	/**
	 * Manage the assignment of global JS
	 * 
	 * @access	private
	 * @param	array
	 * @return	string
	 */
	private function _set_globals( $globals=array() )
	{
		$js = '';
		
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
		return $js;
	}

}
// END CLASS

/* End of file mod.rte.php */
/* Location: ./system/expressionengine/modules/rte/mod.rte.php */