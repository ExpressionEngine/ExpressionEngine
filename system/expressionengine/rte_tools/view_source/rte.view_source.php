<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2012, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.5
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine View Source RTE Tool
 *
 * @package		ExpressionEngine
 * @subpackage	RTE
 * @category	RTE
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class View_source_rte {
	
	public $info = array(
		'name'			=> 'View Source',
		'version'		=> '1.0',
		'description'	=> 'Triggers the RTE to switch to and from view source mode',
		'cp_only'		=> 'n'
	);
	
	private $EE;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		// Make a local reference of the ExpressionEngine super object
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------

	/**
	 * Javascript globls we need
	 *
	 * @access	public
	 */
	function globals()
	{
		$this->EE->lang->loadfile('rte');
		return array(
			'rte.view_source'	=> array(
				'code'		=> lang('view_code'),
				'content'	=> lang('view_content')
			)
		);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Libraries we need
	 *
	 * @access	public
	 * @return	mixed array of libraries
	 */
	function libraries()
	{
		return array(
			'plugin' => 'ba-resize'
		);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Javascript Definition
	 *
	 * @access	public
	 */
	function definition()
	{
		ob_start(); ?>
		
		WysiHat.addButton('view_source', {
			label:			EE.rte.view_source.code,
			'toggle-text':	EE.rte.view_source.content,
			init: function(name, $editor) {
				this.parent.init(name, $editor);
				$editor.add($editor.data('field'))
					.bind('resize', function() {
						var $this	= $(this);
			
						if ($this.is('.WysiHat-editor') && $this.is(':visible')) {
							$this.data('field')
								.height($this.height())
								.width($this.outerWidth());
						} else if ($this.is('.rte') && $this.is(':visible')) {
							$this.data('editor')
								.height($this.height())
								.width($this.width());
						}
					}).resize();
				return this;
			},
			handler: function() {
				var e = {
					target: this.$element
				};
				this.$editor.toggleHTML( e );
			}
		});
		
		function syncSizes()
		{
			var $this	= $(this);
			
			if ($this.is('.WysiHat-editor') && $this.is(':visible')) {
				$this.data('field')
					.height($this.height())
					.width($this.outerWidth());
			} else if ($this.is('.rte') && $this.is(':visible')) {
				$this.data('editor')
					.height($this.height())
					.width($this.width());
			}
		}
		
<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

} // END View_source_rte

/* End of file rte.view_source.php */
/* Location: ./system/expressionengine/rte_tools/view_source/rte.view_source.php */