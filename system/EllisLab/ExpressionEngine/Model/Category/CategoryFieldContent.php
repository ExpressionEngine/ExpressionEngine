
<?php
namespace EllisLab\ExpressionEngine\Model\Category;

use EllisLab\ExpressionEngine\Model\Interfaces\Field\FieldContent;

class CategoryFieldContent
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


	/**
	 * Validate this piece of field content to ensure that it is valid for
	 * saving.  On failure, throw an exception containing all error 
	 * information.
 	 * 
	 * @return void
	 * 
 	 * @throws	FieldContentInvalidException	On validation failure a 
	 * 						FieldContentInvalidException will be thrown with 
	 * 						all relevant errors.
	 */
	public function validate() 
	{
		// TODO	
	}


}
