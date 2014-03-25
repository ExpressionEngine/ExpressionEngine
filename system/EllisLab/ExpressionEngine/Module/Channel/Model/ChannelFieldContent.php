<?php
namespace EllisLab\ExpressionEngine\Module\Channel\Model;

use EllisLab\ExpressionEngine\Model\Interfaces\Field\FieldContent;
use EllisLab\ExpressionEngine\Model\DataTableField\DataTableFieldContent;

class ChannelFieldContent
	extends DataTableFieldContent
		 implements FieldContent {


	/**
	 * Renders this field's content by replacing tags in a template.
	 *
	 * @param	ParsedTemplate|string	$template	The template, either a
	 *						ParsedTemplate object or a tagdata string, in which
	 *						this FieldContent will be rendered.
	 *
	 * @return	ParsedTemplate|string	The ParsedTemplate or tagdata string
	 *						with the relevant tags replaced.
	 */	
	public function render($template) 
	{
		// TODO
	}

}
