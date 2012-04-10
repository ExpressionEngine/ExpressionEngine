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
 * ExpressionEngine Headings RTE Tool
 *
 * @package		ExpressionEngine
 * @subpackage	RTE
 * @category	RTE
 * @author		ExpressionEngine Dev Team
 * @link		http://expressionengine.com
 */
class Headings_rte {
	
	public $info = array(
		'name'			=> 'Headings',
		'version'		=> '1.0',
		'description'	=> 'Adds or swaps heading levels in the RTE. Can also revert text to a paragraph',
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
	 * Globals we need
	 *
	 * @access	public
	 */
	function globals()
	{
		$this->EE->lang->loadfile('rte');
		return array(
			'rte.headings'	=> array(
				'paragraph'		=> lang('paragraph'),
				'heading_1'		=> lang('heading_1'),
				'heading_2'		=> lang('heading_2'),
				'heading_3'		=> lang('heading_3'),
				'heading_4'		=> lang('heading_4'),
				'heading_5'		=> lang('heading_5'),
				'heading_6'		=> lang('heading_6')
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * JS Defintion
	 *
	 * @access	public
	 */
	function definition()
	{
		ob_start(); ?>
		
		var $formatting_selector = $('<select class="button picker"/>');
		
		$formatting_selector
			.append('<option value="p">' + EE.rte.headings.paragraph + '</option>')
			.append('<option value="h1">' + EE.rte.headings.heading_1 + '</option>')
			.append('<option value="h2">' + EE.rte.headings.heading_2 + '</option>')
			.append('<option value="h3">' + EE.rte.headings.heading_3 + '</option>')
			.append('<option value="h4">' + EE.rte.headings.heading_4 + '</option>')
			.append('<option value="h5">' + EE.rte.headings.heading_5 + '</option>')
			.append('<option value="h6">' + EE.rte.headings.heading_6 + '</option>')
			.change(function(){
				var val = $(this).val();
				if ( val != '' )
				{
					$editor.changeContentBlock( $(this).val() );

					// trigger the update
					$editor.trigger( EE.rte.update_event );
				}
			})
			.appendTo( $parent.find('.WysiHat-editor-toolbar') );
		
		// update the selector as the user clicks around
		$editor.bind(
			'keyup mouseup',
			function(){
				var
				selection	= window.getSelection(),
				hasRange	= !! selection.rangeCount,
				el			= selection.anchorNode,
				blocks	 	= 'p,h1,h2,h3,h4,h5,h6',
				$el, $p;

				if ( hasRange )
				{
					while ( el.nodeType != "1" )
					{
						el = el.parentNode;
						
						if (el == null)
						{
							break;
						}
					}
				}
				
				$el 	= $(el);
				$parent	= $el.parents(blocks);
				if ( $el.is(blocks) )
				{
					$formatting_selector.val(el.nodeName.toLowerCase());
				}
				else if ( $parent.length )
				{
					$formatting_selector.val($parent.get(0).nodeName.toLowerCase());
				}
				else
				{
					$formatting_selector.val('');
				}
			});
		
<?php	$buffer = ob_get_contents();
		ob_end_clean(); 
		return $buffer;
	}

} // END Headings_rte

/* End of file rte.headings.php */
/* Location: ./system/expressionengine/rte_tools/headings/rte.headings.php */